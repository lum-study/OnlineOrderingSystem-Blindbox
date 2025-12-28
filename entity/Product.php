<?php

class ProductEntity
{
    private $product_id;
    private $blindbox_id;
    private $product_name;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data)
    {
        $this->product_id = $data['product_id'];
        $this->blindbox_id = $data['blindbox_id'];
        $this->product_name = $data['product_name'];
        $this->created_date = $data['created_date'];
        $this->updated_date = $data['updated_date'];
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getProductId()
    {
        return $this->product_id;
    }

    public function getBlindboxId()
    {
        return $this->blindbox_id;
    }

    public function getProductName()
    {
        return $this->product_name;
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

    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
    }

    public function setBlindboxId($blindbox_id)
    {
        $this->blindbox_id = $blindbox_id;
    }

    public function setProductName($product_name)
    {
        $this->product_name = $product_name;
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
