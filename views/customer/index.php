<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Customers';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="customerIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Manage your customers</p>
        </div>
        <div class="btn-group">
            <?= Html::a('<i class="fas fa-download me-1"></i> Export', ['export'], [
                'class' => 'btn btn-outline-secondary'
            ]) ?>
            
            <?php if (Yii::$app->user->can('customers.create')): ?>
                <?= Html::a('<i class="fas fa-plus me-1"></i> New Customer', ['create'], [
                    'class' => 'btn btn-primary'
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3" x-ref="searchForm">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search by name, phone, or email..."
                           value="<?= Html::encode(Yii::$app->request->get('search')) ?>"
                           x-on:input.debounce.500ms="submitSearch()">
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

    <!-- Customers Grid -->
    <?php Pjax::begin(['id' => 'customers-pjax']); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover'],
        'summary' => '<div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{begin}-{end} of {totalCount} customers</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                      </div>',
        'columns' => [
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
                'attribute' => 'phone',
                'format' => 'text',
                'headerOptions' => ['style' => 'width: 150px'],
            ],
            [
                'attribute' => 'email',
                'format' => 'email',
                'headerOptions' => ['style' => 'width: 200px'],
            ],
            [
                'attribute' => 'address',
                'format' => 'text',
                'value' => function ($model) {
                    return $model->address ? Html::encode(substr($model->address, 0, 50) . (strlen($model->address) > 50 ? '...' : '')) : '-';
                },
            ],
            [
                'label' => 'Total Sales',
                'value' => function ($model) {
                    return Yii::$app->formatter->asCurrency($model->getTotalSales());
                },
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'label' => 'Outstanding',
                'value' => function ($model) {
                    $balance = $model->getOutstandingBalance();
                    $class = $balance > 0 ? 'text-warning' : 'text-success';
                    return Html::tag('span', Yii::$app->formatter->asCurrency($balance), ['class' => $class]);
                },
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-end'],
            ],
            [
                'attribute' => 'created_at',
                'format' => 'date',
                'headerOptions' => ['style' => 'width: 100px'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'headerOptions' => ['style' => 'width: 120px'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function ($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-info me-1',
                            'title' => 'View',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'update' => function ($url, $model) {
                        if (!Yii::$app->user->can('customers.update')) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary me-1',
                            'title' => 'Update',
                            'data-bs-toggle' => 'tooltip'
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        if (!Yii::$app->user->can('customers.delete')) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Delete',
                            'data-bs-toggle' => 'tooltip',
                            'data-method' => 'post',
                            'data-confirm' => 'Are you sure you want to delete this customer?'
                        ]);
                    },
                ],
                'visibleButtons' => [
                    'update' => function ($model) {
                        return Yii::$app->user->can('customers.update');
                    },
                    'delete' => function ($model) {
                        return Yii::$app->user->can('customers.delete');
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>

<script>
function customerIndex() {
    return {
        init() {
            // Initialize tooltips
            const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltips.forEach(el => new bootstrap.Tooltip(el));
        },
        
        submitSearch() {
            this.$refs.searchForm.submit();
        }
    };
}
</script>
