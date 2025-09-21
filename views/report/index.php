<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Reports';
$this->params['breadcrumbs'][] = $this->title;
?>

<div x-data="reportsIndex()" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted mb-0">Business intelligence and analytics</p>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-chart-line text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Today's Sales</div>
                            <div class="h4 mb-0" x-text="'$' + todaySales.toFixed(2)">$0.00</div>
                            <div class="text-success small">
                                <span x-text="todayInvoices"></span> invoices
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-calendar-alt text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">This Month</div>
                            <div class="h4 mb-0" x-text="'$' + monthSales.toFixed(2)">$0.00</div>
                            <div class="text-primary small">
                                <span x-text="monthInvoices"></span> invoices
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-percentage text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Gross Margin</div>
                            <div class="h4 mb-0" x-text="grossMargin.toFixed(1) + '%'">0.0%</div>
                            <div class="text-info small">This month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="fas fa-exclamation-triangle text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Outstanding</div>
                            <div class="h4 mb-0" x-text="'$' + outstanding.toFixed(2)">$0.00</div>
                            <div class="text-warning small">Unpaid invoices</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="row">
        <!-- Sales Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-cart text-success me-2"></i>
                        Sales Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= Url::to(['sales']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Sales Summary</h6>
                                    <p class="mb-1 text-muted small">Detailed sales report with filters</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['customer-sales']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Customer Sales</h6>
                                    <p class="mb-1 text-muted small">Sales performance by customer</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['top-products']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Top Selling Products</h6>
                                    <p class="mb-1 text-muted small">Best performing products by quantity and revenue</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-truck text-primary me-2"></i>
                        Purchase Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= Url::to(['purchases']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Purchase Summary</h6>
                                    <p class="mb-1 text-muted small">Detailed purchase report with filters</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['purchases', 'group_by' => 'vendor']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Vendor Analysis</h6>
                                    <p class="mb-1 text-muted small">Purchase performance by vendor</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie text-info me-2"></i>
                        Financial Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= Url::to(['profit-loss']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Profit & Loss</h6>
                                    <p class="mb-1 text-muted small">Revenue, COGS, and profitability analysis</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['/invoice/index', 'status' => 'confirmed', 'balance' => 'outstanding']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Outstanding Payments</h6>
                                    <p class="mb-1 text-muted small">Unpaid invoices and receivables</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inventory Reports -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-warehouse text-warning me-2"></i>
                        Inventory Reports
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= Url::to(['/inventory/valuation']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Stock Valuation</h6>
                                    <p class="mb-1 text-muted small">Current inventory value by location</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['/inventory/low-stock']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Low Stock Alert</h6>
                                    <p class="mb-1 text-muted small">Products below reorder level</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                        
                        <a href="<?= Url::to(['/inventory/ledger']) ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Stock Movement</h6>
                                    <p class="mb-1 text-muted small">Complete stock ledger with all movements</p>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Export Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0">
            <h5 class="card-title mb-0">
                <i class="fas fa-download text-secondary me-2"></i>
                Quick Exports
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-2">
                    <input type="date" class="form-control" x-model="exportDateFrom" :max="exportDateTo">
                </div>
                <div class="col-md-3 mb-2">
                    <input type="date" class="form-control" x-model="exportDateTo" :min="exportDateFrom">
                </div>
                <div class="col-md-6 mb-2">
                    <div class="btn-group w-100">
                        <button type="button" class="btn btn-outline-secondary" x-on:click="exportReport('sales')">
                            <i class="fas fa-shopping-cart me-1"></i> Sales CSV
                        </button>
                        <button type="button" class="btn btn-outline-secondary" x-on:click="exportReport('purchases')">
                            <i class="fas fa-truck me-1"></i> Purchases CSV
                        </button>
                        <button type="button" class="btn btn-outline-secondary" x-on:click="exportReport('profit-loss')">
                            <i class="fas fa-chart-pie me-1"></i> P&L CSV
                        </button>
                        <button type="button" class="btn btn-outline-secondary" x-on:click="exportReport('top-products')">
                            <i class="fas fa-star me-1"></i> Top Products CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function reportsIndex() {
    return {
        todaySales: 0,
        todayInvoices: 0,
        monthSales: 0,
        monthInvoices: 0,
        grossMargin: 0,
        outstanding: 0,
        exportDateFrom: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
        exportDateTo: new Date().toISOString().split('T')[0],
        
        init() {
            this.loadQuickStats();
        },
        
        async loadQuickStats() {
            try {
                // In a real implementation, you would fetch this data from the server
                // For now, we'll use placeholder data
                this.todaySales = 1250.00;
                this.todayInvoices = 15;
                this.monthSales = 28750.00;
                this.monthInvoices = 342;
                this.grossMargin = 35.2;
                this.outstanding = 4250.00;
            } catch (error) {
                console.error('Failed to load quick stats:', error);
            }
        },
        
        exportReport(type) {
            const params = new URLSearchParams({
                type: type,
                date_from: this.exportDateFrom,
                date_to: this.exportDateTo
            });
            
            window.open(`<?= Url::to(['export']) ?>?${params.toString()}`, '_blank');
        }
    };
}
</script>
