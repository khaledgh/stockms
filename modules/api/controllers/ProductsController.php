<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use app\models\Product;
use app\modules\api\components\JwtAuth;

/**
 * Products API controller
 */
class ProductsController extends ActiveController
{
    public $modelClass = 'app\models\Product';

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
                    'actions' => ['index', 'view'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('products.view');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['create'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('products.create');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('products.update');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('products.delete');
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
        
        return $actions;
    }

    /**
     * Prepare data provider for index action
     */
    public function prepareDataProvider()
    {
        $request = Yii::$app->request;
        
        $query = Product::find()
            ->where(['is_active' => true])
            ->andWhere(['is', 'deleted_at', null]);
        
        // Search by query
        if ($q = $request->get('q')) {
            $query->andWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'sku', $q],
                ['like', 'barcode', $q],
            ]);
        }
        
        // Filter by category
        if ($categoryId = $request->get('category_id')) {
            $query->andWhere(['category_id' => $categoryId]);
        }
        
        // Filter by type
        if ($typeId = $request->get('type_id')) {
            $query->andWhere(['type_id' => $typeId]);
        }
        
        // Sorting
        if ($sort = $request->get('sort')) {
            $sortParts = explode(':', $sort);
            $field = $sortParts[0];
            $direction = isset($sortParts[1]) && $sortParts[1] === 'desc' ? SORT_DESC : SORT_ASC;
            
            $allowedFields = ['name', 'sku', 'cost_price', 'sell_price', 'created_at'];
            if (in_array($field, $allowedFields)) {
                $query->orderBy([$field => $direction]);
            }
        } else {
            $query->orderBy(['name' => SORT_ASC]);
        }
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => min($request->get('per_page', 20), 100),
            ],
        ]);
    }

    /**
     * Search products by SKU or barcode
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $q = $request->get('q');
        
        if (!$q) {
            return ['success' => false, 'message' => 'Query parameter is required'];
        }
        
        $products = Product::search($q);
        
        return [
            'success' => true,
            'data' => $products,
        ];
    }

    /**
     * Get product with stock information
     */
    public function actionView($id)
    {
        $product = $this->findModel($id);
        
        // Get stock information
        $stockItems = [];
        foreach ($product->stockItems as $stockItem) {
            $stockItems[] = [
                'location_id' => $stockItem->location_id,
                'location_name' => $stockItem->location->name,
                'qty' => $stockItem->qty,
                'wac' => $stockItem->wac,
                'stock_value' => $stockItem->getStockValue(),
            ];
        }
        
        $data = $product->toArray();
        $data['stock_items'] = $stockItems;
        $data['total_stock'] = $product->getTotalStock();
        $data['is_low_stock'] = $product->isLowStock();
        $data['profit_margin'] = $product->getProfitMargin();
        
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * Find model by ID
     */
    protected function findModel($id)
    {
        $model = Product::find()
            ->where(['id' => $id])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
            
        if ($model === null) {
            throw new NotFoundHttpException('Product not found');
        }
        
        return $model;
    }

    /**
     * Check access for specific actions
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // Additional access checks can be implemented here
        // For example, sales users might only view products from their assigned locations
    }
}
