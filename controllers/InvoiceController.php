<?php

namespace app\controllers;

use Yii;
use app\models\Invoice;
use app\models\InvoiceLine;
use app\models\Product;
use app\models\Customer;
use app\models\Vendor;
use app\models\Location;
use app\models\Template;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * InvoiceController implements the CRUD actions for Invoice model.
 */
class InvoiceController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return array_merge(
            parent::behaviors(),
            [
                'verbs' => [
                    'class' => VerbFilter::class,
                    'actions' => [
                        'delete' => ['POST'],
                        'confirm' => ['POST'],
                        'void' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Invoice models.
     */
    public function actionIndex()
    {
        $this->checkPermission('invoices.view');

        $query = Invoice::find()
            ->with(['location', 'customer', 'vendor', 'createdBy'])
            ->where(['is', 'deleted_at', null]);

        // Filters
        $type = Yii::$app->request->get('type');
        if ($type) {
            $query->andWhere(['type' => $type]);
        }

        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        $locationId = Yii::$app->request->get('location_id');
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }

        $dateFrom = Yii::$app->request->get('date_from');
        if ($dateFrom) {
            $query->andWhere(['>=', 'created_at', strtotime($dateFrom)]);
        }

        $dateTo = Yii::$app->request->get('date_to');
        if ($dateTo) {
            $query->andWhere(['<=', 'created_at', strtotime($dateTo . ' 23:59:59')]);
        }

        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'code', $search],
                ['like', 'notes', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Displays a single Invoice model.
     */
    public function actionView($id)
    {
        $this->checkPermission('invoices.view');
        
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Invoice model.
     */
    public function actionCreate()
    {
        $this->checkPermission('invoices.create');
        
        $type = Yii::$app->request->get('type', Invoice::TYPE_SALE);
        
        $model = new Invoice();
        $model->type = $type;
        $model->status = Invoice::STATUS_DRAFT;
        $model->location_id = Yii::$app->user->identity->getDefaultLocationId();
        
        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $postData = $this->request->post();
                
                // Load invoice data
                if ($model->load($postData)) {
                    $model->generateCode();
                    
                    if ($model->save()) {
                        // Save invoice lines
                        if (isset($postData['InvoiceLine']) && is_array($postData['InvoiceLine'])) {
                            foreach ($postData['InvoiceLine'] as $lineData) {
                                if (!empty($lineData['product_id']) && !empty($lineData['qty'])) {
                                    $line = new InvoiceLine();
                                    $line->invoice_id = $model->id;
                                    $line->load($lineData, '');
                                    
                                    if (!$line->save()) {
                                        throw new BadRequestHttpException('Failed to save invoice line: ' . implode(', ', $line->getFirstErrors()));
                                    }
                                }
                            }
                        }
                        
                        // Recalculate totals
                        $model->calculateTotals();
                        $model->save();
                        
                        $transaction->commit();
                        
                        $this->setFlash('success', 'Invoice created successfully.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->setFlash('error', 'Failed to create invoice: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'locations' => Location::getDropdownOptions(),
            'customers' => Customer::getDropdownOptions(),
            'vendors' => Vendor::getDropdownOptions(),
        ]);
    }

    /**
     * Updates an existing Invoice model.
     */
    public function actionUpdate($id)
    {
        $this->checkPermission('invoices.update');
        
        $model = $this->findModel($id);
        
        if (!$model->isEditable()) {
            $this->setFlash('error', 'This invoice cannot be edited in its current status.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $postData = $this->request->post();
                
                if ($model->load($postData) && $model->save()) {
                    // Delete existing lines
                    InvoiceLine::deleteAll(['invoice_id' => $model->id]);
                    
                    // Save new lines
                    if (isset($postData['InvoiceLine']) && is_array($postData['InvoiceLine'])) {
                        foreach ($postData['InvoiceLine'] as $lineData) {
                            if (!empty($lineData['product_id']) && !empty($lineData['qty'])) {
                                $line = new InvoiceLine();
                                $line->invoice_id = $model->id;
                                $line->load($lineData, '');
                                
                                if (!$line->save()) {
                                    throw new BadRequestHttpException('Failed to save invoice line: ' . implode(', ', $line->getFirstErrors()));
                                }
                            }
                        }
                    }
                    
                    // Recalculate totals
                    $model->calculateTotals();
                    $model->save();
                    
                    $transaction->commit();
                    
                    $this->setFlash('success', 'Invoice updated successfully.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->setFlash('error', 'Failed to update invoice: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'locations' => Location::getDropdownOptions(),
            'customers' => Customer::getDropdownOptions(),
            'vendors' => Vendor::getDropdownOptions(),
        ]);
    }

    /**
     * Deletes an existing Invoice model.
     */
    public function actionDelete($id)
    {
        $this->checkPermission('invoices.delete');
        
        $model = $this->findModel($id);
        
        if ($model->status === Invoice::STATUS_CONFIRMED) {
            $this->setFlash('error', 'Cannot delete a confirmed invoice.');
            return $this->redirect(['index']);
        }
        
        $model->deleted_at = time();
        $model->save();

        $this->setFlash('success', 'Invoice deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Confirm invoice
     */
    public function actionConfirm($id)
    {
        $this->checkPermission('invoices.confirm');
        
        $model = $this->findModel($id);
        
        try {
            $model->confirm();
            $this->setFlash('success', 'Invoice confirmed successfully.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to confirm invoice: ' . $e->getMessage());
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Void invoice
     */
    public function actionVoid($id)
    {
        $this->checkPermission('invoices.void');
        
        $model = $this->findModel($id);
        
        try {
            $model->void();
            $this->setFlash('success', 'Invoice voided successfully.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to void invoice: ' . $e->getMessage());
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Print invoice
     */
    public function actionPrint($id)
    {
        $this->checkPermission('invoices.print');
        
        $model = $this->findModel($id);
        $templateId = Yii::$app->request->get('template_id');
        $kind = Yii::$app->request->get('kind', Template::KIND_A4);
        
        $template = null;
        if ($templateId) {
            $template = Template::findOne($templateId);
        }
        
        if (!$template) {
            $template = Template::getDefault($kind);
        }
        
        if (!$template) {
            $this->setFlash('error', 'No print template found.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        // Prepare template data
        $data = $this->getPrintData($model);
        
        return $this->render('print', [
            'model' => $model,
            'template' => $template,
            'html' => $template->getFullHtml($data),
        ]);
    }

    /**
     * Add payment to invoice
     */
    public function actionAddPayment($id)
    {
        $this->checkPermission('payments.create');
        
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Invalid request method.');
        }
        
        $model = $this->findModel($id);
        $amount = Yii::$app->request->post('amount');
        $method = Yii::$app->request->post('method', 'cash');
        $ref = Yii::$app->request->post('ref');
        $notes = Yii::$app->request->post('notes');
        
        if (!$amount || $amount <= 0) {
            return $this->asJson([
                'success' => false,
                'message' => 'Payment amount is required and must be greater than 0.'
            ]);
        }
        
        try {
            $payment = $model->addPayment($amount, $method, $ref, $notes);
            
            return $this->asJson([
                'success' => true,
                'message' => 'Payment added successfully.',
                'payment' => $payment->toArray(),
                'balance' => $model->getBalance(),
            ]);
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Search products for invoice
     */
    public function actionSearchProducts()
    {
        $this->checkPermission('invoices.create');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        $locationId = Yii::$app->request->get('location_id');
        
        if (!$q) {
            return ['results' => []];
        }

        $products = Product::search($q, $locationId);
        
        $results = [];
        foreach ($products as $product) {
            $stockItem = $product->getStockItem($locationId);
            $results[] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'sell_price' => $product->sell_price,
                'cost_price' => $product->cost_price,
                'stock' => $stockItem ? $stockItem->qty : 0,
                'wac' => $stockItem ? $stockItem->wac : $product->cost_price,
            ];
        }

        return ['results' => $results];
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
     * Finds the Invoice model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = Invoice::find()->where(['id' => $id])->andWhere(['is', 'deleted_at', null])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
