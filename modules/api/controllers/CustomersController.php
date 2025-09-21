<?php

namespace app\modules\api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use yii\filters\AccessControl;
use app\models\Customer;
use app\modules\api\components\JwtAuth;

/**
 * Customers API controller
 */
class CustomersController extends ActiveController
{
    public $modelClass = 'app\models\Customer';

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
                        return Yii::$app->user->can('customers.view');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['create', 'quick-create'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('customers.create');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['update'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('customers.update');
                    }
                ],
                [
                    'allow' => true,
                    'actions' => ['delete'],
                    'roles' => ['@'],
                    'matchCallback' => function() {
                        return Yii::$app->user->can('customers.delete');
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
        
        $query = Customer::find()
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
     * Search customers
     */
    public function actionSearch()
    {
        $request = Yii::$app->request;
        $q = $request->get('q');
        
        if (!$q) {
            return ['success' => false, 'message' => 'Query parameter is required'];
        }
        
        $customers = Customer::search($q);
        
        return [
            'success' => true,
            'data' => $customers,
        ];
    }

    /**
     * Quick create customer (for inline creation in invoices)
     */
    public function actionQuickCreate()
    {
        $request = Yii::$app->request;
        $name = $request->post('name');
        $phone = $request->post('phone');
        $email = $request->post('email');
        
        if (!$name) {
            throw new BadRequestHttpException('Customer name is required');
        }
        
        $customer = Customer::quickCreate($name, $phone, $email);
        
        if ($customer) {
            return [
                'success' => true,
                'data' => $customer->toArray(),
            ];
        } else {
            throw new BadRequestHttpException('Failed to create customer');
        }
    }

    /**
     * Get customer with sales summary
     */
    public function actionView($id)
    {
        $customer = $this->findModel($id);
        
        $data = $customer->toArray();
        $data['total_sales'] = $customer->getTotalSales();
        $data['outstanding_balance'] = $customer->getOutstandingBalance();
        $data['display_name'] = $customer->getDisplayName();
        
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
        $model = Customer::find()
            ->where(['id' => $id])
            ->andWhere(['is', 'deleted_at', null])
            ->one();
            
        if ($model === null) {
            throw new NotFoundHttpException('Customer not found');
        }
        
        return $model;
    }
}
