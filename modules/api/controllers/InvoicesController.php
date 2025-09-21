<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use app\models\Invoice;
use app\models\InvoiceLine;
use app\models\Product;
use app\models\Template;
use app\modules\api\components\JwtAuth;

/**
 * Invoices API controller
 */
class InvoicesController extends ActiveController
{
    public $modelClass = 'app\models\Invoice';

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['authenticator'] = [
            'class' => JwtAuth::class,
        ];
        
        $behaviors['access'] = [
            'class' => AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'actions' => ['index', 'view', 'print'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('invoices.view');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['create'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('invoices.create');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('invoices.update');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('invoices.delete');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['confirm'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('invoices.confirm');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['pay'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('payments.create');
                    }
                ],
            ],
        ];
        
        return $behaviors;
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        $actions = parent::actions();
        
        // Customize the data provider for the "index" action
        $actions['index']['prepareDataProvider'] = [$this, 'prepareDataProvider'];
        
        // Remove default create/update actions to customize them
        unset($actions['create'], $actions['update']);
        
        return $actions;
    }

    /**
     * Prepare data provider for index action
     */
    public function prepareDataProvider()
    {
        $request = Yii::$app->request;
        
        $query = Invoice::find()
            ->with(['location', 'customer', 'vendor', 'createdBy'])
            ->where(['is', 'deleted_at', null]);
        
        // Filter by type
        if ($type = $request->get('type')) {
            $query->andWhere(['type' => $type]);
        }
        
        // Filter by status
        if ($status = $request->get('status')) {
            $query->andWhere(['status' => $status]);
        }
        
        // Filter by location
        if ($locationId = $request->get('location_id')) {
            $query->andWhere(['location_id' => $locationId]);
        }
        
        // Filter by date range
        if ($dateFrom = $request->get('date_from')) {
            $query->andWhere(['>=', 'created_at', strtotime($dateFrom)]);
        }
        
        if ($dateTo = $request->get('date_to')) {
            $query->andWhere(['<=', 'created_at', strtotime($dateTo . ' 23:59:59')]);
        }
        
        // Search by code or party name
        if ($q = $request->get('q')) {
            $query->andWhere([
                'or',
                ['like', 'code', $q],
                ['like', 'notes', $q],
            ]);
        }
        
        $query->orderBy(['created_at' => SORT_DESC]);
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => min($request->get('per_page', 20), 100),
            ],
        ]);
    }

    /**
     * Create invoice
     */
    public function actionCreate()
    {
        $request = Yii::$app->request;
        $data = $request->post();
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Create invoice
            $invoice = new Invoice();
            $invoice->load($data, '');
            $invoice->generateCode();
            
            if (!$invoice->save()) {
                throw new BadRequestHttpException('Failed to create invoice: ' . implode(', ', $invoice->getFirstErrors()));
            }
            
            // Create invoice lines
            if (isset($data['lines']) && is_array($data['lines'])) {
                foreach ($data['lines'] as $lineData) {
                    $line = new InvoiceLine();
                    $line->invoice_id = $invoice->id;
                    $line->load($lineData, '');
                    
                    if (!$line->save()) {
                        throw new BadRequestHttpException('Failed to create invoice line: ' . implode(', ', $line->getFirstErrors()));
                    }
                }
            }
            
            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();
            
            $transaction->commit();
            
            return [
                'success' => true,
                'data' => $this->getInvoiceData($invoice),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Update invoice
     */
    public function actionUpdate($id)
    {
        $invoice = $this->findModel($id);
        
        if (!$invoice->isEditable()) {
            throw new BadRequestHttpException('Invoice cannot be edited in current status');
        }
        
        $request = Yii::$app->request;
        $data = $request->post();
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Update invoice
            $invoice->load($data, '');
            
            if (!$invoice->save()) {
                throw new BadRequestHttpException('Failed to update invoice: ' . implode(', ', $invoice->getFirstErrors()));
            }
            
            // Update invoice lines
            if (isset($data['lines']) && is_array($data['lines'])) {
                // Delete existing lines
                InvoiceLine::deleteAll(['invoice_id' => $invoice->id]);
                
                // Create new lines
                foreach ($data['lines'] as $lineData) {
                    $line = new InvoiceLine();
                    $line->invoice_id = $invoice->id;
                    $line->load($lineData, '');
                    
                    if (!$line->save()) {
                        throw new BadRequestHttpException('Failed to create invoice line: ' . implode(', ', $line->getFirstErrors()));
                    }
                }
            }
            
            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();
            
            $transaction->commit();
            
            return [
                'success' => true,
                'data' => $this->getInvoiceData($invoice),
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Confirm invoice
     */
    public function actionConfirm($id)
    {
        $invoice = $this->findModel($id);
        
        try {
            $invoice->confirm();
            
            return [
                'success' => true,
                'message' => 'Invoice confirmed successfully',
                'data' => $this->getInvoiceData($invoice),
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Add payment to invoice
     */
    public function actionPay($id)
    {
        $invoice = $this->findModel($id);
        $request = Yii::$app->request;
        
        $amount = $request->post('amount');
        $method = $request->post('method', 'cash');
        $ref = $request->post('ref');
        $notes = $request->post('notes');
        
        if (!$amount || $amount <= 0) {
            throw new BadRequestHttpException('Payment amount is required and must be greater than 0');
        }
        
        try {
            $payment = $invoice->addPayment($amount, $method, $ref, $notes);
            
            return [
                'success' => true,
                'message' => 'Payment added successfully',
                'data' => [
                    'payment' => $payment->toArray(),
                    'invoice' => $this->getInvoiceData($invoice),
                ],
            ];
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * Print invoice
     */
    public function actionPrint($id)
    {
        $invoice = $this->findModel($id);
        $request = Yii::$app->request;
        
        $templateId = $request->get('template_id');
        $template = null;
        
        if ($templateId) {
            $template = Template::findOne($templateId);
        }
        
        if (!$template) {
            // Get default template based on invoice type
            $kind = $request->get('kind', 'pos_58');
            $template = Template::getDefault($kind);
        }
        
        if (!$template) {
            throw new NotFoundHttpException('Print template not found');
        }
        
        // Prepare template data
        $data = $this->getPrintData($invoice);
        
        return [
            'success' => true,
            'data' => [
                'html' => $template->getFullHtml($data),
                'template' => $template->toArray(),
            ],
        ];
    }

    /**
     * Get invoice data with relations
     */
    protected function getInvoiceData($invoice)
    {
        $data = $invoice->toArray();
        $data['location'] = $invoice->location ? $invoice->location->toArray() : null;
        $data['customer'] = $invoice->customer ? $invoice->customer->toArray() : null;
        $data['vendor'] = $invoice->vendor ? $invoice->vendor->toArray() : null;
        $data['created_by_name'] = $invoice->createdBy ? $invoice->createdBy->name : null;
        $data['lines'] = [];
        $data['payments'] = [];
        $data['balance'] = $invoice->getBalance();
        $data['cogs'] = $invoice->getCOGS();
        $data['gross_profit'] = $invoice->getGrossProfit();
        
        foreach ($invoice->invoiceLines as $line) {
            $lineData = $line->toArray();
            $lineData['product_name'] = $line->product->name;
            $lineData['product_sku'] = $line->product->sku;
            $data['lines'][] = $lineData;
        }
        
        foreach ($invoice->payments as $payment) {
            $data['payments'][] = $payment->toArray();
        }
        
        return $data;
    }

    /**
     * Get print data for template
     */
    protected function getPrintData($invoice)
    {
        $companySettings = \app\models\Setting::getCompanySettings();
        
        $data = [
            'company_name' => $companySettings['company.name'] ?? '',
            'company_address' => $companySettings['company.address'] ?? '',
            'company_phone' => $companySettings['company.phone'] ?? '',
            'company_email' => $companySettings['company.email'] ?? '',
            'company_tax_no' => $companySettings['company.tax_no'] ?? '',
            'company_logo' => $companySettings['company.logo'] ?? '',
            'invoice_code' => $invoice->code,
            'date' => date('Y-m-d', $invoice->created_at),
            'time' => date('H:i:s', $invoice->created_at),
            'type' => $invoice->getTypeLabel(),
            'status' => $invoice->getStatusLabel(),
            'location_name' => $invoice->location->name,
            'notes' => $invoice->notes,
            'sub_total' => number_format($invoice->sub_total, 2),
            'discount' => number_format($invoice->discount, 2),
            'tax' => number_format($invoice->tax, 2),
            'total' => number_format($invoice->total, 2),
            'paid' => number_format($invoice->paid, 2),
            'balance' => number_format($invoice->getBalance(), 2),
        ];
        
        // Add party information
        if ($invoice->type === Invoice::TYPE_SALE && $invoice->customer) {
            $data['customer_name'] = $invoice->customer->name;
            $data['customer_phone'] = $invoice->customer->phone;
            $data['customer_email'] = $invoice->customer->email;
            $data['customer_address'] = $invoice->customer->address;
        } elseif ($invoice->type === Invoice::TYPE_PURCHASE && $invoice->vendor) {
            $data['vendor_name'] = $invoice->vendor->name;
            $data['vendor_phone'] = $invoice->vendor->phone;
            $data['vendor_email'] = $invoice->vendor->email;
            $data['vendor_address'] = $invoice->vendor->address;
        }
        
        // Add lines
        $data['lines'] = [];
        foreach ($invoice->invoiceLines as $line) {
            $data['lines'][] = [
                'product_name' => $line->product->name,
                'product_sku' => $line->product->sku,
                'qty' => number_format($line->qty, 3),
                'unit_price' => number_format($line->unit_price, 2),
                'line_total' => number_format($line->line_total, 2),
            ];
        }
        
        return $data;
    }

    /**
     * Find model by ID
     */
    protected function findModel($id)
    {
        $model = Invoice::find()
            ->where(['id' => $id])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
            
        if ($model === null) {
            throw new NotFoundHttpException('Invoice not found');
        }
        
        return $model;
    }
}
