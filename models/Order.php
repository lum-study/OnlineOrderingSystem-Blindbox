<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Order.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Order extends Database
{
    // Fetch all orders with user details
    public function getAllOrders()
    {
        try {
            $sql = "SELECT o.*, u.fullname, u.email 
                    FROM orders o 
                    JOIN user_data u ON o.user_id = u.user_id 
                    WHERE o.is_deleted = 0
                    ORDER BY o.created_date DESC";
            $rows = $this->query($sql, [])->fetchAll();
            
            return $rows;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve orders.");
        }
    }

    // Fetch specific order by ID
    public function getOrderById($order_id)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "SELECT o.*, u.fullname, u.email, u.contact_number, u.gender, p.payment_method
                    FROM orders o 
                    JOIN user_data u ON o.user_id = u.user_id 
                    LEFT JOIN payments p ON o.order_id = p.order_id
                    WHERE o.order_id = ? AND o.is_deleted = 0";
            $row = $this->query($sql, [$order_id])->fetch();
            
            if (!$row) {
                throw new Exception("Order not found.");
            }
            
            return $row;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve order.");
        }
    }

    // Create new order
    public function create($data)
    {
        try {
            $sql = "INSERT INTO orders (order_id, user_id, sub_total_amount, total_amount, tax_amount, status, shipping_address, shipping_id, is_deleted) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $this->query($sql, [
                $data['order_id'],
                $data['user_id'],
                $data['sub_total_amount'],
                $data['total_amount'],
                $data['tax_amount'],
                $data['status'] ?? 'pending',
                $data['shipping_address'],
                $data['shipping_id'],
                0
            ]);
            return $data['order_id'];
        } catch (Exception $e) {
            error_log("Error creating order: " . $e->getMessage());
            throw new Exception("Failed to create order.");
        }
    }

    // Update order
    public function update($order_id, $data)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "UPDATE orders SET sub_total_amount = ?, total_amount = ?, shipping_amount = ?, tax_amount = ?, status = ?, shipping_address = ? 
                    WHERE order_id = ?";
            $this->query($sql, [
                $data['sub_total_amount'],
                $data['total_amount'],
                $data['shipping_amount'],
                $data['tax_amount'],
                $data['status'],
                $data['shipping_address'],
                $order_id
            ]);
        } catch (Exception $e) {
            error_log("Error updating order: " . $e->getMessage());
            throw new Exception("Failed to update order.");
        }
    }

    // Update order status
    public function updateStatus($order_id, $status)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "UPDATE orders SET status = ? WHERE order_id = ?";
            $this->query($sql, [$status, $order_id]);
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
            throw new Exception("Failed to update order status.");
        }
    }

    // Update order status and address
    public function updateStatusAndAddress($order_id, $status, $address)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "UPDATE orders SET status = ?, shipping_address = ? WHERE order_id = ?";
            $this->query($sql, [$status, $address, $order_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error updating order: " . $e->getMessage());
            throw new Exception("Failed to update order.");
        }
    }

    // Get count of orders grouped by status
    public function getOrdersByStatusCount()
    {
        try {
            $sql = "SELECT status, COUNT(*) as count FROM orders WHERE is_deleted = 0 GROUP BY status";
            $results = $this->query($sql, [])->fetchAll();

            $counts = [
                'pending' => 0,
                'paid' => 0,
                'shipped' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];

            foreach ($results as $row) {
                $counts[$row['status']] = $row['count'];
            }

            return $counts;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve order counts.");
        }
    }

    // Get order items
    public function getOrderItems($order_id)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "SELECT oi.*, b.product_name, b.price as unit_price 
                    FROM order_items oi
                    JOIN blindbox b ON oi.blindbox_id = b.blindbox_id
                    WHERE oi.order_id = ?";
            return $this->query($sql, [$order_id])->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve order items.");
        }
    }

    // Soft delete order
    public function delete($order_id)
    {
        if (empty($order_id)) {
            throw new Exception("Order ID is required.");
        }
        
        try {
            $sql = "UPDATE orders SET is_deleted = 1 WHERE order_id = ?";
            $this->query($sql, [$order_id]);
            return true;
        } catch (Exception $e) {
            error_log("Error deleting order: " . $e->getMessage());
            throw new Exception("Failed to delete order.");
        }
    }
}
