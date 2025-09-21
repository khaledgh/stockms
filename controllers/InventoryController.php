<?php

namespace app\controllers;

use Yii;
use app\models\StockItem;
use app\models\StockLedger;
use app\models\Product;
use app\models\Location;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * InventoryController manages stock items and adjustments
 */
class InventoryController extends BaseController
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
                        'adjust' => ['POST'],
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all stock items with current inventory levels
     */
    public function actionIndex()
    {
        $this->checkPermission('inventory.view');

        $query = StockItem::find()
            ->with(['product', 'location'])
            ->where(['>', 'qty', 0]);

        // Filters
        $locationId = Yii::$app->request->get('location_id');
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }

        $productSearch = Yii::$app->request->get('product_search');
        if ($productSearch) {
            $query->joinWith('product')
                ->andWhere([
                    'or',
                    ['like', 'product.name', $productSearch],
                    ['like', 'product.sku', $productSearch],
                ]);
        }

        $lowStock = Yii::$app->request->get('low_stock');
        if ($lowStock) {
            $query->joinWith('product')
                ->andWhere('stock_item.qty <= product.reorder_level');
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['product.name' => SORT_ASC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Stock adjustment form
     */
    public function actionAdjust()
    {
        $this->checkPermission('inventory.adjust');

        if (Yii::$app->request->isPost) {
            $productId = Yii::$app->request->post('product_id');
            $locationId = Yii::$app->request->post('location_id');
            $adjustmentQty = Yii::$app->request->post('adjustment_qty');
            $reason = Yii::$app->request->post('reason', 'Manual Adjustment');
            $notes = Yii::$app->request->post('notes');

            if (!$productId || !$locationId || !$adjustmentQty) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Product, location, and adjustment quantity are required.'
                ]);
            }

            $product = Product::findOne($productId);
            $location = Location::findOne($locationId);

            if (!$product || !$location) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Invalid product or location.'
                ]);
            }

            try {
                StockLedger::adjustment($product, $location, $adjustmentQty, $reason, $notes);

                return $this->asJson([
                    'success' => true,
                    'message' => 'Stock adjustment completed successfully.'
                ]);
            } catch (\Exception $e) {
                return $this->asJson([
                    'success' => false,
                    'message' => 'Failed to adjust stock: ' . $e->getMessage()
                ]);
            }
        }

        return $this->render('adjust', [
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Stock ledger (movement history)
     */
    public function actionLedger()
    {
        $this->checkPermission('inventory.view');

        $query = StockLedger::find()
            ->with(['product', 'location', 'createdBy']);

        // Filters
        $productId = Yii::$app->request->get('product_id');
        if ($productId) {
            $query->andWhere(['product_id' => $productId]);
        }

        $locationId = Yii::$app->request->get('location_id');
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }

        $reason = Yii::$app->request->get('reason');
        if ($reason) {
            $query->andWhere(['reason' => $reason]);
        }

        $dateFrom = Yii::$app->request->get('date_from');
        if ($dateFrom) {
            $query->andWhere(['>=', 'moved_at', strtotime($dateFrom)]);
        }

        $dateTo = Yii::$app->request->get('date_to');
        if ($dateTo) {
            $query->andWhere(['<=', 'moved_at', strtotime($dateTo . ' 23:59:59')]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['moved_at' => SORT_DESC],
            ],
        ]);

        return $this->render('ledger', [
            'dataProvider' => $dataProvider,
            'locations' => Location::getDropdownOptions(),
            'reasons' => StockLedger::getReasonOptions(),
        ]);
    }

    /**
     * Low stock report
     */
    public function actionLowStock()
    {
        $this->checkPermission('inventory.view');

        $query = StockItem::find()
            ->with(['product', 'location'])
            ->joinWith('product')
            ->where('stock_item.qty <= product.reorder_level')
            ->andWhere(['>', 'product.reorder_level', 0]);

        $locationId = Yii::$app->request->get('location_id');
        if ($locationId) {
            $query->andWhere(['stock_item.location_id' => $locationId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['product.name' => SORT_ASC],
            ],
        ]);

        return $this->render('low-stock', [
            'dataProvider' => $dataProvider,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Stock valuation report
     */
    public function actionValuation()
    {
        $this->checkPermission('inventory.view');

        $query = StockItem::find()
            ->with(['product', 'location'])
            ->where(['>', 'qty', 0]);

        $locationId = Yii::$app->request->get('location_id');
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => ['product.name' => SORT_ASC],
            ],
        ]);

        // Calculate totals
        $totalQty = 0;
        $totalValue = 0;

        foreach ($query->all() as $stockItem) {
            $totalQty += $stockItem->qty;
            $totalValue += $stockItem->getStockValue();
        }

        return $this->render('valuation', [
            'dataProvider' => $dataProvider,
            'locations' => Location::getDropdownOptions(),
            'totalQty' => $totalQty,
            'totalValue' => $totalValue,
        ]);
    }

    /**
     * Search products for adjustment
     */
    public function actionSearchProducts()
    {
        $this->checkPermission('inventory.adjust');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        $locationId = Yii::$app->request->get('location_id');
        
        if (!$q) {
            return ['results' => []];
        }

        $products = Product::search($q);
        
        $results = [];
        foreach ($products as $product) {
            $stockItem = $product->getStockItem($locationId);
            $results[] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'current_stock' => $stockItem ? $stockItem->qty : 0,
                'wac' => $stockItem ? $stockItem->wac : $product->cost_price,
                'reorder_level' => $product->reorder_level,
            ];
        }

        return ['results' => $results];
    }

    /**
     * Export inventory to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('inventory.view');
        
        $locationId = Yii::$app->request->get('location_id');
        
        $query = StockItem::find()
            ->with(['product', 'location'])
            ->where(['>', 'qty', 0]);
            
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }

        $filename = 'inventory_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'SKU',
            'Product Name',
            'Location',
            'Quantity',
            'WAC',
            'Stock Value',
            'Reorder Level',
            'Status',
        ]);
        
        foreach ($query->batch(100) as $stockItems) {
            foreach ($stockItems as $stockItem) {
                $status = 'Normal';
                if ($stockItem->qty <= $stockItem->product->reorder_level) {
                    $status = 'Low Stock';
                }
                if ($stockItem->qty <= 0) {
                    $status = 'Out of Stock';
                }
                
                fputcsv($output, [
                    $stockItem->product->sku,
                    $stockItem->product->name,
                    $stockItem->location->name,
                    $stockItem->qty,
                    $stockItem->wac,
                    $stockItem->getStockValue(),
                    $stockItem->product->reorder_level,
                    $status,
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
}
