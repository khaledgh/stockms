<?php

namespace app\controllers;

use Yii;
use app\models\Invoice;
use app\models\Product;
use app\models\StockItem;
use app\models\StockLedger;

/**
 * Dashboard controller
 */
class DashboardController extends BaseController
{
    /**
     * Dashboard index
     */
    public function actionIndex()
    {
        $this->checkPermission('dashboard.view');
        
        // Today's stats
        $todayStart = strtotime('today');
        $todayEnd = strtotime('tomorrow') - 1;
        
        // Sales stats
        $todaySales = Invoice::find()
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', date('Y-m-d 00:00:00', $todayStart), date('Y-m-d 23:59:59', $todayEnd)])
            ->sum('total') ?: 0;
            
        $todaySalesCount = Invoice::find()
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', date('Y-m-d 00:00:00', $todayStart), date('Y-m-d 23:59:59', $todayEnd)])
            ->count();
        
        // This month's stats
        $monthStart = strtotime('first day of this month');
        $monthEnd = strtotime('last day of this month 23:59:59');
        
        $monthSales = Invoice::find()
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', date('Y-m-d 00:00:00', $monthStart), date('Y-m-d H:i:s', $monthEnd)])
            ->sum('total') ?: 0;
            
        $monthPurchases = Invoice::find()
            ->where(['type' => Invoice::TYPE_PURCHASE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', date('Y-m-d 00:00:00', $monthStart), date('Y-m-d H:i:s', $monthEnd)])
            ->sum('total') ?: 0;
        
        // Low stock products
        $lowStockProducts = StockItem::getLowStockItems();
        
        // Top selling products (this month)
        $topProducts = StockLedger::find()
            ->select(['product_id', 'SUM(ABS(qty)) as total_qty'])
            ->where(['reason' => StockLedger::REASON_SALE])
            ->andWhere(['between', 'moved_at', date('Y-m-d 00:00:00', $monthStart), date('Y-m-d H:i:s', $monthEnd)])
            ->groupBy('product_id')
            ->orderBy(['total_qty' => SORT_DESC])
            ->limit(5)
            ->with('product')
            ->all();
        
        // Recent invoices
        $recentInvoices = Invoice::find()
            ->with(['customer', 'vendor', 'location'])
            ->where(['is', 'deleted_at', null])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(10)
            ->all();
        
        // Pending invoices (draft status)
        $pendingInvoices = Invoice::find()
            ->where(['status' => Invoice::STATUS_DRAFT])
            ->andWhere(['is', 'deleted_at', null])
            ->count();
        
        // Outstanding payments
        $outstandingAmount = Invoice::find()
            ->where(['status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['>', 'total - paid', 0])
            ->andWhere(['is', 'deleted_at', null])
            ->sum('total - paid') ?: 0;
        
        return $this->render('index', [
            'todaySales' => $todaySales,
            'todaySalesCount' => $todaySalesCount,
            'monthSales' => $monthSales,
            'monthPurchases' => $monthPurchases,
            'lowStockProducts' => $lowStockProducts,
            'topProducts' => $topProducts,
            'recentInvoices' => $recentInvoices,
            'pendingInvoices' => $pendingInvoices,
            'outstandingAmount' => $outstandingAmount,
        ]);
    }
    
    /**
     * Get sales chart data
     */
    public function actionSalesChart()
    {
        $this->checkPermission('dashboard.view');
        
        $days = 30;
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dateStart = $date . ' 00:00:00';
            $dateEnd = $date . ' 23:59:59';
            
            $sales = Invoice::find()
                ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
                ->andWhere(['between', 'confirmed_at', $dateStart, $dateEnd])
                ->sum('total') ?: 0;
                
            $data[] = [
                'date' => $date,
                'sales' => (float) $sales,
            ];
        }
        
        return $this->asJson([
            'success' => true,
            'data' => $data,
        ]);
    }
}
