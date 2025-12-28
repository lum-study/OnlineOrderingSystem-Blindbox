<?php

class CategoryEntity
{
    private $category_id;
    private $category_name;
    private $description;
    private $created_date;
    private $updated_date;
    private $is_deleted;

    public function __construct($data)
    {
        $this->category_id = $data['category_id'];
        $this->category_name = $data['category_name'];
        $this->description = $data['description'] ?? null;
        $this->created_date = $data['created_date'];
        $this->updated_date = $data['updated_date'];
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getCategoryId()
    {
        return $this->category_id;
    }

    public function getCategoryName()
    {
        return $this->category_name;
    }

    public function getDescription()
    {
        return $this->description;
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

    public function setCategoryId($category_id)
    {
        $this->category_id = $category_id;
    }

    public function setCategoryName($category_name)
    {
        $this->category_name = $category_name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
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
