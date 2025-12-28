<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Payment.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Payment extends Database
{
    public function addPayment($order_id, $payment_method, $status, $amount)
    {
        try {
            $payment_id = IDGenerator::paymentID();

            $sql = "INSERT INTO payments 
                (payment_id, order_id, payment_method, status, amount, is_deleted) 
                VALUES (?, ?, ?, ?, ?, ?)";

            $this->query($sql, [
                $payment_id,
                $order_id,
                $payment_method,
                $status,
                $amount,
                0 // not deleted
            ]);

            return $payment_id;
        } catch (Exception $e) {
            throw new Exception("Failed to add new payment record." . $e->getMessage());
        }
    }

    public function getPaymentByOrderID($order_id)
    {
        try {
            $sql = "SELECT * FROM payments WHERE order_id = ?";
            $rows = $this->query($sql, [$order_id])->fetchAll();

            $payment = [];
            foreach ($rows as $row) {
                $payment[] = new PaymentEntity($row);
            }
            return $payment;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve payment for $order_id.");
        }
    }

    public function getPaymentByID($payment_id)
    {
        try {
            $sql = "SELECT * FROM payments WHERE payment_id = ?";
            $rows = $this->query($sql, [$payment_id])->fetchAll();

            $payment = [];
            foreach ($rows as $row) {
                $payment[] = new PaymentEntity($row);
            }
            return $payment;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve payment $payment_id.");
        }
    }

    public function updatePaymentMethod($payment_id, $payment_method)
    {
        try {
            $sql = "UPDATE payments SET payment_method = ? WHERE payment_id = ?";
            return $this->query($sql, [$payment_method, $payment_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update payment $payment_id.");
        }
    }

    public function updatePaymentStatus($payment_id, $payment_status)
    {
        try {
            $sql = "UPDATE payments SET payment_status = ? WHERE payment_id = ?";
            return $this->query($sql, [$payment_status, $payment_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to update payment $payment_id.");
        }
    }

    public function removePayment($payment_id)
    {
        try {
            $sql = "UPDATE payments SET is_deleted = ? WHERE payment_id = ?";
            return $this->query($sql, [1 , $payment_id]);
        } catch (Exception $e) {
            throw new Exception("Failed to remove payment $payment_id.");
        }
    }
}
