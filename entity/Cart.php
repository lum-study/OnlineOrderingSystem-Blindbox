<?php

class CartEntity
{
    private $cart_id;
    private $user_id;
    private $created_date;
    private $updated_date;

    public function __construct($data)
    {
        $this->cart_id = $data['cart_id'];
        $this->user_id = $data['user_id'];
        $this->created_date = $data['created_date'];
        $this->updated_date = $data['updated_date'];
    }

    public function getCartId()
    {
        return $this->cart_id;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function setCartId($cart_id)
    {
        $this->cart_id = $cart_id;
    }

    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
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
