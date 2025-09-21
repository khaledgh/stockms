<?php

/** @var yii\web\View $this */
/** @var string $content */

use app\assets\AppAsset;
use yii\bootstrap5\Html;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerMetaTag(['name' => 'keywords', 'content' => $this->params['meta_keywords'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #3730a3;
            --secondary-color: #6b7280;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .sidebar {
            background: white;
            box-shadow: var(--card-shadow);
            border-radius: 0.75rem;
            padding: 1.5rem;
            height: fit-content;
        }
        
        .sidebar .nav-link {
            color: var(--secondary-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 0.75rem;
        }
        
        .main-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            min-height: 600px;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
        }
        
        .btn {
            border-radius: 0.5rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .table {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table thead th {
            background-color: var(--light-bg);
            border: none;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .badge {
            font-weight: 500;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        /* Loading states */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Skeleton loader */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Toast notifications */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
        }
    </style>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<!-- Toast Container -->
<div class="toast-container" x-data="toastStore()">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast show" role="alert" x-show="toast.show" x-transition>
            <div class="toast-header">
                <strong class="me-auto" x-text="toast.title"></strong>
                <button type="button" class="btn-close" @click="removeToast(toast.id)"></button>
            </div>
            <div class="toast-body" x-text="toast.message"></div>
        </div>
    </template>
</div>

<!-- Navigation -->
<?php
NavBar::begin([
    'brandLabel' => Html::img('@web/images/logo.png', ['alt' => 'Logo', 'style' => 'height: 30px']) . ' Stock MS',
    'brandUrl' => Yii::$app->homeUrl,
    'options' => [
        'class' => 'navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4',
    ],
]);

$menuItems = [];

if (Yii::$app->user->isGuest) {
    $menuItems[] = ['label' => 'Login', 'url' => ['/site/login']];
} else {
    $menuItems[] = [
        'label' => 'Logout (' . Yii::$app->user->identity->name . ')',
        'url' => ['/site/logout'],
        'linkOptions' => ['data-method' => 'post']
    ];
}

echo Nav::widget([
    'options' => ['class' => 'navbar-nav ms-auto'],
    'items' => $menuItems,
]);

NavBar::end();
?>

<main class="flex-shrink-0">
    <div class="container-fluid">
        <?php if (!Yii::$app->user->isGuest): ?>
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 mb-4">
                <div class="sidebar">
                    <?= Nav::widget([
                        'options' => ['class' => 'nav flex-column'],
                        'items' => [
                            [
                                'label' => '<i class="fas fa-tachometer-alt"></i> Dashboard',
                                'url' => ['/dashboard/index'],
                                'encode' => false,
                            ],
                            [
                                'label' => '<i class="fas fa-box"></i> Products',
                                'url' => ['/product/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('products.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-warehouse"></i> Inventory',
                                'url' => ['/inventory/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('inventory.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-exchange-alt"></i> Transfers',
                                'url' => ['/transfer/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('inventory.transfer'),
                            ],
                            [
                                'label' => '<i class="fas fa-file-invoice"></i> Invoices',
                                'url' => ['/invoice/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('invoices.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-users"></i> Customers',
                                'url' => ['/customer/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('customers.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-truck"></i> Vendors',
                                'url' => ['/vendor/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('vendors.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-chart-bar"></i> Reports',
                                'url' => ['/report/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('reports.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-print"></i> Templates',
                                'url' => ['/template/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('templates.view'),
                            ],
                            [
                                'label' => '<i class="fas fa-cog"></i> Settings',
                                'url' => ['/setting/index'],
                                'encode' => false,
                                'visible' => Yii::$app->user->can('settings.view'),
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10 col-md-9">
                <div class="main-content">
                    <?= $content ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <?= $content ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Alpine.js Global Store -->
<script>
document.addEventListener('alpine:init', () => {
    // Toast notification store
    Alpine.store('toastStore', () => ({
        toasts: [],
        nextId: 1,
        
        addToast(title, message, type = 'info') {
            const toast = {
                id: this.nextId++,
                title: title,
                message: message,
                type: type,
                show: true
            };
            
            this.toasts.push(toast);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                this.removeToast(toast.id);
            }, 5000);
        },
        
        removeToast(id) {
            const index = this.toasts.findIndex(t => t.id === id);
            if (index > -1) {
                this.toasts[index].show = false;
                setTimeout(() => {
                    this.toasts.splice(index, 1);
                }, 300);
            }
        }
    }));
    
    // Global functions
    window.showToast = (title, message, type = 'info') => {
        Alpine.store('toastStore').addToast(title, message, type);
    };
    
    window.showSuccess = (message) => {
        showToast('Success', message, 'success');
    };
    
    window.showError = (message) => {
        showToast('Error', message, 'error');
    };
    
    window.showWarning = (message) => {
        showToast('Warning', message, 'warning');
    };
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Global search: /
    if (e.key === '/' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        const searchInput = document.querySelector('#global-search');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // New sale invoice: +
    if (e.key === '+' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        window.location.href = '/invoice/create?type=sale';
    }
    
    // New transfer: t
    if (e.key === 't' && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        window.location.href = '/transfer/create';
    }
});
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
