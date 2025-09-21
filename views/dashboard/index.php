<?php

/** @var yii\web\View $this */
/** @var float $todaySales */
/** @var int $todaySalesCount */
/** @var float $monthSales */
/** @var float $monthPurchases */
/** @var array $lowStockProducts */
/** @var array $topProducts */
/** @var array $recentInvoices */
/** @var int $pendingInvoices */
/** @var float $outstandingAmount */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Dashboard';
?>

<div x-data="dashboardData()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted mb-0">Welcome back, <?= Html::encode(Yii::$app->user->identity->name) ?>!</p>
        </div>
        <div>
            <span class="text-muted"><?= date('l, F j, Y') ?></span>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-dollar-sign text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Today's Sales</div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asCurrency($todaySales) ?></div>
                            <div class="text-success small">
                                <i class="fas fa-receipt me-1"></i>
                                <?= $todaySalesCount ?> invoices
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-chart-line text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">This Month</div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asCurrency($monthSales) ?></div>
                            <div class="text-muted small">
                                Purchases: <?= Yii::$app->formatter->asCurrency($monthPurchases) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Low Stock Items</div>
                            <div class="h4 mb-0"><?= count($lowStockProducts) ?></div>
                            <div class="text-warning small">
                                <i class="fas fa-box me-1"></i>
                                Needs attention
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-clock text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Outstanding</div>
                            <div class="h4 mb-0"><?= Yii::$app->formatter->asCurrency($outstandingAmount) ?></div>
                            <div class="text-info small">
                                <i class="fas fa-file-invoice me-1"></i>
                                <?= $pendingInvoices ?> pending
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="row">
        <!-- Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">Sales Trend (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0">
                    <h5 class="card-title mb-0">Top Selling Products</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topProducts)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fs-1 mb-3"></i>
                            <p>No sales data available</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($topProducts as $index => $ledger): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?= Html::encode($ledger->product->name) ?></div>
                                            <div class="text-muted small"><?= Html::encode($ledger->product->sku) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold"><?= number_format($ledger->total_qty, 0) ?></div>
                                            <div class="text-muted small">sold</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity and Low Stock -->
    <div class="row">
        <!-- Recent Invoices -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Invoices</h5>
                    <?= Html::a('View All', ['/invoice/index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($recentInvoices)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-file-invoice fs-1 mb-3"></i>
                            <p>No recent invoices</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Type</th>
                                        <th>Party</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentInvoices as $invoice): ?>
                                        <tr>
                                            <td>
                                                <?= Html::a(
                                                    Html::encode($invoice->code),
                                                    ['/invoice/view', 'id' => $invoice->id],
                                                    ['class' => 'text-decoration-none']
                                                ) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $invoice->type === 'sale' ? 'success' : 'primary' ?>">
                                                    <?= ucfirst($invoice->type) ?>
                                                </span>
                                            </td>
                                            <td><?= Html::encode($invoice->getPartyName()) ?></td>
                                            <td><?= Yii::$app->formatter->asCurrency($invoice->total) ?></td>
                                            <td>
                                                <span class="badge status-<?= $invoice->status ?>">
                                                    <?= ucfirst($invoice->status) ?>
                                                </span>
                                            </td>
                                            <td><?= Yii::$app->formatter->asDate($invoice->created_at) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Low Stock Alert</h5>
                    <?= Html::a('View Inventory', ['/inventory/index'], ['class' => 'btn btn-sm btn-outline-warning']) ?>
                </div>
                <div class="card-body">
                    <?php if (empty($lowStockProducts)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fs-1 mb-3 text-success"></i>
                            <p>All products are well stocked!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($lowStockProducts, 0, 5) as $stockItem): ?>
                                <div class="list-group-item border-0 px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold"><?= Html::encode($stockItem->product->name) ?></div>
                                            <div class="text-muted small">
                                                <?= Html::encode($stockItem->product->sku) ?> • 
                                                <?= Html::encode($stockItem->location->name) ?>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold low-stock"><?= number_format($stockItem->qty, 0) ?></div>
                                            <div class="text-muted small">
                                                Min: <?= number_format($stockItem->product->reorder_level, 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($lowStockProducts) > 5): ?>
                                <div class="list-group-item border-0 px-0 text-center">
                                    <small class="text-muted">
                                        And <?= count($lowStockProducts) - 5 ?> more items...
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="quick-actions no-print">
    <?= Html::a('<i class="fas fa-plus"></i>', ['/invoice/create', 'type' => 'sale'], [
        'class' => 'btn btn-primary',
        'title' => 'New Sale Invoice',
        'data-bs-toggle' => 'tooltip'
    ]) ?>
</div>

<script>
function dashboardData() {
    return {
        salesChart: null,
        
        init() {
            this.loadSalesChart();
        },
        
        async loadSalesChart() {
            try {
                const response = await StockMS.ajax('<?= Url::to(['/dashboard/sales-chart']) ?>');
                
                if (response.success) {
                    this.renderSalesChart(response.data);
                }
            } catch (error) {
                console.error('Failed to load sales chart:', error);
            }
        },
        
        renderSalesChart(data) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            
            // Simple chart implementation (you can replace with Chart.js)
            const canvas = ctx.canvas;
            const width = canvas.width;
            const height = canvas.height;
            
            // Clear canvas
            ctx.clearRect(0, 0, width, height);
            
            if (data.length === 0) {
                ctx.fillStyle = '#6b7280';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No sales data available', width / 2, height / 2);
                return;
            }
            
            // Find max value for scaling
            const maxSales = Math.max(...data.map(d => d.sales));
            const padding = 40;
            const chartWidth = width - (padding * 2);
            const chartHeight = height - (padding * 2);
            
            // Draw axes
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            
            // Y-axis
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, height - padding);
            ctx.stroke();
            
            // X-axis
            ctx.beginPath();
            ctx.moveTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();
            
            // Draw line chart
            ctx.strokeStyle = '#4f46e5';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            data.forEach((point, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                const y = height - padding - (point.sales / maxSales) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Draw points
            ctx.fillStyle = '#4f46e5';
            data.forEach((point, index) => {
                const x = padding + (index / (data.length - 1)) * chartWidth;
                const y = height - padding - (point.sales / maxSales) * chartHeight;
                
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, 2 * Math.PI);
                ctx.fill();
            });
            
            // Add labels
            ctx.fillStyle = '#6b7280';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            
            // X-axis labels (show every 5th day)
            data.forEach((point, index) => {
                if (index % 5 === 0) {
                    const x = padding + (index / (data.length - 1)) * chartWidth;
                    const date = new Date(point.date);
                    const label = (date.getMonth() + 1) + '/' + date.getDate();
                    ctx.fillText(label, x, height - padding + 20);
                }
            });
            
            // Y-axis labels
            ctx.textAlign = 'right';
            for (let i = 0; i <= 4; i++) {
                const value = (maxSales / 4) * i;
                const y = height - padding - (i / 4) * chartHeight;
                ctx.fillText('$' + value.toFixed(0), padding - 10, y + 4);
            }
        }
    };
}
</script>
