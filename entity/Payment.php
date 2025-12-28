<?php

class PaymentEntity
{
    private $payment_id;
    private $order_id;
    private $payment_method;
    private $status;
    private $amount;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data)
    {
        $this->payment_id = $data['payment_id'] ?? null;
        $this->order_id = $data['order_id'] ?? null;
        $this->payment_method = $data['payment_method'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->amount = $data['amount'] ?? null;
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getPaymentId()
    {
        return $this->payment_id;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }

    public function getPaymentMethod()
    {
        return $this->payment_method;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function getIsDeleted()
    {
        return $this->is_deleted;
    }

    public function setPaymentId($payment_id)
    {
        $this->payment_id = $payment_id;
    }

    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    public function setPaymentMethod($payment_method)
    {
        $this->payment_method = $payment_method;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }

    public function setUpdatedDate($updated_date)
    {
        $this->updated_date = $updated_date;
    }

    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
    }
}
