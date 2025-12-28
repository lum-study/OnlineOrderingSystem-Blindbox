<?php
require_once __DIR__ . '/../../config/init.php';

class DashboardController
{
    private $db;
    public function __construct()
    {
        $this->db = new Database();
    }

    public function show() {
        require __DIR__ . '/../../views/pages/admin/dashboard.php';
    }

    /**
     * Get overall statistics
     */
    public function getStatistics()
    {
        return [
            'users' => $this->getUserStats(),
            'products' => $this->getProductStats(),
            'orders' => $this->getOrderStats(),
            'revenue' => $this->getRevenueStats(),
        ];
    }
    /**
     * Get user statistics
     */
    private function getUserStats()
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');

        $totalResult = $this->db->query("SELECT COUNT(*) as count FROM user_data WHERE is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $todayResult = $this->db->query("SELECT COUNT(*) as count FROM user_data WHERE DATE(created_date) = '$today' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $weekResult = $this->db->query("SELECT COUNT(*) as count FROM user_data WHERE created_date >= '$weekStart' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $monthResult = $this->db->query("SELECT COUNT(*) as count FROM user_data WHERE created_date >= '$monthStart' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => $totalResult['count'] ?? 0,
            'today' => $todayResult['count'] ?? 0,
            'this_week' => $weekResult['count'] ?? 0,
            'this_month' => $monthResult['count'] ?? 0,
        ];
    }
    /**
     * Get product statistics
     */
    private function getProductStats()
    {
        $totalResult = $this->db->query("SELECT COUNT(*) as count FROM blindbox WHERE is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $activeResult = $this->db->query("SELECT COUNT(*) as count FROM blindbox WHERE status = 'active' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $inactiveResult = $this->db->query("SELECT COUNT(*) as count FROM blindbox WHERE status = 'inactive' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $outOfStockResult = $this->db->query("SELECT COUNT(*) as count FROM blindbox WHERE stock_quantity = 0 AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $lowStockResult = $this->db->query("SELECT COUNT(*) as count FROM blindbox WHERE stock_quantity > 0 AND stock_quantity <= 20 AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => $totalResult['count'] ?? 0,
            'active' => $activeResult['count'] ?? 0,
            'inactive' => $inactiveResult['count'] ?? 0,
            'out_of_stock' => $outOfStockResult['count'] ?? 0,
            'low_stock' => $lowStockResult['count'] ?? 0,
        ];
    }
    /**
     * Get order statistics
     */
    private function getOrderStats()
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');

        $totalResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $todayResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_date) = '$today' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $weekResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE created_date >= '$weekStart' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $monthResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE created_date >= '$monthStart' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        $pendingResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $processingResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'processing' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $deliveredResult = $this->db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'delivered' AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => $totalResult['count'] ?? 0,
            'today' => $todayResult['count'] ?? 0,
            'this_week' => $weekResult['count'] ?? 0,
            'this_month' => $monthResult['count'] ?? 0,
            'pending' => $pendingResult['count'] ?? 0,
            'processing' => $processingResult['count'] ?? 0,
            'delivered' => $deliveredResult['count'] ?? 0,
        ];
    }
    /**
     * Get revenue statistics
     */
    private function getRevenueStats()
    {
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('-7 days'));
        $monthStart = date('Y-m-01');
        $yearStart = date('Y-01-01');

        $totalResult = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE status IN ('completed', 'paid', 'delivered') AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $todayResult = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE DATE(created_date) = '$today' AND status IN ('completed', 'paid', 'delivered') AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $weekResult = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE created_date >= '$weekStart' AND status IN ('completed', 'paid', 'delivered') AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $monthResult = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE created_date >= '$monthStart' AND status IN ('completed', 'paid', 'delivered') AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);
        $yearResult = $this->db->query("SELECT SUM(total_amount) as total FROM orders WHERE created_date >= '$yearStart' AND status IN ('completed', 'paid', 'delivered') AND is_deleted = 0")->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => $totalResult['total'] ?? 0,
            'today' => $todayResult['total'] ?? 0,
            'this_week' => $weekResult['total'] ?? 0,
            'this_month' => $monthResult['total'] ?? 0,
            'this_year' => $yearResult['total'] ?? 0,
            'currency' => '$',
        ];
    }

    /**
     * Get sales data for the last N days
     */
    public function getSalesChartData($days = 7)
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $result = $this->db->query(
                "SELECT 
                    DATE(created_date) as date,
                    SUM(total_amount) as revenue,
                    COUNT(*) as orders
                FROM orders 
                WHERE DATE(created_date) = '$date' AND is_deleted = 0 AND status IN ('completed', 'paid')
                GROUP BY DATE(created_date)"
            );
            $row = $result->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $data[] = [
                    'date' => $date,
                    'revenue' => $row['revenue'] ?? 0,
                    'orders' => $row['orders'] ?? 0,
                ];
            } else {
                $data[] = [
                    'date' => $date,
                    'revenue' => 0,
                    'orders' => 0,
                ];
            }
        }
        return $data;
    }

    /**
     * Get monthly sales data for current year
     */
    public function getMonthlySalesData()
    {
        $year = date('Y');
        $months = [];
        $monthNames = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];
        for ($month = 1; $month <= 12; $month++) {
            $monthStr = str_pad($month, 2, '0', STR_PAD_LEFT);
            $result = $this->db->query(
                "SELECT 
                    SUM(total_amount) as revenue,
                    COUNT(*) as orders
                FROM orders 
                WHERE YEAR(created_date) = $year 
                AND MONTH(created_date) = $month 
                AND is_deleted = 0 
                AND status IN ('completed', 'paid')"
            )->fetch(PDO::FETCH_ASSOC);

            $months[] = [
                'month' => $monthStr,
                'month_name' => $monthNames[$month - 1],
                'revenue' => $result['revenue'] ?? 0,
                'orders' => $result['orders'] ?? 0,
            ];
        }

        return $months;
    }
    /**
     * Get top selling products
     */
    public function getTopProducts($limit = 5)
    {
        $result = $this->db->query(
            "SELECT 
                b.blindbox_id,
                b.product_name as name,
                b.price,
                COALESCE(SUM(oi.quantity), 0) as total_sold,
                COALESCE(SUM(oi.subtotal), 0) as total_revenue
            FROM blindbox b
            LEFT JOIN order_items oi ON b.blindbox_id = oi.blindbox_id
            WHERE b.is_deleted = 0
            GROUP BY b.blindbox_id, b.product_name, b.price
            ORDER BY total_sold DESC
            LIMIT $limit"
        );

        return $result->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }
    /**
     * Get recent orders
     */
    public function getRecentOrders($limit = 5)
    {
        $result = $this->db->query(
            "SELECT 
                o.order_id,
                o.user_id,
                ud.fullname as customer_name,
                o.total_amount,
                o.status as order_status,
                o.created_date as created_at
            FROM orders o
            JOIN user_data ud ON o.user_id = ud.user_id
            WHERE o.is_deleted = 0
            ORDER BY o.created_date DESC
            LIMIT $limit"
        );

        return $result->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }
}
