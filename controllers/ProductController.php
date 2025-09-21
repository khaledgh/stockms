<?php

namespace app\controllers;

use Yii;
use app\models\Product;
use app\models\Category;
use app\models\Type;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\web\Response;
use yii\filters\VerbFilter;

/**
 * ProductController implements the CRUD actions for Product model.
 */
class ProductController extends BaseController
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
     * Lists all Product models.
     *
     * @return string
     */
    public function actionIndex()
    {
        $this->checkPermission('products.view');

        $searchModel = new Product();
        $query = Product::find()
            ->with(['category', 'type'])
            ->where(['is', 'deleted_at', null]);

        // Search functionality
        $search = Yii::$app->request->get('search');
        if ($search) {
            $query->andWhere([
                'or',
                ['like', 'name', $search],
                ['like', 'sku', $search],
                ['like', 'barcode', $search],
            ]);
        }

        // Category filter
        $categoryId = Yii::$app->request->get('category_id');
        if ($categoryId) {
            $query->andWhere(['category_id' => $categoryId]);
        }

        // Type filter
        $typeId = Yii::$app->request->get('type_id');
        if ($typeId) {
            $query->andWhere(['type_id' => $typeId]);
        }

        // Status filter
        $status = Yii::$app->request->get('status');
        if ($status !== null) {
            $query->andWhere(['is_active' => $status]);
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
            'searchModel' => $searchModel,
            'categories' => Category::getDropdownOptions(),
            'types' => Type::getDropdownOptions(),
        ]);
    }

    /**
     * Displays a single Product model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $this->checkPermission('products.view');
        
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new Product model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|Response
     */
    public function actionCreate()
    {
        $this->checkPermission('products.create');
        
        $model = new Product();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                $this->setFlash('success', 'Product created successfully.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
            'categories' => Category::getDropdownOptions(),
            'types' => Type::getDropdownOptions(),
        ]);
    }

    /**
     * Updates an existing Product model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id ID
     * @return string|Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $this->checkPermission('products.update');
        
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            $this->setFlash('success', 'Product updated successfully.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'categories' => Category::getDropdownOptions(),
            'types' => Type::getDropdownOptions(),
        ]);
    }

    /**
     * Deletes an existing Product model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id ID
     * @return Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->checkPermission('products.delete');
        
        $model = $this->findModel($id);
        $model->deleted_at = time();
        $model->save();

        $this->setFlash('success', 'Product deleted successfully.');
        return $this->redirect(['index']);
    }

    /**
     * Search products via AJAX
     */
    public function actionSearch()
    {
        $this->checkPermission('products.view');
        
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q');
        if (!$q) {
            return ['results' => []];
        }

        $products = Product::search($q);
        
        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'sell_price' => $product->sell_price,
                'cost_price' => $product->cost_price,
                'stock' => $product->getTotalStock(),
                'is_low_stock' => $product->isLowStock(),
            ];
        }

        return ['results' => $results];
    }

    /**
     * Import products from CSV
     */
    public function actionImport()
    {
        $this->checkPermission('products.import');
        
        if (Yii::$app->request->isPost) {
            $uploadedFile = UploadedFile::getInstanceByName('csv_file');
            
            if ($uploadedFile) {
                $filePath = Yii::getAlias('@runtime/uploads/') . $uploadedFile->name;
                
                if ($uploadedFile->saveAs($filePath)) {
                    $results = $this->processCsvImport($filePath);
                    unlink($filePath); // Clean up
                    
                    return $this->asJson([
                        'success' => true,
                        'message' => "Import completed. {$results['success']} products imported, {$results['errors']} errors.",
                        'results' => $results,
                    ]);
                }
            }
            
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to upload file.',
            ]);
        }

        return $this->render('import');
    }

    /**
     * Process CSV import
     */
    private function processCsvImport($filePath)
    {
        $results = ['success' => 0, 'errors' => 0, 'messages' => []];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // Skip header row
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                try {
                    $product = new Product();
                    $product->sku = $data[0] ?? '';
                    $product->name = $data[1] ?? '';
                    $product->description = $data[2] ?? '';
                    $product->cost_price = floatval($data[3] ?? 0);
                    $product->sell_price = floatval($data[4] ?? 0);
                    $product->reorder_level = intval($data[5] ?? 0);
                    $product->barcode = $data[6] ?? null;
                    
                    // Find category by name
                    if (!empty($data[7])) {
                        $category = Category::find()->where(['name' => $data[7]])->one();
                        if ($category) {
                            $product->category_id = $category->id;
                        }
                    }
                    
                    // Find type by name
                    if (!empty($data[8])) {
                        $type = Type::find()->where(['name' => $data[8]])->one();
                        if ($type) {
                            $product->type_id = $type->id;
                        }
                    }
                    
                    if ($product->save()) {
                        $results['success']++;
                    } else {
                        $results['errors']++;
                        $results['messages'][] = "Row {$results['success'] + $results['errors']}: " . implode(', ', $product->getFirstErrors());
                    }
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['messages'][] = "Row {$results['success'] + $results['errors']}: " . $e->getMessage();
                }
            }
            
            fclose($handle);
        }
        
        return $results;
    }

    /**
     * Export products to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('products.view');
        
        $query = Product::find()
            ->with(['category', 'type'])
            ->where(['is_active' => true])
            ->andWhere(['is', 'deleted_at', null]);

        $filename = 'products_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'SKU',
            'Name',
            'Description',
            'Cost Price',
            'Sell Price',
            'Reorder Level',
            'Barcode',
            'Category',
            'Type',
            'Total Stock',
            'Status',
            'Created At',
        ]);
        
        foreach ($query->batch(100) as $products) {
            foreach ($products as $product) {
                fputcsv($output, [
                    $product->sku,
                    $product->name,
                    $product->description,
                    $product->cost_price,
                    $product->sell_price,
                    $product->reorder_level,
                    $product->barcode,
                    $product->category ? $product->category->name : '',
                    $product->type ? $product->type->name : '',
                    $product->getTotalStock(),
                    $product->is_active ? 'Active' : 'Inactive',
                    date('Y-m-d H:i:s', $product->created_at),
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Toggle product status
     */
    public function actionToggleStatus($id)
    {
        $this->checkPermission('products.update');
        
        $model = $this->findModel($id);
        $model->is_active = !$model->is_active;
        
        if ($model->save()) {
            $status = $model->is_active ? 'activated' : 'deactivated';
            $this->setFlash('success', "Product {$status} successfully.");
        } else {
            $this->setFlash('error', 'Failed to update product status.');
        }
        
        return $this->redirect(['index']);
    }

    /**
     * Finds the Product model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Product the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Product::find()->where(['id' => $id])->andWhere(['is', 'deleted_at', null])->one()) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
