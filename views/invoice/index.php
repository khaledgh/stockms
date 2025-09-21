<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $locations */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Invoice;

$this->title = 'Invoices';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="invoiceIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Manage sales and purchase invoices</p>
        </div>
        <div class="btn-group">
            <?php if (Yii::$app->user->can('invoices.create')): ?>
                <div class="btn-group">
                    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-plus me-1"></i> New Invoice
                    </button>
                    <ul class="dropdown-menu">
                        <li><?= Html::a('<i class="fas fa-shopping-cart me-2"></i> Sale Invoice', 
                            ['create', 'type' => Invoice::TYPE_SALE], ['class' => 'dropdown-item']) ?></li>
                        <li><?= Html::a('<i class="fas fa-truck me-2"></i> Purchase Invoice', 
                            ['create', 'type' => Invoice::TYPE_PURCHASE], ['class' => 'dropdown-item']) ?></li>
                    </ul>
                </div>
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
                    <label class="form-label">Type</label>
                    <?= Html::dropDownList('type', Yii::$app->request->get('type'), [
                        '' => 'All Types',
                        Invoice::TYPE_SALE => 'Sale',
                        Invoice::TYPE_PURCHASE => 'Purchase'
                    ], [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                        '' => 'All Status',
                        Invoice::STATUS_DRAFT => 'Draft',
                        Invoice::STATUS_CONFIRMED => 'Confirmed',
                        Invoice::STATUS_VOID => 'Void'
                    ], [
                        'class' => 'form-select',
                        'x-on:change' => 'submitFilters()'
                    ]) ?>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <?= Html::dropDownList('location_id', Yii::$app->request->get('location_id'), 
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

    <!-- Invoices Grid -->
    <?php Pjax::begin(['id' => 'invoices-pjax']); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'summary' => '<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{begin}-{end} of {totalCount} invoices</span>
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
                'attribute' => 'type',
                'value' => function ($model) {
                    $class = $model->type === Invoice::TYPE_SALE ? 'bg-success' : 'bg-primary';
                    return Html::tag('span', ucfirst($model->type), ['class' => "badge {$class}"]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 80px'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'label' => 'Party',
                'value' => function ($model) {
                    return $model->getPartyName();
                },
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'attribute' => 'location_id',
                'label' => 'Location',
                'value' => function ($model) {
                    return $model->location ? $model->location->name : '-';
                },
                'headerOptions' => ['style' => 'width: 120px'],
            ],
            [
                'attribute' => 'total',
                'format' => 'currency',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'label' => 'Balance',
                'value' => function ($model) {
                    $balance = $model->getBalance();
                    $class = $balance > 0 ? 'text-warning' : 'text-success';
                    return Html::tag('span', Yii::$app->formatter->asCurrency($balance), ['class' => $class]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 100px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    $badges = [
                        Invoice::STATUS_DRAFT => 'bg-secondary',
                        Invoice::STATUS_CONFIRMED => 'bg-success',
                        Invoice::STATUS_VOID => 'bg-danger',
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
                'headerOptions' => ['style' => 'width: 150px'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{view} {update} {confirm} {print} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info me-1',
                            'title' => 'View',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'update' => function ($url, $model) {
                        if (!Yii::$app->user->can('invoices.update') || !$model->isEditable()) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary me-1',
                            'title' => 'Update',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'confirm' => function ($url, $model) {
                        if (!Yii::$app->user->can('invoices.confirm') || $model->status !== Invoice::STATUS_DRAFT) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-check"></i>', ['confirm', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-success me-1',
                            'title' => 'Confirm',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to confirm this invoice?'
                        ]);
                    },
                    'print' => function ($url, $model) {
                        if (!Yii::$app->user->can('invoices.print')) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-print"></i>', ['print', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-secondary me-1',
                            'title' => 'Print',
                            'data-bs-toggle' => 'tooltip',
                            'target' => '_blank'
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if (!Yii::$app->user->can('invoices.delete') || $model->status === Invoice::STATUS_CONFIRMED) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Delete',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to delete this invoice?'
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<script>
function invoiceIndex() {
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
