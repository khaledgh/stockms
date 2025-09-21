<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var app\models\Product $searchModel */
/** @var array $categories */
/** @var array $types */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Products';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="productIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Manage your product catalog</p>
        </div>
        <div class="btn-group">
            <?php if (Yii::$app->user->can('products.import')): ?>
                <?= Html::a('<i class="fas fa-upload me-1"></i> Import', ['import'], [
                    'class' => 'btn btn-outline-primary',
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#importModal'
                ]) ?>
            <?php endif; ?>
            
            <?= Html::a('<i class="fas fa-download me-1"></i> Export', ['export'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
            
            <?php if (Yii::$app->user->can('products.create')): ?>
                <?= Html::a('<i class="fas fa-plus me-1"></i> New Product', ['create'], [
                    'class' => 'btn btn-primary'
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3" x-ref="filterForm">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name, SKU, or barcode..."
                           value="<?= Html::encode(Yii::$app->request->get('search')) ?>"
                           x-on:input.debounce.500ms="submitFilters()">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <?= Html::dropDownList('category_id', Yii::$app->request->get('category_id'), 
                        ['' => 'All Categories'] + $categories, [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <?= Html::dropDownList('type_id', Yii::$app->request->get('type_id'), 
                        ['' => 'All Types'] + $types, [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                        '' => 'All Status',
                        '1' => 'Active',
                        '0' => 'Inactive'
                    ], [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <?= Html::a('Clear', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Grid -->
    <?php Pjax::begin(['id' => 'products-pjax']); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'summary' => '<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{begin}-{end} of {totalCount} products</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                      </div>',
        'columns' => [
            [
                'attribute' => 'sku',
                'format' => 'text',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'name',
                'format' => 'text',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->name), ['view', 'id' => $model->id], [
                        'class' => 'text-decoration-none fw-semibold'
                    ]);
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'category_id',
                'label' => 'Category',
                'value' => function ($model) {
                    return $model->category ? $model->category->name : '-';
                },
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'type_id',
                'label' => 'Type',
                'value' => function ($model) {
                    return $model->type ? $model->type->name : '-';
                },
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'cost_price',
                'format' => 'currency',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'attribute' => 'sell_price',
                'format' => 'currency',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'label' => 'Stock',
                'value' => function ($model) {
                    $stock = $model->getTotalStock();
                    $class = $model->isLowStock() ? 'text-danger fw-bold' : 'text-success';
                    return Html::tag('span', number_format($stock, 0), ['class' => $class]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'is_active',
                'label' => 'Status',
                'value' => function ($model) {
                    $class = $model->is_active ? 'bg-success' : 'bg-secondary';
                    $text = $model->is_active ? 'Active' : 'Inactive';
                    return Html::tag('span', $text, ['class' => "badge {$class}"]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{view} {update} {toggle} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info me-1',
                            'title' => 'View',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'update' => function ($url, $model) {
                        if (!Yii::$app->user->can('products.update')) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary me-1',
                            'title' => 'Update',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'toggle' => function ($url, $model) {
                        if (!Yii::$app->user->can('products.update')) {
                            return '';
                        }
                        $icon = $model->is_active ? 'fas fa-toggle-on text-success' : 'fas fa-toggle-off text-secondary';
                        $title = $model->is_active ? 'Deactivate' : 'Activate';
                        return Html::a("<i class=\"{$icon}\"></i>", ['toggle-status', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-secondary me-1',
                            'title' => $title,
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => "Are you sure you want to {$title} this product?"
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if (!Yii::$app->user->can('products.delete')) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Delete',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to delete this product?'
                        ]);
                    },
                ],
                'visibleButtons' => [
                    'update' => function ($model) {
                        return Yii::$app->user->can('products.update');
                    },
                    'delete' => function ($model) {
                        return Yii::$app->user->can('products.delete');
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<!-- Import Modal -->
<?php if (Yii::$app->user->can('products.import')): ?>
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Products</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">
                            CSV format: SKU, Name, Description, Cost Price, Sell Price, Reorder Level, Barcode, Category, Type
                        </div>
                    </div>
                    <div class="mb-3">
                        <a href="<?= Url::to(['sample-csv']) ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-download me-1"></i> Download Sample CSV
                        </a>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitImport()">Import</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function productIndex() {
    return {
        init() {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));
        },
        
        submitFilters() {
            this.$refs.filterForm.submit();
        }
    };
}

function submitImport() {
    const form = document.getElementById('importForm');
    const formData = new FormData(form);
    
    fetch('<?= Url::to(['import']) ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            bootstrap.Modal.getInstance(document.getElementById('importModal')).hide();
            location.reload();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        showError('Import failed: ' + error.message);
    });
}
</script>
