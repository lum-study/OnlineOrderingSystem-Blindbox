<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/ProductImage.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class ProductImage extends Database
{
    public function getImagesByProductId($product_id)
    {
        if (empty($product_id)) {
            throw new Exception("Product ID is required.");
        }

        try {
            $sql = "SELECT * FROM product_images 
                    WHERE product_id = ? AND is_deleted = 0 
                    ORDER BY is_front DESC, 
                    LENGTH(image_url) ASC, 
                    image_url ASC";
            $rows = $this->query($sql, [$product_id])->fetchAll();

            $images = [];
            foreach ($rows as $row) {
                $images[] = new ProductImageEntity($row);
            }
            return $images;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve product images.");
        }
    }

    public function getImageById($image_id)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }

        try {
            $sql = "SELECT * FROM product_images WHERE image_id = ? AND is_deleted = 0";
            $row = $this->query($sql, [$image_id])->fetch();

            if (!$row) {
                throw new Exception("Image not found.");
            }

            return new ProductImageEntity($row);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve image.");
        }
    }

    public function addImage($product_id, $image_url)
    {
        if (empty($product_id)) {
            throw new Exception("Product ID is required.");
        }
        if (empty($image_url)) {
            throw new Exception("Image URL is required.");
        }

        try {
            $image_id = IDGenerator::productImageID();

            $sql = "INSERT INTO product_images (image_id, product_id, image_url, is_front) 
                    VALUES (?, ?, ?, 0)";
            $this->query($sql, [$image_id, $product_id, $image_url]);

            return $image_id;
        } catch (Exception $e) {
            error_log("Error adding product image: " . $e->getMessage());
            throw new Exception("Failed to add image.");
        }
    }

    public function updateImage($image_id, $image_url)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }
        if (empty($image_url)) {
            throw new Exception("Image URL is required.");
        }

        try {
            $sql = "UPDATE product_images SET image_url = ? WHERE image_id = ?";
            $this->query($sql, [$image_url, $image_id]);
        } catch (Exception $e) {
            error_log("Error updating product image: " . $e->getMessage());
            throw new Exception("Failed to update image.");
        }
    }

    public function deleteImage($image_id)
    {
        if (empty($image_id)) {
            throw new Exception("Image ID is required.");
        }

        try {
            $sql = "UPDATE product_images SET is_deleted = 1 WHERE image_id = ?";
            $this->query($sql, [$image_id]);
        } catch (Exception $e) {
            error_log("Error deleting product image: " . $e->getMessage());
            throw new Exception("Failed to delete image.");
        }
    }
}
