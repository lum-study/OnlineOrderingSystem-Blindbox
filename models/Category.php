<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Category.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class Category extends Database
{
    public function getAllCategories($sortBy = 'category_name', $sortOrder = 'ASC')
    {
        try {
            // Validate sort parameters
            $allowedColumns = ['category_id', 'category_name', 'created_date'];
            $allowedOrder = ['ASC', 'DESC'];
            
            if (!in_array($sortBy, $allowedColumns)) {
                $sortBy = 'category_name';
            }
            if (!in_array($sortOrder, $allowedOrder)) {
                $sortOrder = 'ASC';
            }
            
            $sql = "SELECT * FROM categories WHERE is_deleted = 0 ORDER BY $sortBy $sortOrder";
            $rows = $this->query($sql, [])->fetchAll();

            $categories = [];
            foreach ($rows as $row) {
                $categories[] = new CategoryEntity($row);
            }
            return $categories;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve categories.");
        }
    }

    public function getCategoryById($category_id)
    {
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }

        try {
            $sql = "SELECT * FROM categories WHERE category_id = ? AND is_deleted = 0";
            $row = $this->query($sql, [$category_id])->fetch();

            if (!$row) {
                throw new Exception("Category not found.");
            }

            return new CategoryEntity($row);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve category.");
        }
    }

    public function addCategory($category_name, $description)
    {
        if (empty($category_name)) {
            throw new Exception("Category name is required.");
        }

        try {
            $category_id = IDGenerator::categoryID();

            $sql = "INSERT INTO categories (category_id, category_name, description) 
                    VALUES (?, ?, ?)";
            $this->query($sql, [$category_id, $category_name, $description]);

            return $category_id;
        } catch (Exception $e) {
            error_log("Error adding category: " . $e->getMessage());
            throw new Exception("Failed to create category.");
        }
    }

    public function updateCategory($category_id, $category_name, $description)
    {
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }
        if (empty($category_name)) {
            throw new Exception("Category name is required.");
        }

        try {
            $sql = "UPDATE categories SET category_name = ?, description = ? 
                    WHERE category_id = ?";
            $this->query($sql, [$category_name, $description, $category_id]);
        } catch (Exception $e) {
            error_log("Error updating category: " . $e->getMessage());
            throw new Exception("Failed to update category.");
        }
    }

    public function deleteCategory($category_id)
    {
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }

        try {
            $sql = "UPDATE categories SET is_deleted = 1 WHERE category_id = ?";
            $this->query($sql, [$category_id]);
        } catch (Exception $e) {
            error_log("Error deleting category: " . $e->getMessage());
            throw new Exception("Failed to delete category.");
        }
    }
}
