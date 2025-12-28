<?php
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../controllers/CheckoutController.php';

class PaymentController
{
    private $paymentModel;
    private $orderModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
        $this->orderModel = new Order();
    }

    public function initiatePayment()
    {
        header('Content-Type: application/json');

        try {
            $paymentData = $this->validateAndGetPaymentData();
            $orderID = $paymentData["order_id"];
            $paymentMethod = $paymentData["payment_method"];
            $amount = $paymentData["amount"];

            $this->validateOrderOwnership($orderID, $_SESSION["user_id"]);

            if ($this->getPaymentByOrderID($orderID)) {
                throw new Exception("Order $orderID already paid");
            }

            if ($paymentMethod === 'card') {
                $stripe = new StripeLib();
                $result = $stripe->createPaymentIntent(intval(round($amount * 100)));

                if (!$result["success"]) {
                    throw new Exception($result['error'] ?? 'Payment gateway error');
                }

                echo json_encode([
                    'success' => true,
                    'client_secret' => $result['client_secret']
                ]);
            } else {
                // Cash or other methods needing no initialization
                echo json_encode(['success' => true]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Add a new payment record
     */
    public function addPayment()
    {
        header('Content-Type: application/json');

        try {
            $paymentData = $this->validateAndGetPaymentData();
            $paymentId = $this->createPaymentRecord($paymentData);
            $this->orderModel->updateStatus($paymentData["order_id"], "pending");
            echo json_encode([
                'success' => true,
                'payment_id' => $paymentId,
                'message' => 'Payment recorded successfully'
            ]);

        } catch (ValidationException $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'field' => $e->getField() ?? null
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log('PaymentController Error: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while processing your payment. Please contact support.'
            ]);
        }
    }

    /**
     * Get payment details by order ID
     */
    public function getPaymentByOrderID($order_id)
    {
        try {
            $this->validateOrderId($order_id);
            return $this->paymentModel->getPaymentByOrderId($order_id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Error getting payment for order $order_id: " . $e->getMessage());
            throw new Exception('Failed to retrieve payment information. Please try again.');
        }
    }

    /**
     * Get payment details by payment ID
     */
    public function getPaymentByID($payment_id)
    {
        try {
            $this->validatePaymentId($payment_id);
            return $this->paymentModel->getPaymentByID($payment_id);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Error getting payment $payment_id: " . $e->getMessage());
            throw new Exception('Failed to retrieve payment details. Please try again.');
        }
    }

    /**
     * Update payment method
     */
    public function updatePaymentMethod($payment_id, $payment_method)
    {
        try {
            $this->validatePaymentId($payment_id);
            $this->validatePaymentMethod($payment_method);

            $updated_date = date('Y-m-d H:i:s');
            $result = $this->paymentModel->updatePaymentMethod($payment_id, $payment_method, $updated_date);

            if (!$result) {
                throw new Exception('Failed to update payment method.');
            }

            return true;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Error updating payment method for $payment_id: " . $e->getMessage());
            throw new Exception('Failed to update payment method. Please try again.');
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($payment_id, $payment_status)
    {
        try {
            $this->validatePaymentId($payment_id);
            $this->validatePaymentStatus($payment_status);

            $updated_date = date('Y-m-d H:i:s');
            $result = $this->paymentModel->updatePaymentStatus($payment_id, $payment_status, $updated_date);

            if (!$result) {
                throw new Exception('Failed to update payment status.');
            }

            return true;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Error updating payment status for $payment_id: " . $e->getMessage());
            throw new Exception('Failed to update payment status. Please try again.');
        }
    }

    /**
     * Remove payment record
     */
    public function removePayment($payment_id)
    {
        try {
            $this->validatePaymentId($payment_id);

            // Check if payment exists before attempting to remove
            $payment = $this->getPaymentByID($payment_id);
            if (!$payment) {
                throw new ValidationException('Payment not found.', 'payment_id');
            }

            $result = $this->paymentModel->removePayment($payment_id);

            if (!$result) {
                throw new Exception('Failed to remove payment.');
            }

            return true;

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            error_log("Error removing payment $payment_id: " . $e->getMessage());
            throw new Exception('Failed to remove payment. Please try again.');
        }
    }

    /**
     * Validate and get payment data from input
     */
    private function validateAndGetPaymentData(): array
    {
        $raw = json_decode(file_get_contents("php://input"), true);

        if (!is_array($raw)) {
            throw new ValidationException('Invalid or empty payment data received.');
        }

        // Required fields
        $requiredFields = ['order_id', 'payment_method'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($raw[$field]) || trim((string) $raw[$field]) === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if (!empty($errors)) {
            throw new ValidationException(implode('. ', $errors) . '.');
        }

        $order_id = trim((string) $raw['order_id']);
        $payment_method = strtolower(trim((string) $raw['payment_method']));

        // Validate order exists and belongs to user
        $orderData = $this->validateOrderAndGetData($order_id);
        $amount = $orderData["total_amount"];

        // Validate payment method
        $validPaymentMethods = ['card', 'cash'];
        if (!in_array($payment_method, $validPaymentMethods, true)) {
            throw new ValidationException(
                'Invalid payment method. Allowed values: ' . implode(', ', $validPaymentMethods),
                'payment_method'
            );
        }

        return [
            'order_id' => $order_id,
            'payment_method' => $payment_method,
            'amount' => $amount
        ];
    }

    /**
     * Validate order ownership and return order data
     */
    private function validateOrderAndGetData($orderID): array
    {
        $this->validateOrderId($orderID);
        $orderData = $this->orderModel->getOrderById($orderID);

        if (!$orderData) {
            throw new ValidationException('Order not found.', 'order_id');
        }

        if ($orderData["user_id"] !== $_SESSION["user_id"]) {
            throw new Exception('Invalid order ID');
        }

        return $orderData;
    }

    /**
     * Create payment record in database
     */
    private function createPaymentRecord(array $paymentData): string
    {
        try {
            $paymentId = $this->paymentModel->addPayment(
                $paymentData['order_id'],
                $paymentData['payment_method'],
                "completed",
                $paymentData['amount'],
            );

            if (!$paymentId) {
                throw new Exception('Failed to create payment record in database.');
            }

            return $paymentId;

        } catch (Exception $e) {
            error_log('Error creating payment record: ' . $e->getMessage());
            throw new Exception('Failed to create payment record. Please try again.');
        }
    }

    /**
     * Validate order ownership (for methods that don't need full order data)
     */
    private function validateOrderOwnership($orderID, $userID): void
    {
        $orderData = $this->orderModel->getOrderById($orderID);

        if (!$orderData) {
            throw new Exception('Order not found');
        }

        if ($orderData["user_id"] !== $userID) {
            throw new Exception('Invalid order ID');
        }
    }

    /**
     * Validate order ID
     */
    private function validateOrderId($order_id): void
    {
        if (empty($order_id)) {
            throw new ValidationException('Order ID is required.', 'order_id');
        }

        if (!is_string($order_id)) {
            throw new ValidationException('Order ID must be a string.', 'order_id');
        }

        if (strlen(trim($order_id)) < 1) {
            throw new ValidationException('Order ID cannot be empty.', 'order_id');
        }
    }

    /**
     * Validate payment ID
     */
    private function validatePaymentId($payment_id): void
    {
        if (empty($payment_id)) {
            throw new ValidationException('Payment ID is required.', 'payment_id');
        }

        if (!is_string($payment_id)) {
            throw new ValidationException('Payment ID must be a string.', 'payment_id');
        }
    }

    /**
     * Validate payment method
     */
    private function validatePaymentMethod($payment_method): void
    {
        if (empty($payment_method)) {
            throw new ValidationException('Payment method is required.', 'payment_method');
        }

        $validMethods = ['card', 'cash'];
        if (!in_array(strtolower($payment_method), $validMethods)) {
            throw new ValidationException(
                'Invalid payment method. Valid methods are: ' . implode(', ', $validMethods),
                'payment_method'
            );
        }
    }

    /**
     * Validate payment status
     */
    private function validatePaymentStatus($payment_status): void
    {
        if (empty($payment_status)) {
            throw new ValidationException('Payment status is required.', 'payment_status');
        }

        $validStatuses = ['completed', 'pending', 'failed', 'refunded', 'processing'];
        if (!in_array(strtolower($payment_status), $validStatuses)) {
            throw new ValidationException(
                'Invalid payment status. Valid statuses are: ' . implode(', ', $validStatuses),
                'payment_status'
            );
        }
    }
}