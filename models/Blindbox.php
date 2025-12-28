<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/Blindbox.php';
require_once __DIR__ . '/../lib/IDGenerator.php';
require_once __DIR__ . '/../lib/Pagination.php';

class Blindbox extends Database
{
    public function getAllBlindboxes($sortBy = 'product_name', $sortOrder = 'ASC', $statusFilter = 'all')
    {
        try {
            // Validate sort parameters
            $allowedColumns = ['blindbox_id', 'product_name', 'category_id', 'price', 'stock_quantity'];
            $allowedOrder = ['ASC', 'DESC'];

            if (!in_array($sortBy, $allowedColumns)) {
                $sortBy = 'product_name';
            }
            if (!in_array($sortOrder, $allowedOrder)) {
                $sortOrder = 'ASC';
            }

            // Build WHERE clause based on status filter
            $whereClause = "is_deleted = 0";
            if ($statusFilter === 'active') {
                $whereClause .= " AND status = 'active'";
            } elseif ($statusFilter === 'inactive') {
                $whereClause .= " AND status = 'inactive'";
            }

            $sql = "SELECT * FROM blindbox WHERE $whereClause ORDER BY $sortBy $sortOrder";
            $rows = $this->query($sql, [])->fetchAll();

            $blindboxes = [];
            foreach ($rows as $row) {
                $blindboxes[] = new BlindboxEntity($row);
            }
            return $blindboxes;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindboxes.");
        }
    }

    public function getBlindboxById($blindbox_id)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }

        try {
            $sql = "SELECT * FROM blindbox WHERE blindbox_id = ? AND is_deleted = 0";
            $row = $this->query($sql, [$blindbox_id])->fetch();

            if (!$row) {
                throw new Exception("Blindbox not found.");
            }

            return new BlindboxEntity($row);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindbox.");
        }
    }

    public function getBlindboxesByCategory($category_id)
    {
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }

        try {
            $sql = "SELECT * FROM blindbox WHERE category_id = ? AND is_deleted = 0 ORDER BY product_name ASC";
            $rows = $this->query($sql, [$category_id])->fetchAll();

            $blindboxes = [];
            foreach ($rows as $row) {
                $blindboxes[] = new BlindboxEntity($row);
            }
            return $blindboxes;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindboxes by category.");
        }
    }

    public function searchBlindboxes($search_query)
    {
        if (empty($search_query)) {
            throw new Exception("Search query is required.");
        }

        try {
            $search_term = '%' . $search_query . '%';
            $sql = "SELECT * FROM blindbox 
                    WHERE (product_name LIKE ? OR description LIKE ?) AND is_deleted = 0 
                    ORDER BY product_name ASC";
            $rows = $this->query($sql, [$search_term, $search_term])->fetchAll();

            $blindboxes = [];
            foreach ($rows as $row) {
                $blindboxes[] = new BlindboxEntity($row);
            }
            return $blindboxes;
        } catch (Exception $e) {
            throw new Exception("Failed to search blindboxes.");
        }
    }

    public function addBlindbox($category_id, $product_name, $description, $price, $stock_quantity, $status = 'active')
    {
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }
        if (empty($price)) {
            throw new Exception("Price is required.");
        }
        if ($stock_quantity === null || $stock_quantity === '') {
            throw new Exception("Stock quantity is required.");
        }

        try {
            $blindbox_id = IDGenerator::blindboxID();

            $sql = "INSERT INTO blindbox (blindbox_id, category_id, product_name, description, price, stock_quantity, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $this->query($sql, [$blindbox_id, $category_id, $product_name, $description, $price, $stock_quantity, $status]);

            return $blindbox_id;
        } catch (Exception $e) {
            error_log("Error adding blindbox: " . $e->getMessage());
            throw new Exception("Failed to create blindbox.");
        }
    }

    public function updateBlindbox($blindbox_id, $category_id, $product_name, $description, $price, $stock_quantity, $status)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }
        if (empty($category_id)) {
            throw new Exception("Category ID is required.");
        }
        if (empty($product_name)) {
            throw new Exception("Product name is required.");
        }
        if (empty($price)) {
            throw new Exception("Price is required.");
        }
        if ($stock_quantity === null || $stock_quantity === '') {
            throw new Exception("Stock quantity is required.");
        }

        try {
            $sql = "UPDATE blindbox SET category_id = ?, product_name = ?, description = ?, price = ?, stock_quantity = ?, status = ? 
                    WHERE blindbox_id = ?";
            $this->query($sql, [$category_id, $product_name, $description, $price, $stock_quantity, $status, $blindbox_id]);
        } catch (Exception $e) {
            error_log("Error updating blindbox: " . $e->getMessage());
            throw new Exception("Failed to update blindbox.");
        }
    }

    public function deleteBlindbox($blindbox_id)
    {
        if (empty($blindbox_id)) {
            throw new Exception("Blindbox ID is required.");
        }

        try {
            $sql = "UPDATE blindbox SET is_deleted = 1 WHERE blindbox_id = ?";
            $this->query($sql, [$blindbox_id]);
        } catch (Exception $e) {
            error_log("Error deleting blindbox: " . $e->getMessage());
            throw new Exception("Failed to delete blindbox.");
        }
    }

    public function getNewArrival()
    {
        try {
            $sql = "SELECT *
                FROM blindbox
                WHERE is_deleted = 0
                ORDER BY created_date DESC
                LIMIT 4";
            $rows = $this->query($sql, [])->fetchAll();

            $blindboxes = [];
            foreach ($rows as $row) {
                $blindboxes[] = new BlindboxEntity($row);
            }

            return $blindboxes;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindboxes.");
        }
    }

    public function getTopSellingProduct()
    {
        try {
            $sql = "SELECT 
                        b.*,
                        SUM(oi.quantity) AS total_quantity_sold,
                        SUM(oi.subtotal) AS total_sales_amount
                    FROM order_items oi
                    INNER JOIN `orders` o 
                        ON oi.order_id = o.order_id
                    INNER JOIN blindbox b 
                        ON oi.blindbox_id = b.blindbox_id
                    WHERE 
                        o.status IN ('pending', 'completed', 'shipped')
                        AND o.is_deleted = 0
                        AND b.is_deleted = 0
                    GROUP BY 
                        b.blindbox_id,
                        b.product_name,
                        b.price
                    ORDER BY 
                        total_quantity_sold DESC
                    LIMIT 4;";
            $rows = $this->query($sql, [])->fetchAll();

            $blindboxes = [];
            foreach ($rows as $row) {
                $blindboxes[] = new BlindboxEntity($row);
            }

            return $blindboxes;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve blindboxes." . $e->getMessage());
        }
    }

    public function getBlindboxesFiltered($minPrice = null, $maxPrice = null, $search = '', $category = null, $sort = 'newest', $page = 1, $limit = 12)
    {
        $params = [];
        $where = " WHERE b.is_deleted = 0 ";

        if (!empty($search)) {
            $where .= " AND (b.product_name LIKE ? OR b.description LIKE ?) ";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if (!empty($category)) {
            $where .= " AND b.category_id = ? ";
            $params[] = $category;
        }

        if ($minPrice !== null && $maxPrice !== null) {
            $where .= " AND b.price BETWEEN ? AND ? ";
            $params[] = $minPrice;
            $params[] = $maxPrice;
        }

        // Sorting
        $order = " ORDER BY b.created_date DESC ";
        $join = "";
        $group = "";

        switch ($sort) {
            case 'low-high':
                $order = " ORDER BY b.price ASC ";
                break;
            case 'high-low':
                $order = " ORDER BY b.price DESC ";
                break;
            case 'popular':
                $join = "
                LEFT JOIN order_items oi ON oi.blindbox_id = b.blindbox_id
                LEFT JOIN orders o ON oi.order_id = o.order_id 
                                    AND o.status IN ('paid', 'completed', 'shipped') 
                                    AND o.is_deleted = 0
            ";
                $group = " GROUP BY b.blindbox_id ";
                $order = " ORDER BY SUM(oi.quantity) DESC ";
                break;
            case 'newest':
            default:
                $order = " ORDER BY b.created_date DESC ";
        }

        $query = "SELECT b.* " . ($sort === 'popular' ? ", COALESCE(SUM(oi.quantity),0) AS total_quantity_sold " : "") . "
              FROM blindbox b
              $join
              $where
              $group
              $order";

        return new SimplePager($query, $params, $limit, $page);
    }

    public function getPriceRange($category = null)
    {
        try {
            $params = [];
            $where = " WHERE is_deleted = 0 ";

            if (!empty($category)) {
                $where .= " AND category_id = ? ";
                $params[] = $category;
            }

            $sql = "SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM blindbox $where";
            $stmt = $this->query($sql, $params);
            return $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            throw new Exception("Failed to retrieve price range.");
        }
    }
}
