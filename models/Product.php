<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Product.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Product extends Database
{
    public function getAllProducts()
    {
        try {
            $sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY product_name ASC";
            $rows = $this->query($sql, [])->fetchAll();

            $products = [];
            foreach ($rows as $row) {
                $products[] = new ProductEntity($row);
            }
            return $products;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve products.");
        }
    }

    public function getProductById($product_id)
    {
        if (empty($product_id)) {
            throw new Exception("Product ID is required.");
        }

        try {
            $sql = "SELECT * FROM products WHERE product_id = ? AND is_deleted = 0";
            $row = $this->query($sql, [$product_id])->fetch();

            if (!$row) {
                throw new Exception("Product not found.");
            }

            return new ProductEntity($row);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve product.");
        }
    }

    public function getProductsByBlindboxId($blindbox_id)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }

        try {
            $sql = "SELECT * FROM products WHERE blindbox_id = ? AND is_deleted = 0 ORDER BY product_name ASC";
            $rows = $this->query($sql, [$blindbox_id])->fetchAll();

            $products = [];
            foreach ($rows as $row) {
                $products[] = new ProductEntity($row);
            }
            return $products;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve products by blindbox.");
        }
    }

    public function searchProducts($search_query)
    {
        if (empty($search_query)) {
            throw new Exception("Search query is required.");
        }

        try {
            $search_term = '%' . $search_query . '%';
            $sql = "SELECT * FROM products WHERE product_name LIKE ? AND is_deleted = 0 ORDER BY product_name ASC";
            $rows = $this->query($sql, [$search_term])->fetchAll();

            $products = [];
            foreach ($rows as $row) {
                $products[] = new ProductEntity($row);
            }
            return $products;
        } catch (Exception $e) {
            throw new Exception("Failed to search products.");
        }
    }

    public function addProduct($blindbox_id, $product_name)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }

        try {
            $product_id = IDGenerator::productID();

            $sql = "INSERT INTO products (product_id, blindbox_id, product_name) 
                    VALUES (?, ?, ?)";
            $this->query($sql, [$product_id, $blindbox_id, $product_name]);

            return $product_id;
        } catch (Exception $e) {
            error_log("Error adding product: " . $e->getMessage());
            throw new Exception("Failed to create product.");
        }
    }

    public function updateProduct($product_id, $blindbox_id, $product_name)
    {
        if (empty($product_id)) {
            throw new Exception("Product ID is required.");
        }
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }

        try {
            $sql = "UPDATE products SET blindbox_id = ?, product_name = ? WHERE product_id = ?";
            $this->query($sql, [$blindbox_id, $product_name, $product_id]);
        } catch (Exception $e) {
            error_log("Error updating product: " . $e->getMessage());
            throw new Exception("Failed to update product.");
        }
    }

    public function deleteProduct($product_id)
    {
        if (empty($product_id)) {
            throw new Exception("Product ID is required.");
        }

        try {
            $sql = "UPDATE products SET is_deleted = 1 WHERE product_id = ?";
            $this->query($sql, [$product_id]);
        } catch (Exception $e) {
            error_log("Error deleting product: " . $e->getMessage());
            throw new Exception("Failed to delete product.");
        }
    }
}
