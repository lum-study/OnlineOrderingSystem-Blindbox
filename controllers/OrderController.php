<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../models/Order.php';

class OrderController
{
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    // Get user orders with item count and review count
    public function getUserOrders($user_id)
    {
        $sql = "SELECT o.*, 
                (SELECT SUM(quantity) FROM order_items WHERE order_id = o.order_id) as item_count,
                (SELECT COUNT(*) FROM blindbox_reviews r JOIN order_items oi ON r.order_item_id = oi.order_item_id WHERE oi.order_id = o.order_id AND r.is_deleted = 0) as review_count
                FROM orders o 
                WHERE o.user_id = ? AND o.is_deleted = 0 
                ORDER BY 
                    CASE status
                        WHEN 'to_pay' THEN 1
                        WHEN 'pending' THEN 2
                        WHEN 'shipped' THEN 3
                        WHEN 'completed' THEN 4
                        WHEN 'cancelled' THEN 5
                        ELSE 6
                    END,
                    o.created_date DESC";
        return Database::fetchAll($sql, [$user_id]);
    }

    // Get user order details
    public function getUserOrderDetails($order_id, $user_id)
    {
        $sql = "SELECT o.* FROM orders o WHERE o.order_id = ? AND o.user_id = ? AND o.is_deleted = 0";
        $order = Database::fetch($sql, [$order_id, $user_id]);

        if ($order) {
            $sql = "SELECT oi.*, b.product_name 
                    FROM order_items oi 
                    LEFT JOIN blindbox b ON oi.blindbox_id = b.blindbox_id 
                    WHERE oi.order_id = ?";
            $order['items'] = Database::fetchAll($sql, [$order_id]);
        }

        return $order;
    }

    // Complete order
    public function completeOrder($order_id, $user_id)
    {
        $sql = "UPDATE orders SET status = 'completed' WHERE order_id = ? AND user_id = ? AND status = 'shipped'";
        $stmt = Database::query($sql, [$order_id, $user_id]);
        $result = $stmt->rowCount() > 0;
        return [
            'success' => $result,
            'message' => $result ? 'Order marked as completed' : 'Failed to complete order'
        ];
    }

    // Cancel order directly
    public function cancelOrder($order_id, $user_id)
    {
        $sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ? AND user_id = ? AND status IN ('paid', 'pending')";
        $stmt = Database::query($sql, [$order_id, $user_id]);
        $result = $stmt->rowCount() > 0;
        return [
            'success' => $result,
            'message' => $result ? 'Order cancelled successfully' : 'Failed to cancel order'
        ];
    }
}
