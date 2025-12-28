<?php
// OrderItem.php - Entity class for OrderItem
class OrderItemEntity
{
    private $order_item_id;
    private $order_id;
    private $blindbox_id;
    private $quantity;
    private $price;
    private $subtotal;
    private $tax_amount;

    public function __construct($data = [])
    {
        if (is_array($data)) {
            $this->order_item_id = $data['order_item_id'] ?? null;
            $this->order_id = $data['order_id'] ?? null;
            $this->blindbox_id = $data['blindbox_id'] ?? null;
            $this->quantity = $data['quantity'] ?? null;
            $this->price = $data['price'] ?? null;
            $this->subtotal = $data['subtotal'] ?? null;
            $this->tax_amount = $data['tax_amount'] ?? null;
        }
    }

    public function getOrderItemId()
    {
        return $this->order_item_id;
    }
    public function setOrderItemId($order_item_id)
    {
        $this->order_item_id = $order_item_id;
    }

    public function getOrderId()
    {
        return $this->order_id;
    }
    public function setOrderId($order_id)
    {
        $this->order_id = $order_id;
    }

    public function getBlindboxId()
    {
        return $this->blindbox_id;
    }
    public function setBlindboxId($blindbox_id)
    {
        $this->blindbox_id = $blindbox_id;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function getPrice()
    {
        return $this->price;
    }
    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function getSubtotal()
    {
        return $this->subtotal;
    }
    public function setSubtotal($subtotal)
    {
        $this->subtotal = $subtotal;
    }

    public function getTaxAmount()
    {
        return $this->tax_amount;
    }
    public function setTaxAmount($tax_amount)
    {
        $this->tax_amount = $tax_amount;
    }
}
