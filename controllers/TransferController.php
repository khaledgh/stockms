<?php

namespace app\controllers;

use Yii;
use app\models\Transfer;
use app\models\TransferLine;
use app\models\Product;
use app\models\Location;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * TransferController manages stock transfers between locations
 */
class TransferController extends BaseController
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
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Transfer models.
     */
    public function actionIndex()
    {
        $this->checkPermission('inventory.transfer');

        $query = Transfer::find()
            ->with(['fromLocation', 'toLocation', 'createdBy'])
            ->where(['is', 'deleted_at', null]);

        // Filters
        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['status' => $status]);
        }

        $fromLocationId = Yii::$app->request->get('from_location_id');
        if ($fromLocationId) {
            $query->andWhere(['from_location_id' => $fromLocationId]);
        }

        $toLocationId = Yii::$app->request->get('to_location_id');
        if ($toLocationId) {
            $query->andWhere(['to_location_id' => $toLocationId]);
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
     * Displays a single Transfer model.
     */
    public function actionView($id)
    {
        $this->checkPermission('inventory.transfer');
        
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Transfer model.
     */
    public function actionCreate()
    {
        $this->checkPermission('inventory.transfer');
        
        $model = new Transfer();
        $model->status = Transfer::STATUS_DRAFT;

        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $postData = $this->request->post();
                
                // Load transfer data
                if ($model->load($postData)) {
                    $model->generateCode();
                    
                    if ($model->save()) {
                        // Save transfer lines
                        if (isset($postData['TransferLine']) && is_array($postData['TransferLine'])) {
                            foreach ($postData['TransferLine'] as $lineData) {
                                if (!empty($lineData['product_id']) && !empty($lineData['qty'])) {
                                    $line = new TransferLine();
                                    $line->transfer_id = $model->id;
                                    $line->load($lineData, '');
                                    
                                    if (!$line->save()) {
                                        throw new BadRequestHttpException('Failed to save transfer line: ' . implode(', ', $line->getFirstErrors()));
                                    }
                                }
                            }
                        }
                        
                        $transaction->commit();
                        
                        $this->setFlash('success', 'Transfer created successfully.');
                        return $this->redirect(['view', 'id' => $model->id]);
                    }
                }
                
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->setFlash('error', 'Failed to create transfer: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'model' => $model,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Updates an existing Transfer model.
     */
    public function actionUpdate($id)
    {
        $this->checkPermission('inventory.transfer');
        
        $model = $this->findModel($id);
        
        if (!$model->isEditable()) {
            $this->setFlash('error', 'This transfer cannot be edited in its current status.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $postData = $this->request->post();
                
                if ($model->load($postData) && $model->save()) {
                    // Delete existing lines
                    TransferLine::deleteAll(['transfer_id' => $model->id]);
                    
                    // Save new lines
                    if (isset($postData['TransferLine']) && is_array($postData['TransferLine'])) {
                        foreach ($postData['TransferLine'] as $lineData) {
                            if (!empty($lineData['product_id']) && !empty($lineData['qty'])) {
                                $line = new TransferLine();
                                $line->transfer_id = $model->id;
                                $line->load($lineData, '');
                                
                                if (!$line->save()) {
                                    throw new BadRequestHttpException('Failed to save transfer line: ' . implode(', ', $line->getFirstErrors()));
                                }
                            }
                        }
                    }
                    
                    $transaction->commit();
                    
                    $this->setFlash('success', 'Transfer updated successfully.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->setFlash('error', 'Failed to update transfer: ' . $e->getMessage());
            }
        }

        return $this->render('update', [
            'model' => $model,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Deletes an existing Transfer model.
     */
    public function actionDelete($id)
    {
        $this->checkPermission('inventory.transfer');
        
        $model = $this->findModel($id);
        
        if ($model->status === Transfer::STATUS_CONFIRMED) {
            $this->setFlash('error', 'Cannot delete a confirmed transfer.');
            return $this->redirect(['index']);
        }
        
        $model->deleted_at = time();
        $model->save();

        $this->setFlash('success', 'Transfer deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Confirm transfer
     */
    public function actionConfirm($id)
    {
        $this->checkPermission('inventory.transfer');
        
        $model = $this->findModel($id);
        
        try {
            $model->confirm();
            $this->setFlash('success', 'Transfer confirmed successfully. Stock has been moved.');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to confirm transfer: ' . $e->getMessage());
        }
        
        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Search products for transfer
     */
    public function actionSearchProducts()
    {
        $this->checkPermission('inventory.transfer');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        $fromLocationId = Yii::$app->request->get('from_location_id');
        
        if (!$q || !$fromLocationId) {
            return ['results' => []];
        }

        $products = Product::search($q, $fromLocationId);
        
        $results = [];
        foreach ($products as $product) {
            $stockItem = $product->getStockItem($fromLocationId);
            if ($stockItem && $stockItem->qty > 0) {
                $results[] = [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'available_qty' => $stockItem->qty,
                    'wac' => $stockItem->wac,
                ];
            }
        }

        return ['results' => $results];
    }

    /**
     * Get available stock for product at location
     */
    public function actionGetStock()
    {
        $this->checkPermission('inventory.transfer');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $productId = Yii::$app->request->get('product_id');
        $locationId = Yii::$app->request->get('location_id');
        
        if (!$productId || !$locationId) {
            return ['available_qty' => 0];
        }
        
        $product = Product::findOne($productId);
        if (!$product) {
            return ['available_qty' => 0];
        }
        
        $stockItem = $product->getStockItem($locationId);
        
        return [
            'available_qty' => $stockItem ? $stockItem->qty : 0,
            'wac' => $stockItem ? $stockItem->wac : $product->cost_price,
        ];
    }

    /**
     * Export transfers to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('inventory.transfer');
        
        $query = Transfer::find()
            ->with(['fromLocation', 'toLocation', 'createdBy'])
            ->where(['is', 'deleted_at', null])
            ->orderBy(['created_at' => SORT_DESC]);

        $filename = 'transfers_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Code',
            'From Location',
            'To Location',
            'Status',
            'Items Count',
            'Total Qty',
            'Created By',
            'Created At',
            'Confirmed At',
            'Notes',
        ]);
        
        foreach ($query->batch(100) as $transfers) {
            foreach ($transfers as $transfer) {
                fputcsv($output, [
                    $transfer->code,
                    $transfer->fromLocation->name,
                    $transfer->toLocation->name,
                    ucfirst($transfer->status),
                    count($transfer->transferLines),
                    $transfer->getTotalQty(),
                    $transfer->createdBy ? $transfer->createdBy->name : '',
                    date('Y-m-d H:i:s', $transfer->created_at),
                    $transfer->confirmed_at ? date('Y-m-d H:i:s', $transfer->confirmed_at) : '',
                    $transfer->notes,
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Finds the Transfer model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = Transfer::find()->where(['id' => $id])->andWhere(['is', 'deleted_at', null])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
