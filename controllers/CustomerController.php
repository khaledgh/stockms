<?php

namespace app\controllers;

use Yii;
use app\models\Customer;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * CustomerController implements the CRUD actions for Customer model.
 */
class CustomerController extends BaseController
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
                    ],
                ],
            ]
        );
    }

    /**
     * Lists all Customer models.
     */
    public function actionIndex()
    {
        $this->checkPermission('customers.view');

        $query = Customer::find()
            ->where(['is', 'deleted_at', null]);

        // Search functionality
        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'name', $search],
                ['like', 'phone', $search],
                ['like', 'email', $search],
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['name' => SORT_ASC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Customer model.
     */
    public function actionView($id)
    {
        $this->checkPermission('customers.view');
        
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Customer model.
     */
    public function actionCreate()
    {
        $this->checkPermission('customers.create');
        
        $model = new Customer();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                $this->setFlash('success', 'Customer created successfully.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Customer model.
     */
    public function actionUpdate($id)
    {
        $this->checkPermission('customers.update');
        
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            $this->setFlash('success', 'Customer updated successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Customer model.
     */
    public function actionDelete($id)
    {
        $this->checkPermission('customers.delete');
        
        $model = $this->findModel($id);
        $model->deleted_at = time();
        $model->save();

        $this->setFlash('success', 'Customer deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Search customers via AJAX
     */
    public function actionSearch()
    {
        $this->checkPermission('customers.view');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        if (!$q) {
            return ['results' => []];
        }

        $customers = Customer::search($q);
        
        $results = [];
        foreach ($customers as $customer) {
            $results[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'display_name' => $customer->getDisplayName(),
            ];
        }

        return ['results' => $results];
    }

    /**
     * Quick create customer via AJAX
     */
    public function actionQuickCreate()
    {
        $this->checkPermission('customers.create');
        
        if (!Yii::$app->request->isPost) {
            return $this->asJson([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);
        }
        
        $name = Yii::$app->request->post('name');
        $phone = Yii::$app->request->post('phone');
        $email = Yii::$app->request->post('email');
        
        if (!$name) {
            return $this->asJson([
                'success' => false,
                'message' => 'Customer name is required.'
            ]);
        }
        
        $customer = Customer::quickCreate($name, $phone, $email);
        
        if ($customer) {
            return $this->asJson([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'display_name' => $customer->getDisplayName(),
                ]
            ]);
        } else {
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to create customer.'
            ]);
        }
    }

    /**
     * Export customers to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('customers.view');
        
        $query = Customer::find()
            ->where(['is', 'deleted_at', null])
            ->orderBy(['name' => SORT_ASC]);

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Name',
            'Phone',
            'Email',
            'Address',
            'Total Sales',
            'Outstanding Balance',
            'Created At',
        ]);
        
        foreach ($query->batch(100) as $customers) {
            foreach ($customers as $customer) {
                fputcsv($output, [
                    $customer->name,
                    $customer->phone,
                    $customer->email,
                    $customer->address,
                    $customer->getTotalSales(),
                    $customer->getOutstandingBalance(),
                    date('Y-m-d H:i:s', $customer->created_at),
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Finds the Customer model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = Customer::find()->where(['id' => $id])->andWhere(['is', 'deleted_at', null])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
