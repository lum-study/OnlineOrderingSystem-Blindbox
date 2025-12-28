<?php
require_once __DIR__ . '/../../lib/Database.php';
require_once __DIR__ . '/../../lib/Security.php';
require_once __DIR__ . '/../../models/Order.php';

class AdminOrderController
{
    private $orderModel;

    public function __construct()
    {
        $this->orderModel = new Order();
    }

    // Update order
    public function updateOrder($order_id, $data)
    {
        try {
            if (!Security::verifyCSRF($data['csrf_token'] ?? '')) {
                http_response_code(403);
                return ['success' => false, 'message' => 'Invalid request. Please refresh and try again.'];
            }

            $this->orderModel->updateStatusAndAddress($order_id, $data['status'], $data['shipping_address']);
            return ['success' => true, 'message' => 'Order updated successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
