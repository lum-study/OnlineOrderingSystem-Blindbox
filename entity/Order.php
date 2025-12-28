<?php
// Order.php - Entity class for Order
class OrderEntity
{
    private $order_id;
    private $user_id;
    private $sub_total_amount;
    private $total_amount;
    private $tax_amount;
    private $status;
    private $shipping_address;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data = [])
    {
        if (is_array($data)) {
            $this->order_id = $data['order_id'] ?? null;
            $this->user_id = $data['user_id'] ?? null;
            $this->sub_total_amount = $data['sub_total_amount'] ?? null;
            $this->total_amount = $data['total_amount'] ?? null;
            $this->tax_amount = $data['tax_amount'] ?? null;
            $this->status = $data['status'] ?? 'pending';
            $this->shipping_address = $data['shipping_address'] ?? null;
            $this->created_date = $data['created_date'] ?? null;
            $this->updated_date = $data['updated_date'] ?? null;
            $this->is_deleted = $data['is_deleted'] ?? 0;
        }
    }

    public function getOrderId()
    {
        return $this->order_id;
    }
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    public function getSubTotalAmount()
    {
        return $this->sub_total_amount;
    }
    public function setSubTotalAmount($sub_total_amount)
    {
        $this->sub_total_amount = $sub_total_amount;
    }

    public function getTotalAmount()
    {
        return $this->total_amount;
    }
    public function setTotalAmount($total_amount)
    {
        $this->total_amount = $total_amount;
    }

    public function getTaxAmount()
    {
        return $this->tax_amount;
    }
    public function setTaxAmount($tax_amount)
    {
        $this->tax_amount = $tax_amount;
    }

    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getShippingAddress()
    {
        return $this->shipping_address;
    }
    public function setShippingAddress($shipping_address)
    {
        $this->shipping_address = $shipping_address;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }
    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }
    public function setUpdatedDate($updated_date)
    {
        $this->updated_date = $updated_date;
    }

    public function getIsDeleted()
    {
        return $this->is_deleted;
    }
    public function setIsDeleted($is_deleted)
    {
        $this->is_deleted = $is_deleted;
    }
}
