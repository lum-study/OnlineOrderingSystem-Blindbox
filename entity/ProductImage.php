<?php

class ProductImageEntity
{
    private $image_id;
    private $product_id;
    private $image_url;
    private $is_front;
    private $upload_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data)
    {
        $this->image_id = $data['image_id'];
        $this->product_id = $data['product_id'];
        $this->image_url = $data['image_url'] ?? null;
        $this->is_front = $data['is_front'] ?? 0;
        $this->upload_date = $data['upload_date'];
        $this->updated_date = $data['updated_date'];
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getImageId()
    {
        return $this->image_id;
    }

    public function getProductId()
    {
        return $this->product_id;
    }

    public function getImageUrl()
    {
        return $this->image_url;
    }

    public function isFront()
    {
        return $this->is_front;
    }

    public function getUploadDate()
    {
        return $this->upload_date;
    }

    public function getUpdatedDate()
    {
        return $this->updated_date;
    }

    public function isDeleted()
    {
        return $this->is_deleted;
    }

    public function setImageId($image_id)
    {
        $this->image_id = $image_id;
    }

    public function setProductId($product_id)
    {
        $this->product_id = $product_id;
    }

    public function setImageUrl($image_url)
    {
        $this->image_url = $image_url;
    }

    public function setUploadDate($upload_date)
    {
        $this->upload_date = $upload_date;
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
