<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use app\models\Vendor;
use app\modules\api\components\JwtAuth;

/**
 * Vendors API controller
 */
class VendorsController extends ActiveController
{
    public $modelClass = 'app\models\Vendor';

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
                    'actions' => ['index', 'view', 'search'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('vendors.view');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['create', 'quick-create'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('vendors.create');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('vendors.update');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('vendors.delete');
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
        
        $query = Vendor::find()
            ->where(['is', 'deleted_at', null]);
        
        // Search by query
        if ($q = $request->get('q')) {
            $query->andWhere([
                'or',
                ['like', 'name', $q],
                ['like', 'phone', $q],
                ['like', 'email', $q],
            ]);
        }
        
        $query->orderBy(['name' => SORT_ASC]);
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => min($request->get('per_page', 20), 100),
            ],
        ]);
    }

    /**
     * Search vendors
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $q = $request->get('q');
        
        if (!$q) {
            return ['success' => false, 'message' => 'Query parameter is required'];
        }
        
        $vendors = Vendor::search($q);
        
        return [
            'success' => true,
            'data' => $vendors,
        ];
    }

    /**
     * Quick create vendor (for inline creation in invoices)
     */
    public function actionQuickCreate()
    {
        $request = Yii::$app->request;
        $name = $request->post('name');
        $phone = $request->post('phone');
        $email = $request->post('email');
        
        if (!$name) {
            throw new BadRequestHttpException('Vendor name is required');
        }
        
        $vendor = Vendor::quickCreate($name, $phone, $email);
        
        if ($vendor) {
            return [
                'success' => true,
                'data' => $vendor->toArray(),
            ];
        } else {
            throw new BadRequestHttpException('Failed to create vendor');
        }
    }

    /**
     * Get vendor with purchase summary
     */
    public function actionView($id)
    {
        $vendor = $this->findModel($id);
        
        $data = $vendor->toArray();
        $data['total_purchases'] = $vendor->getTotalPurchases();
        $data['outstanding_balance'] = $vendor->getOutstandingBalance();
        $data['display_name'] = $vendor->getDisplayName();
        
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
        $model = Vendor::find()
            ->where(['id' => $id])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
            
        if ($model === null) {
            throw new NotFoundHttpException('Vendor not found');
        }
        
        return $model;
    }
}
