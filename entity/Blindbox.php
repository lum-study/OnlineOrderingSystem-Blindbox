<?php

class BlindboxEntity
{
    private $blindbox_id;
    private $category_id;
    private $product_name;
    private $description;
    private $price;
    private $stock_quantity;
    private $status;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data)
    {
        $this->blindbox_id = $data['blindbox_id'];
        $this->category_id = $data['category_id'];
        $this->product_name = $data['product_name'];
        $this->description = $data['description'] ?? null;
        $this->price = $data['price'];
        $this->stock_quantity = $data['stock_quantity'];
        $this->status = $data['status'] ?? 'active';
        $this->created_date = $data['created_date'];
        $this->updated_date = $data['updated_date'];
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getBlindboxId()
    {
        return $this->blindbox_id;
    }

    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function getProductName()
    {
        return $this->product_name;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function getStockQuantity()
    {
        return $this->stock_quantity;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getCreatedDate()
    {
        return $this->created_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function isDeleted()
    {
        return $this->is_deleted;
    }

    public function setBlindboxId($blindbox_id)
    {
        $this->blindbox_id = $blindbox_id;
    }

    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;
    }

    public function setProductName($product_name)
    {
        $this->product_name = $product_name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setPrice($price)
    {
        $this->price = $price;
    }

    public function setStockQuantity($stock_quantity)
    {
        $this->stock_quantity = $stock_quantity;
    }

    public function setStatus($status)
    {
        $this->status = $status;
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
