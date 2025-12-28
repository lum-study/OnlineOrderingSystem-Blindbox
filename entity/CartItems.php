<?php
class CartItemEntity
{
    private $cart_item_id;
    private $cart_id;
    private $product_id;
    private $quantity;
    private $created_date;
    private $updated_date;

    public function __construct(array $data = [])
    {
        $this->cart_item_id = $data['cart_item_id'] ?? null;
        $this->cart_id = $data['cart_id'] ?? null;
        $this->product_id = $data['product_id'] ?? null;
        $this->quantity = $data['quantity'] ?? null;
        $this->created_date = $data['created_date'] ?? null;
        $this->updated_date = $data['updated_date'] ?? null;
    }

    public function getCartItemID()
    {
        return $this->cart_item_id;
    }

    public function getCartId()
    {
        return $this->cart_id;
    }

    public function getProductId()
    {
        return $this->product_id;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function setCartItemId($cart_item_id)
    {
        $this->cart_item_id = $cart_item_id;
    }

    public function setCartId($cart_id)
    {
        $this->cart_id = $cart_id;
    }

    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
    }

    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function setCreatedDate($created_date)
    {
        $this->created_date = $created_date;
    }

    public function setUpdatedDate($updated_date)
    {
        $this->updated_date = $updated_date;
    }
}
