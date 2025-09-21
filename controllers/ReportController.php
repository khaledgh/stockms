<?php

namespace app\controllers;

use Yii;
use app\models\Invoice;
use app\models\InvoiceLine;
use app\models\StockLedger;
use app\models\Product;
use app\models\Location;
use app\models\Customer;
use app\models\Vendor;
use yii\data\ActiveDataProvider;
use yii\web\Response;

/**
 * ReportController handles various business reports
 */
class ReportController extends BaseController
{
    /**
     * Reports dashboard
     */
    public function actionIndex()
    {
        $this->checkPermission('reports.view');
        
        return $this->render('index');
    }

    /**
     * Sales report
     */
    public function actionSales()
    {
        $this->checkPermission('reports.view');
        
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $locationId = Yii::$app->request->get('location_id');
        $customerId = Yii::$app->request->get('customer_id');
        
        $query = Invoice::find()
            ->with(['location', 'customer', 'createdBy'])
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ]);
            
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }
        
        if ($customerId) {
            $query->andWhere(['customer_id' => $customerId]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['confirmed_at' => SORT_DESC]],
        ]);
        
        // Calculate totals
        $totals = $query->select([
            'COUNT(*) as invoice_count',
            'SUM(sub_total) as total_subtotal',
            'SUM(discount) as total_discount', 
            'SUM(tax) as total_tax',
            'SUM(total) as total_amount',
            'SUM(paid) as total_paid'
        ])->asArray()->one();
        
        return $this->render('sales', [
            'dataProvider' => $dataProvider,
            'totals' => $totals,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locations' => Location::getDropdownOptions(),
            'customers' => Customer::getDropdownOptions(),
        ]);
    }

    /**
     * Purchase report
     */
    public function actionPurchases()
    {
        $this->checkPermission('reports.view');
        
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $locationId = Yii::$app->request->get('location_id');
        $vendorId = Yii::$app->request->get('vendor_id');
        
        $query = Invoice::find()
            ->with(['location', 'vendor', 'createdBy'])
            ->where(['type' => Invoice::TYPE_PURCHASE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ]);
            
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }
        
        if ($vendorId) {
            $query->andWhere(['vendor_id' => $vendorId]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
            'sort' => ['defaultOrder' => ['confirmed_at' => SORT_DESC]],
        ]);
        
        // Calculate totals
        $totals = $query->select([
            'COUNT(*) as invoice_count',
            'SUM(sub_total) as total_subtotal',
            'SUM(discount) as total_discount', 
            'SUM(tax) as total_tax',
            'SUM(total) as total_amount',
            'SUM(paid) as total_paid'
        ])->asArray()->one();
        
        return $this->render('purchases', [
            'dataProvider' => $dataProvider,
            'totals' => $totals,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locations' => Location::getDropdownOptions(),
            'vendors' => Vendor::getDropdownOptions(),
        ]);
    }

    /**
     * Profit & Loss report
     */
    public function actionProfitLoss()
    {
        $this->checkPermission('reports.view');
        
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $locationId = Yii::$app->request->get('location_id');
        
        $fromTimestamp = strtotime($dateFrom . ' 00:00:00');
        $toTimestamp = strtotime($dateTo . ' 23:59:59');
        
        // Sales Revenue
        $salesQuery = Invoice::find()
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', $fromTimestamp, $toTimestamp]);
            
        if ($locationId) {
            $salesQuery->andWhere(['location_id' => $locationId]);
        }
        
        $salesData = $salesQuery->select([
            'SUM(sub_total) as revenue',
            'SUM(discount) as discounts',
            'SUM(tax) as tax_collected'
        ])->asArray()->one();
        
        // Cost of Goods Sold (COGS)
        $cogsQuery = InvoiceLine::find()
            ->joinWith(['invoice'])
            ->where(['invoice.type' => Invoice::TYPE_SALE, 'invoice.status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'invoice.confirmed_at', $fromTimestamp, $toTimestamp]);
            
        if ($locationId) {
            $cogsQuery->andWhere(['invoice.location_id' => $locationId]);
        }
        
        $cogs = $cogsQuery->sum('invoice_line.qty * invoice_line.cost_price') ?: 0;
        
        // Purchase Expenses
        $purchaseQuery = Invoice::find()
            ->where(['type' => Invoice::TYPE_PURCHASE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', $fromTimestamp, $toTimestamp]);
            
        if ($locationId) {
            $purchaseQuery->andWhere(['location_id' => $locationId]);
        }
        
        $purchaseData = $purchaseQuery->select([
            'SUM(total) as total_purchases'
        ])->asArray()->one();
        
        // Calculate P&L
        $revenue = floatval($salesData['revenue'] ?? 0);
        $discounts = floatval($salesData['discounts'] ?? 0);
        $taxCollected = floatval($salesData['tax_collected'] ?? 0);
        $totalPurchases = floatval($purchaseData['total_purchases'] ?? 0);
        
        $netRevenue = $revenue - $discounts;
        $grossProfit = $netRevenue - $cogs;
        $netProfit = $grossProfit; // Simplified - in real scenario, subtract operating expenses
        
        $profitLossData = [
            'revenue' => $revenue,
            'discounts' => $discounts,
            'net_revenue' => $netRevenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0,
            'total_purchases' => $totalPurchases,
            'tax_collected' => $taxCollected,
            'net_profit' => $netProfit,
            'net_margin' => $netRevenue > 0 ? ($netProfit / $netRevenue) * 100 : 0,
        ];
        
        return $this->render('profit-loss', [
            'data' => $profitLossData,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Top selling products report
     */
    public function actionTopProducts()
    {
        $this->checkPermission('reports.view');
        
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $locationId = Yii::$app->request->get('location_id');
        $limit = Yii::$app->request->get('limit', 20);
        
        $query = StockLedger::find()
            ->select([
                'product_id',
                'SUM(ABS(qty)) as total_qty',
                'SUM(ABS(qty * unit_price)) as total_revenue'
            ])
            ->with(['product'])
            ->where(['reason' => StockLedger::REASON_SALE])
            ->andWhere(['between', 'moved_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ])
            ->groupBy('product_id')
            ->orderBy(['total_qty' => SORT_DESC])
            ->limit($limit);
            
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,
        ]);
        
        return $this->render('top-products', [
            'dataProvider' => $dataProvider,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Customer sales report
     */
    public function actionCustomerSales()
    {
        $this->checkPermission('reports.view');
        
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        $locationId = Yii::$app->request->get('location_id');
        
        $query = Invoice::find()
            ->select([
                'customer_id',
                'COUNT(*) as invoice_count',
                'SUM(total) as total_sales',
                'SUM(paid) as total_paid',
                'SUM(total - paid) as outstanding'
            ])
            ->with(['customer'])
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ])
            ->andWhere(['is not', 'customer_id', null])
            ->groupBy('customer_id')
            ->orderBy(['total_sales' => SORT_DESC]);
            
        if ($locationId) {
            $query->andWhere(['location_id' => $locationId]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);
        
        return $this->render('customer-sales', [
            'dataProvider' => $dataProvider,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'locations' => Location::getDropdownOptions(),
        ]);
    }

    /**
     * Export report to CSV
     */
    public function actionExport()
    {
        $this->checkPermission('reports.export');
        
        $reportType = Yii::$app->request->get('type');
        $dateFrom = Yii::$app->request->get('date_from', date('Y-m-01'));
        $dateTo = Yii::$app->request->get('date_to', date('Y-m-d'));
        
        switch ($reportType) {
            case 'sales':
                return $this->exportSales($dateFrom, $dateTo);
            case 'purchases':
                return $this->exportPurchases($dateFrom, $dateTo);
            case 'profit-loss':
                return $this->exportProfitLoss($dateFrom, $dateTo);
            case 'top-products':
                return $this->exportTopProducts($dateFrom, $dateTo);
            default:
                $this->setFlash('error', 'Invalid report type.');
                return $this->redirect(['index']);
        }
    }

    /**
     * Export sales report to CSV
     */
    private function exportSales($dateFrom, $dateTo)
    {
        $query = Invoice::find()
            ->with(['location', 'customer', 'createdBy'])
            ->where(['type' => Invoice::TYPE_SALE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ])
            ->orderBy(['confirmed_at' => SORT_DESC]);

        $filename = 'sales_report_' . $dateFrom . '_to_' . $dateTo . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Invoice Code',
            'Date',
            'Customer',
            'Location',
            'Subtotal',
            'Discount',
            'Tax',
            'Total',
            'Paid',
            'Balance',
            'Created By'
        ]);
        
        foreach ($query->batch(100) as $invoices) {
            foreach ($invoices as $invoice) {
                fputcsv($output, [
                    $invoice->code,
                    date('Y-m-d H:i:s', $invoice->confirmed_at),
                    $invoice->customer ? $invoice->customer->name : 'Walk-in Customer',
                    $invoice->location->name,
                    $invoice->sub_total,
                    $invoice->discount,
                    $invoice->tax,
                    $invoice->total,
                    $invoice->paid,
                    $invoice->getBalance(),
                    $invoice->createdBy ? $invoice->createdBy->name : ''
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export purchases report to CSV
     */
    private function exportPurchases($dateFrom, $dateTo)
    {
        $query = Invoice::find()
            ->with(['location', 'vendor', 'createdBy'])
            ->where(['type' => Invoice::TYPE_PURCHASE, 'status' => Invoice::STATUS_CONFIRMED])
            ->andWhere(['between', 'confirmed_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ])
            ->orderBy(['confirmed_at' => SORT_DESC]);

        $filename = 'purchases_report_' . $dateFrom . '_to_' . $dateTo . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Invoice Code',
            'Date',
            'Vendor',
            'Location',
            'Subtotal',
            'Discount',
            'Tax',
            'Total',
            'Paid',
            'Balance',
            'Created By'
        ]);
        
        foreach ($query->batch(100) as $invoices) {
            foreach ($invoices as $invoice) {
                fputcsv($output, [
                    $invoice->code,
                    date('Y-m-d H:i:s', $invoice->confirmed_at),
                    $invoice->vendor ? $invoice->vendor->name : '',
                    $invoice->location->name,
                    $invoice->sub_total,
                    $invoice->discount,
                    $invoice->tax,
                    $invoice->total,
                    $invoice->paid,
                    $invoice->getBalance(),
                    $invoice->createdBy ? $invoice->createdBy->name : ''
                ]);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export P&L report to CSV
     */
    private function exportProfitLoss($dateFrom, $dateTo)
    {
        // This would generate a P&L summary CSV
        $filename = 'profit_loss_' . $dateFrom . '_to_' . $dateTo . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // This is a simplified version - you would calculate actual P&L data here
        fputcsv($output, ['Profit & Loss Report', $dateFrom . ' to ' . $dateTo]);
        fputcsv($output, ['Item', 'Amount']);
        fputcsv($output, ['Revenue', '0.00']);
        fputcsv($output, ['COGS', '0.00']);
        fputcsv($output, ['Gross Profit', '0.00']);
        
        fclose($output);
        exit;
    }

    /**
     * Export top products report to CSV
     */
    private function exportTopProducts($dateFrom, $dateTo)
    {
        $query = StockLedger::find()
            ->select([
                'product_id',
                'SUM(ABS(qty)) as total_qty',
                'SUM(ABS(qty * unit_price)) as total_revenue'
            ])
            ->with(['product'])
            ->where(['reason' => StockLedger::REASON_SALE])
            ->andWhere(['between', 'moved_at', 
                strtotime($dateFrom . ' 00:00:00'), 
                strtotime($dateTo . ' 23:59:59')
            ])
            ->groupBy('product_id')
            ->orderBy(['total_qty' => SORT_DESC])
            ->limit(100);

        $filename = 'top_products_' . $dateFrom . '_to_' . $dateTo . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, [
            'Rank',
            'SKU',
            'Product Name',
            'Quantity Sold',
            'Revenue'
        ]);
        
        $rank = 1;
        foreach ($query->all() as $item) {
            fputcsv($output, [
                $rank++,
                $item->product->sku,
                $item->product->name,
                $item->total_qty,
                $item->total_revenue
            ]);
        }
        
        fclose($output);
        exit;
    }
}
