<?php

namespace app\controllers;

use Yii;
use app\models\Vendor;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * VendorController implements the CRUD actions for Vendor model.
 */
class VendorController extends BaseController
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
     * Lists all Vendor models.
     */
    public function actionIndex()
    {
        $this->checkPermission('vendors.view');

        $query = Vendor::find()
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
     * Displays a single Vendor model.
     */
    public function actionView($id)
    {
        $this->checkPermission('vendors.view');
        
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Vendor model.
     */
    public function actionCreate()
    {
        $this->checkPermission('vendors.create');
        
        $model = new Vendor();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                $this->setFlash('success', 'Vendor created successfully.');
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
     * Updates an existing Vendor model.
     */
    public function actionUpdate($id)
    {
        $this->checkPermission('vendors.update');
        
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            $this->setFlash('success', 'Vendor updated successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Vendor model.
     */
    public function actionDelete($id)
    {
        $this->checkPermission('vendors.delete');
        
        $model = $this->findModel($id);
        $model->deleted_at = time();
        $model->save();

        $this->setFlash('success', 'Vendor deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Search vendors via AJAX
     */
    public function actionSearch()
    {
        $this->checkPermission('vendors.view');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        if (!$q) {
            return ['results' => []];
        }

        $vendors = Vendor::search($q);
        
        $results = [];
        foreach ($vendors as $vendor) {
            $results[] = [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'phone' => $vendor->phone,
                'email' => $vendor->email,
                'display_name' => $vendor->getDisplayName(),
            ];
        }

        return ['results' => $results];
    }

    /**
     * Quick create vendor via AJAX
     */
    public function actionQuickCreate()
    {
        $this->checkPermission('vendors.create');
        
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
                'message' => 'Vendor name is required.'
            ]);
        }
        
        $vendor = Vendor::quickCreate($name, $phone, $email);
        
        if ($vendor) {
            return $this->asJson([
                'success' => true,
                'message' => 'Vendor created successfully.',
                'vendor' => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'phone' => $vendor->phone,
                    'email' => $vendor->email,
                    'display_name' => $vendor->getDisplayName(),
                ]
            ]);
        } else {
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to create vendor.'
            ]);
        }
    }

    /**
     * Export vendors to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('vendors.view');
        
        $query = Vendor::find()
            ->where(['is', 'deleted_at', null])
            ->orderBy(['name' => SORT_ASC]);

        $filename = 'vendors_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Name',
            'Phone',
            'Email',
            'Address',
            'Tax Number',
            'Total Purchases',
            'Outstanding Balance',
            'Created At',
        ]);
        
        foreach ($query->batch(100) as $vendors) {
            foreach ($vendors as $vendor) {
                fputcsv($output, [
                    $vendor->name,
                    $vendor->phone,
                    $vendor->email,
                    $vendor->address,
                    $vendor->tax_no,
                    $vendor->getTotalPurchases(),
                    $vendor->getOutstandingBalance(),
                    date('Y-m-d H:i:s', $vendor->created_at),
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Finds the Vendor model based on its primary key value.
     */
    protected function findModel($id)
    {
        if (($model = Vendor::find()->where(['id' => $id])->andWhere(['is', 'deleted_at', null])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
