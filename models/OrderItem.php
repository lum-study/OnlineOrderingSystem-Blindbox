<?php
require_once __DIR__ . '/../lib/Database.php';

class OrderItem
{
    // Get all order items for a specific order
    public static function getByOrderId($order_id)
    {
        $sql = "SELECT oi.*, b.product_name, b.price as unit_price 
                FROM order_items oi
                JOIN blindbox b ON oi.blindbox_id = b.blindbox_id
                WHERE oi.order_id = ?";
        return Database::fetchAll($sql, [$order_id]);
    }

    // Create new order item
    public static function create($data)
    {
        $sql = "INSERT INTO order_items (order_item_id, order_id, blindbox_id, quantity, price, subtotal, tax_amount) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        return Database::query($sql, [
            $data['order_item_id'],
            $data['order_id'],
            $data['blindbox_id'],
            $data['quantity'],
            $data['price'],
            $data['subtotal'],
            $data['tax_amount']
        ]);
    }

    // Update order item
    public static function update($order_item_id, $data)
    {
        $sql = "UPDATE order_items SET quantity = ?, price = ?, subtotal = ?, tax_amount = ? 
                WHERE order_item_id = ?";
        return Database::query($sql, [
            $data['quantity'],
            $data['price'],
            $data['subtotal'],
            $data['tax_amount'],
            $order_item_id
        ]);
    }

    // Delete order item
    public static function delete($order_item_id)
    {
        $sql = "DELETE FROM order_items WHERE order_item_id = ?";
        return Database::query($sql, [$order_item_id]);
    }

    public function getOrderCountByBlindboxId($blindboxId)
    {
        $sql = "SELECT SUM(oi.quantity) as totalOrders
                FROM order_items AS oi
                INNER JOIN orders AS o ON oi.order_id = o.order_id
                WHERE oi.blindbox_id = ? AND o.is_deleted = 0";
        $result = Database::query($sql, [$blindboxId])->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['totalOrders'] ?? 0);
    }
}