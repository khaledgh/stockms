<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $locations */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Inventory';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="inventoryIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Current stock levels across all locations</p>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="fas fa-exclamation-triangle me-1"></i> Low Stock', ['low-stock'], [
                'class' => 'btn btn-outline-warning'
            ]) ?>
            
            <?= Html::a('<i class="fas fa-chart-line me-1"></i> Valuation', ['valuation'], [
                'class' => 'btn btn-outline-info'
            ]) ?>
            
            <?= Html::a('<i class="fas fa-history me-1"></i> Ledger', ['ledger'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
            
            <?= Html::a('<i class="fas fa-download me-1"></i> Export', ['export'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
            
            <?php if (Yii::$app->user->can('inventory.adjust')): ?>
                <?= Html::a('<i class="fas fa-edit me-1"></i> Adjust Stock', ['adjust'], [
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
                    <label class="form-label">Search Products</label>
                    <input type="text" 
                           name="product_search" 
                           class="form-control" 
                           placeholder="Search by product name or SKU..."
                           value="<?= Html::encode(Yii::$app->request->get('product_search')) ?>"
                           x-on:input.debounce.500ms="submitFilters()">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <?= Html::dropDownList('location_id', Yii::$app->request->get('location_id'), 
                        ['' => 'All Locations'] + $locations, [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Stock Level</label>
                    <?= Html::dropDownList('low_stock', Yii::$app->request->get('low_stock'), [
                        '' => 'All Items',
                        '1' => 'Low Stock Only'
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
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Grid -->
    <?php Pjax::begin(['id' => 'inventory-pjax']); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'summary' => '<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{begin}-{end} of {totalCount} stock items</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                      </div>',
        'columns' => [
            [
                'attribute' => 'product.sku',
                'label' => 'SKU',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'product.name',
                'label' => 'Product',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->product->name), ['/product/view', 'id' => $model->product->id], [
                        'class' => 'text-decoration-none fw-semibold'
                    ]);
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'location.name',
                'label' => 'Location',
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'attribute' => 'qty',
                'label' => 'Quantity',
                'value' => function ($model) {
                    $qty = number_format($model->qty, 3);
                    $isLowStock = $model->qty <= $model->product->reorder_level && $model->product->reorder_level > 0;
                    $class = $isLowStock ? 'text-danger fw-bold' : 'text-success';
                    
                    return Html::tag('span', $qty, ['class' => $class]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'wac',
                'label' => 'WAC',
                'format' => 'currency',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'label' => 'Stock Value',
                'value' => function ($model) {
                    return Yii::$app->formatter->asCurrency($model->getStockValue());
                },
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'attribute' => 'product.reorder_level',
                'label' => 'Reorder Level',
                'value' => function ($model) {
                    return number_format($model->product->reorder_level, 0);
                },
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'label' => 'Status',
                'value' => function ($model) {
                    if ($model->qty <= 0) {
                        return Html::tag('span', 'Out of Stock', ['class' => 'badge bg-danger']);
                    } elseif ($model->qty <= $model->product->reorder_level && $model->product->reorder_level > 0) {
                        return Html::tag('span', 'Low Stock', ['class' => 'badge bg-warning']);
                    } else {
                        return Html::tag('span', 'In Stock', ['class' => 'badge bg-success']);
                    }
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{adjust}',
                'buttons' => [
                    'adjust' => function ($url, $model) {
                        if (!Yii::$app->user->can('inventory.adjust')) {
                            return '';
                        }
                        return Html::button('<i class="fas fa-edit"></i>', [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Adjust Stock',
                            'data-bs-toggle' => 'tooltip',
                            'onclick' => "openAdjustModal({$model->product->id}, {$model->location->id}, '{$model->product->name}', '{$model->location->name}', {$model->qty})"
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<!-- Stock Adjustment Modal -->
<?php if (Yii::$app->user->can('inventory.adjust')): ?>
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adjustForm">
                    <input type="hidden" id="adjust_product_id" name="product_id">
                    <input type="hidden" id="adjust_location_id" name="location_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <div id="adjust_product_info" class="form-control-plaintext"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <div id="adjust_location_info" class="form-control-plaintext"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Stock</label>
                        <div id="adjust_current_stock" class="form-control-plaintext fw-bold"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adjustment Quantity</label>
                        <input type="number" 
                               id="adjustment_qty" 
                               name="adjustment_qty" 
                               class="form-control" 
                               step="0.001" 
                               placeholder="Enter positive or negative quantity"
                               required>
                        <div class="form-text">
                            Use positive numbers to increase stock, negative to decrease
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <select name="reason" class="form-select" required>
                            <option value="">Select Reason</option>
                            <option value="Manual Adjustment">Manual Adjustment</option>
                            <option value="Stock Count">Stock Count</option>
                            <option value="Damaged Goods">Damaged Goods</option>
                            <option value="Expired Items">Expired Items</option>
                            <option value="Lost Items">Lost Items</option>
                            <option value="Found Items">Found Items</option>
                            <option value="System Correction">System Correction</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this adjustment"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAdjustment()">Adjust Stock</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function inventoryIndex() {
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

function openAdjustModal(productId, locationId, productName, locationName, currentStock) {
    document.getElementById('adjust_product_id').value = productId;
    document.getElementById('adjust_location_id').value = locationId;
    document.getElementById('adjust_product_info').textContent = productName;
    document.getElementById('adjust_location_info').textContent = locationName;
    document.getElementById('adjust_current_stock').textContent = parseFloat(currentStock).toFixed(3);
    document.getElementById('adjustment_qty').value = '';
    document.querySelector('[name="reason"]').value = '';
    document.querySelector('[name="notes"]').value = '';
    
    new bootstrap.Modal(document.getElementById('adjustModal')).show();
}

function submitAdjustment() {
    const form = document.getElementById('adjustForm');
    const formData = new FormData(form);
    
    fetch('<?= Url::to(['adjust']) ?>', {
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
            bootstrap.Modal.getInstance(document.getElementById('adjustModal')).hide();
            location.reload();
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        showError('Adjustment failed: ' + error.message);
    });
}
</script>
