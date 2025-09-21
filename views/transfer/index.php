<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $locations */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Transfer;

$this->title = 'Stock Transfers';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="transferIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Move stock between locations</p>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="fas fa-download me-1"></i> Export', ['export'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
            
            <?php if (Yii::$app->user->can('inventory.transfer')): ?>
                <?= Html::a('<i class="fas fa-plus me-1"></i> New Transfer', ['create'], [
                    'class' => 'btn btn-primary'
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3" x-ref="filterForm">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by code or notes..."
                           value="<?= Html::encode(Yii::$app->request->get('search')) ?>"
                           x-on:input.debounce.500ms="submitFilters()">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                        '' => 'All Status',
                        Transfer::STATUS_DRAFT => 'Draft',
                        Transfer::STATUS_CONFIRMED => 'Confirmed'
                    ], [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">From Location</label>
                    <?= Html::dropDownList('from_location_id', Yii::$app->request->get('from_location_id'), 
                        ['' => 'All Locations'] + $locations, [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">To Location</label>
                    <?= Html::dropDownList('to_location_id', Yii::$app->request->get('to_location_id'), 
                        ['' => 'All Locations'] + $locations, [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">From</label>
                    <input type="date" 
                           name="date_from" 
                           class="form-control" 
                           value="<?= Html::encode(Yii::$app->request->get('date_from')) ?>"
                           x-on:change="submitFilters()">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">To</label>
                    <input type="date" 
                           name="date_to" 
                           class="form-control" 
                           value="<?= Html::encode(Yii::$app->request->get('date_to')) ?>"
                           x-on:change="submitFilters()">
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <?= Html::a('Clear', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transfers Grid -->
    <?php Pjax::begin(['id' => 'transfers-pjax']); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'summary' => '<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{begin}-{end} of {totalCount} transfers</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                      </div>',
        'columns' => [
            [
                'attribute' => 'code',
                'format' => 'text',
                'value' => function ($model) {
                    return Html::a(Html::encode($model->code), ['view', 'id' => $model->id], [
                        'class' => 'text-decoration-none fw-semibold'
                    ]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'from_location_id',
                'label' => 'From',
                'value' => function ($model) {
                    return $model->fromLocation ? $model->fromLocation->name : '-';
                },
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'label' => 'Direction',
                'value' => function ($model) {
                    return Html::tag('i', '', ['class' => 'fas fa-arrow-right text-primary']);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 50px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'to_location_id',
                'label' => 'To',
                'value' => function ($model) {
                    return $model->toLocation ? $model->toLocation->name : '-';
                },
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'label' => 'Items',
                'value' => function ($model) {
                    return count($model->transferLines);
                },
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'label' => 'Total Qty',
                'value' => function ($model) {
                    return number_format($model->getTotalQty(), 3);
                },
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    $badges = [
                        Transfer::STATUS_DRAFT => 'bg-secondary',
                        Transfer::STATUS_CONFIRMED => 'bg-success',
                    ];
                    $class = $badges[$model->status] ?? 'bg-secondary';
                    return Html::tag('span', ucfirst($model->status), ['class' => "badge {$class}"]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'created_at',
                'format' => 'datetime',
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{view} {update} {confirm} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info me-1',
                            'title' => 'View',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'update' => function ($url, $model) {
                        if (!Yii::$app->user->can('inventory.transfer') || !$model->isEditable()) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary me-1',
                            'title' => 'Update',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'confirm' => function ($url, $model) {
                        if (!Yii::$app->user->can('inventory.transfer') || $model->status !== Transfer::STATUS_DRAFT) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-check"></i>', ['confirm', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-success me-1',
                            'title' => 'Confirm Transfer',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to confirm this transfer? This will move the stock between locations.'
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if (!Yii::$app->user->can('inventory.transfer') || $model->status === Transfer::STATUS_CONFIRMED) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Delete',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to delete this transfer?'
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<script>
function transferIndex() {
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
</script>
