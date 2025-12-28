<?php
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Blindbox.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../lib/Pagination.php';
require_once __DIR__ . '/../../lib/Database.php';

class AdminProductController
{
    private $categoryModel;
    private $blindboxModel;
    private $productModel;
    private $db;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->blindboxModel = new Blindbox();
        $this->productModel = new Product();
        $this->db = new Database();
    }

    public function index($params = [])
    {
        // Admin guard
        require_once __DIR__ . '/../../includes/admin_guard.php';

        // Get sort parameters for categories
        $sortBy = $_GET['sort_by'] ?? 'category_id';
        $sortOrder = $_GET['sort_order'] ?? 'ASC';
        $categoryPage = isset($_GET['category_page']) ? max(1, (int)$_GET['category_page']) : 1;
        $categorySearch = $_GET['category_search'] ?? '';

        // Validate sort parameters
        $allowedSortColumns = ['category_id', 'category_name', 'created_date'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'category_id';
        }
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'ASC';
        }

        // Get sort parameters for products
        $productSortBy = $_GET['product_sort_by'] ?? 'blindbox_id';
        $productSortOrder = $_GET['product_sort_order'] ?? 'ASC';
        $statusFilter = $_GET['status_filter'] ?? 'all';
        $productPage = isset($_GET['product_page']) ? max(1, (int)$_GET['product_page']) : 1;
        $productSearch = $_GET['product_search'] ?? '';

        // Validate product sort parameters
        $allowedProductSortColumns = ['blindbox_id', 'product_name', 'category_id', 'price', 'stock_quantity'];
        if (!in_array($productSortBy, $allowedProductSortColumns)) {
            $productSortBy = 'blindbox_id';
        }
        if (!in_array($productSortOrder, ['ASC', 'DESC'])) {
            $productSortOrder = 'ASC';
        }

        // Validate status filter
        if (!in_array($statusFilter, ['all', 'active', 'inactive'])) {
            $statusFilter = 'all';
        }

        // Initialize variables with defaults
        $categories = [];
        $blindboxes = [];
        $categoryPager = null;
        $productPager = null;
        $alertProducts = [];
        $categoryTotalCount = 0;
        $productTotalCount = 0;

        try {
            // Categories pagination
            $categoryQuery = "SELECT * FROM categories WHERE is_deleted = 0";
            $categoryParams = [];
            if (!empty($categorySearch)) {
                $categoryQuery .= " AND (category_id LIKE ? OR category_name LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $categorySearch . '%';
                $categoryParams = [$searchTerm, $searchTerm, $searchTerm];
            }
            $categoryQuery .= " ORDER BY $sortBy $sortOrder";
            $categoryPager = new SimplePager($categoryQuery, $categoryParams, 10, $categoryPage);
            $categories = $categoryPager->result;

            // Get total category count
            $categoryCountQuery = "SELECT COUNT(*) as total FROM categories WHERE is_deleted = 0";
            if (!empty($categorySearch)) {
                $categoryCountQuery .= " AND (category_id LIKE ? OR category_name LIKE ? OR description LIKE ?)";
            }
            $categoryTotalCount = $this->db->query($categoryCountQuery, $categoryParams)->fetch()['total'];
            $categoryCountQuery = "SELECT COUNT(*) as total FROM categories WHERE is_deleted = 0";
            if (!empty($categorySearch)) {
                $categoryCountQuery .= " AND (category_id LIKE ? OR category_name LIKE ? OR description LIKE ?)";
            }
            $categoryTotalCount = $this->db->query($categoryCountQuery, $categoryParams)->fetch()['total'];

            // Products pagination
            $productQuery = "SELECT * FROM blindbox WHERE is_deleted = 0";
            $productParams = [];
            if ($statusFilter === 'active') {
                $productQuery .= " AND stock_quantity > 0";
            } elseif ($statusFilter === 'inactive') {
                $productQuery .= " AND stock_quantity = 0";
            }
            if (!empty($productSearch)) {
                $productQuery .= " AND (blindbox_id LIKE ? OR product_name LIKE ? OR category_id LIKE ?)";
                $searchTerm = '%' . $productSearch . '%';
                $productParams = [$searchTerm, $searchTerm, $searchTerm];
            }
            $productQuery .= " ORDER BY $productSortBy $productSortOrder";
            $productPager = new SimplePager($productQuery, $productParams, 10, $productPage);
            $blindboxes = $productPager->result;

            // Get total product count
            $productCountQuery = "SELECT COUNT(*) as total FROM blindbox WHERE is_deleted = 0";
            $productCountParams = [];
            if ($statusFilter === 'active') {
                $productCountQuery .= " AND stock_quantity > 0";
            } elseif ($statusFilter === 'inactive') {
                $productCountQuery .= " AND stock_quantity = 0";
            }
            if (!empty($productSearch)) {
                $productCountQuery .= " AND (blindbox_id LIKE ? OR product_name LIKE ? OR category_id LIKE ?)";
                $productCountParams = $productParams;
            }
            $productTotalCount = $this->db->query($productCountQuery, $productCountParams)->fetch()['total'];

            // Check for low stock products (stock <= 10) and out of stock products
            $alertQuery = "SELECT blindbox_id, product_name, stock_quantity, category_id FROM blindbox WHERE is_deleted = 0 AND stock_quantity <= 10 ORDER BY stock_quantity ASC";
            $alertProducts = $this->db->query($alertQuery, [])->fetchAll();
        } catch (Exception $e) {
            error_log("Error fetching data: " . $e->getMessage());
        }

        // Pass all variables to view
        $categoryModel = $this->categoryModel;
        $blindboxModel = $this->blindboxModel;
        $productModel = $this->productModel;

        // Make renderPagination available in view scope
        $renderPagination = function ($pageParam, $currentPage, $totalPages, $href) {
            $this->renderPagination($pageParam, $currentPage, $totalPages, $href);
        };

        // Set page title
        $pageTitle = 'Product Management';

        // Include the view
        include __DIR__ . '/../../views/pages/admin/products/admin_products.php';
    }

    /**
     * Generate pagination HTML
     * @param string $pageParam The page parameter name (e.g., 'category_page' or 'product_page')
     * @param int $currentPage Current page number
     * @param int $totalPages Total number of pages
     * @param string $href URL parameters to preserve
     * @return void
     */
    private function renderPagination($pageParam, $currentPage, $totalPages, $href)
    {
        if ($totalPages <= 1) return;

        $prev = max($currentPage - 1, 1);
        $next = min($currentPage + 1, $totalPages);

        $prevDisabled = ($currentPage == 1);
        $nextDisabled = ($currentPage == $totalPages);

        $prevClass = "pager-item pager-prev" . ($prevDisabled ? " disabled" : "");
        $nextClass = "pager-item pager-next" . ($nextDisabled ? " disabled" : "");

        $prevHref = $prevDisabled ? "#" : "?{$pageParam}=$prev&$href";
        $nextHref = $nextDisabled ? "#" : "?{$pageParam}=$next&$href";

        echo "<nav class='pager'>";
        echo "<div class='pager-group'>";
        echo "<a href='{$prevHref}' class='{$prevClass}'" . ($prevDisabled ? " aria-disabled='true' tabindex='-1'" : "") . ">◀</a>";

        if ($totalPages <= 5) {
            for ($p = 1; $p <= $totalPages; $p++) {
                $active = $p == $currentPage ? " active" : "";
                echo "<a href='?{$pageParam}=$p&$href' class='pager-item$active'>$p</a>";
            }
        } else {
            // Determine the window of pages to show
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);

            if ($currentPage <= 3) {
                $endPage = min(5, $totalPages);
            }
            if ($currentPage > $totalPages - 3) {
                $startPage = max(1, $totalPages - 4);
            }

            if ($startPage > 1) {
                echo "<a href='?{$pageParam}=1&$href' class='pager-item'>1</a>";
                if ($startPage > 2) {
                    echo "<span class='pager-ellipsis'>...</span>";
                }
            }

            for ($p = $startPage; $p <= $endPage; $p++) {
                $active = $p == $currentPage ? " active" : "";
                echo "<a href='?{$pageParam}=$p&$href' class='pager-item$active'>$p</a>";
            }

            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo "<span class='pager-ellipsis'>...</span>";
                }
                echo "<a href='?{$pageParam}={$totalPages}&$href' class='pager-item'>{$totalPages}</a>";
            }
        }

        echo "<a href='{$nextHref}' class='{$nextClass}'" . ($nextDisabled ? " aria-disabled='true' tabindex='-1'" : "") . ">▶</a>";
        echo "</div>";
        echo "</nav>";
    }

    public function getCategoriesData()
    {
        header('Content-Type: application/json');

        $sortBy = $_GET['sort_by'] ?? 'category_id';
        $sortOrder = $_GET['sort_order'] ?? 'ASC';
        $categoryPage = isset($_GET['category_page']) ? max(1, (int)$_GET['category_page']) : 1;
        $categorySearch = $_GET['category_search'] ?? '';

        // Validate sort parameters
        $allowedSortColumns = ['category_id', 'category_name', 'created_date'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'category_id';
        }
        if (!in_array($sortOrder, ['ASC', 'DESC'])) {
            $sortOrder = 'ASC';
        }

        try {
            // Build query with search
            $categoryQuery = "SELECT * FROM categories WHERE is_deleted = 0";
            $categoryParams = [];
            if (!empty($categorySearch)) {
                $categoryQuery .= " AND (category_id LIKE ? OR category_name LIKE ? OR description LIKE ?)";
                $searchTerm = '%' . $categorySearch . '%';
                $categoryParams = [$searchTerm, $searchTerm, $searchTerm];
            }
            $categoryQuery .= " ORDER BY $sortBy $sortOrder";

            $categoryPager = new SimplePager($categoryQuery, $categoryParams, 10, $categoryPage);
            $categories = $categoryPager->result;

            // Get actual total count
            $categoryCountQuery = "SELECT COUNT(*) as total FROM categories WHERE is_deleted = 0";
            if (!empty($categorySearch)) {
                $categoryCountQuery .= " AND (category_id LIKE ? OR category_name LIKE ? OR description LIKE ?)";
            }
            $categoryTotalCount = $this->db->query($categoryCountQuery, $categoryParams)->fetch()['total'];

            $data = [];
            foreach ($categories as $categoryRow) {
                $data[] = [
                    'category_id' => $categoryRow['category_id'],
                    'category_name' => $categoryRow['category_name'],
                    'description' => $categoryRow['description'] ?: 'N/A',
                    'created_date' => date('Y-m-d H:i', strtotime($categoryRow['created_date']))
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $categoryPager->page,
                    'total_pages' => $categoryPager->page_count,
                    'total_items' => $categoryTotalCount
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getCategoryById()
    {
        header('Content-Type: application/json');

        $categoryId = $_GET['id'] ?? '';

        if (empty($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            return;
        }

        try {
            $category = $this->categoryModel->getCategoryById($categoryId);
            $data = [
                'category_id' => $category->getCategoryId(),
                'category_name' => $category->getCategoryName(),
                'description' => $category->getDescription() ?: 'N/A',
                'created_date' => date('Y-m-d H:i', strtotime($category->getCreatedDate()))
            ];
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addCategory()
    {
        header('Content-Type: application/json');

        $categoryName = $_POST['category_name'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty(trim($categoryName))) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            return;
        }

        try {
            $categoryId = $this->categoryModel->addCategory($categoryName, $description);
            echo json_encode(['success' => true, 'message' => 'Category added successfully', 'category_id' => $categoryId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateCategory()
    {
        header('Content-Type: application/json');

        $categoryId = $_POST['category_id'] ?? '';
        $categoryName = $_POST['category_name'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($categoryId) || empty(trim($categoryName))) {
            echo json_encode(['success' => false, 'message' => 'Category ID and name are required']);
            return;
        }

        try {
            $sql = "UPDATE categories SET category_name = ?, description = ? 
                    WHERE category_id = ? AND is_deleted = 0";
            Database::query($sql, [$categoryName, $description, $categoryId]);
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function updateProduct()
    {
        header('Content-Type: application/json');

        $blindboxId = $_POST['blindbox_id'] ?? '';
        $productName = $_POST['product_name'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $price = $_POST['price'] ?? '';
        $stockQuantity = $_POST['stock_quantity'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($blindboxId) || empty(trim($productName)) || empty($categoryId) || $price === '' || $stockQuantity === '') {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            return;
        }

        try {
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/ProductImage.php';
            require_once __DIR__ . '/../../models/BlindboxImage.php';

            $productModel = new Product();
            $productImageModel = new ProductImage();
            $blindboxImageModel = new BlindboxImage();

            // Update blindbox basic info
            $sql = "UPDATE blindbox 
                    SET product_name = ?, category_id = ?, price = ?, stock_quantity = ?, description = ? 
                    WHERE blindbox_id = ? AND is_deleted = 0";
            $this->blindboxModel->query($sql, [$productName, $categoryId, $price, $stockQuantity, $description, $blindboxId]);

            // Get category name for folder naming
            $category = $this->categoryModel->getCategoryById($categoryId);
            $categoryName = preg_replace('/[^a-zA-Z0-9_]/', '_', $category->getCategoryName());

            // Handle blindbox image replacement
            if (isset($_FILES['new_blindbox_image']) && $_FILES['new_blindbox_image']['error'] === UPLOAD_ERR_OK) {
                // Use proper folder structure
                $baseUploadDir = __DIR__ . '/../../assets/images/uploads/BB/';
                $blindboxDir = $baseUploadDir . $blindboxId . '/blindbox/';

                if (!file_exists($blindboxDir)) {
                    mkdir($blindboxDir, 0777, true);
                }

                $fileExtension = pathinfo($_FILES['new_blindbox_image']['name'], PATHINFO_EXTENSION);
                $newFileName = $categoryName . '_blindbox.' . $fileExtension;
                $uploadPath = $blindboxDir . $newFileName;

                if (move_uploaded_file($_FILES['new_blindbox_image']['tmp_name'], $uploadPath)) {
                    // Delete old blindbox image record (soft delete)
                    $deleteSql = "UPDATE blindbox_images SET is_deleted = 1 WHERE blindbox_id = ?";
                    $blindboxImageModel->query($deleteSql, [$blindboxId]);

                    // Insert new blindbox image
                    $imageUrl = 'assets/images/uploads/BB/' . $blindboxId . '/blindbox/' . $newFileName;
                    require_once __DIR__ . '/../../lib/IDGenerator.php';
                    $imageId = IDGenerator::blindboxImageID();
                    $insertSql = "INSERT INTO blindbox_images (image_id, blindbox_id, image_url, is_deleted) VALUES (?, ?, ?, 0)";
                    $blindboxImageModel->query($insertSql, [$imageId, $blindboxId, $imageUrl]);
                }
            }

            // Handle variants to delete
            if (!empty($_POST['variants_to_delete'])) {
                foreach ($_POST['variants_to_delete'] as $productId) {
                    // Soft delete product
                    $deleteProdSql = "UPDATE products SET is_deleted = 1 WHERE product_id = ?";
                    $productModel->query($deleteProdSql, [$productId]);

                    // Soft delete product images
                    $deleteImgSql = "UPDATE product_images SET is_deleted = 1 WHERE product_id = ?";
                    $productImageModel->query($deleteImgSql, [$productId]);
                }
            }

            // Handle existing variant updates (name and image changes)
            if (!empty($_POST['existing_variants'])) {
                require_once __DIR__ . '/../../lib/IDGenerator.php';

                $baseUploadDir = __DIR__ . '/../../assets/images/uploads/BB/';
                $productDir = $baseUploadDir . $blindboxId . '/product/';

                if (!file_exists($productDir)) {
                    mkdir($productDir, 0777, true);
                }

                // Clear all front images for this blindbox ONCE before processing variants
                $clearFrontSql = "UPDATE product_images pi INNER JOIN products p ON pi.product_id = p.product_id SET pi.is_front = 0 WHERE p.blindbox_id = ? AND pi.is_deleted = 0";
                $productImageModel->query($clearFrontSql, [$blindboxId]);

                foreach ($_POST['existing_variants'] as $index => $variantData) {
                    $productId = $variantData['product_id'] ?? '';
                    $variantName = $variantData['name'] ?? '';
                    $isFront = $variantData['is_front'] ?? '0';

                    if (empty($productId)) {
                        continue;
                    }

                    // Update variant name
                    if (!empty($variantName)) {
                        $updateVariantSql = "UPDATE products SET product_name = ? WHERE product_id = ? AND blindbox_id = ?";
                        $productModel->query($updateVariantSql, [$variantName, $productId, $blindboxId]);
                    }

                    // Set this variant as front if specified
                    if ($isFront == '1') {
                        $setFrontSql = "UPDATE product_images SET is_front = 1 
                                        WHERE product_id = ? AND is_deleted = 0 
                                        LIMIT 1";
                        $productImageModel->query($setFrontSql, [$productId]);
                    }

                    // Handle new image upload for existing variant
                    if (
                        isset($_FILES['existing_variants']['tmp_name'][$index]['image']) &&
                        $_FILES['existing_variants']['error'][$index]['image'] === UPLOAD_ERR_OK
                    ) {

                        // Get variant number for filename
                        $variantNumSql = "SELECT COUNT(*) + 1 as num FROM products WHERE blindbox_id = ? AND product_id <= ? AND is_deleted = 0 ORDER BY product_id";
                        $variantNumResult = $productModel->query($variantNumSql, [$blindboxId, $productId])->fetch();
                        $variantNumber = $variantNumResult['num'] ?? 1;

                        $fileExtension = pathinfo($_FILES['existing_variants']['name'][$index]['image'], PATHINFO_EXTENSION);
                        $newImgFileName = $categoryName . '_' . $variantNumber . '.' . $fileExtension;
                        $uploadPath = $productDir . $newImgFileName;

                        if (move_uploaded_file($_FILES['existing_variants']['tmp_name'][$index]['image'], $uploadPath)) {
                            // Soft delete old images for this variant
                            $deleteOldImgSql = "UPDATE product_images SET is_deleted = 1 WHERE product_id = ?";
                            $productImageModel->query($deleteOldImgSql, [$productId]);

                            // Insert new image
                            $imageUrl = 'assets/images/uploads/BB/' . $blindboxId . '/product/' . $newImgFileName;
                            $productImageId = IDGenerator::productImageID();
                            $insertImgSql = "INSERT INTO product_images (image_id, product_id, image_url, is_front, is_deleted) VALUES (?, ?, ?, ?, 0)";
                            $productImageModel->query($insertImgSql, [$productImageId, $productId, $imageUrl, $isFront]);
                        }
                    }
                }
            }

            // Handle variant updates (size/color changes) - legacy support
            if (!empty($_POST['variant_update'])) {
                foreach ($_POST['variant_update'] as $productId => $variantData) {
                    $size = $variantData['size'] ?? '';
                    $color = $variantData['color'] ?? '';

                    if (!empty($size) && !empty($color)) {
                        $productName = $size . ' - ' . $color;
                        $updateVariantSql = "UPDATE products SET product_name = ? WHERE product_id = ? AND blindbox_id = ?";
                        $productModel->query($updateVariantSql, [$productName, $productId, $blindboxId]);
                    }
                }
            }

            // Handle new variants
            if (!empty($_POST['new_variants'])) {
                require_once __DIR__ . '/../../lib/IDGenerator.php';

                // Use proper folder structure
                $baseUploadDir = __DIR__ . '/../../assets/images/uploads/BB/';
                $productDir = $baseUploadDir . $blindboxId . '/product/';

                if (!file_exists($productDir)) {
                    mkdir($productDir, 0777, true);
                }

                // Count existing variants for numbering
                $countSql = "SELECT COUNT(*) as count FROM products WHERE blindbox_id = ? AND is_deleted = 0";
                $countResult = $productModel->query($countSql, [$blindboxId])->fetch();
                $variantNumber = $countResult['count'] + 1;

                foreach ($_POST['new_variants'] as $index => $variantData) {
                    $variantName = $variantData['name'] ?? '';
                    $isFront = $variantData['is_front'] ?? '0';

                    if (empty($variantName)) {
                        continue;
                    }

                    $newProductId = IDGenerator::productID();

                    // Insert new variant
                    $insertVariantSql = "INSERT INTO products (product_id, blindbox_id, product_name, is_deleted) VALUES (?, ?, ?, 0)";
                    $productModel->query($insertVariantSql, [$newProductId, $blindboxId, $variantName]);

                    // Handle variant image (single image per variant)
                    if (
                        isset($_FILES['new_variants']['tmp_name'][$index]['image']) &&
                        $_FILES['new_variants']['error'][$index]['image'] === UPLOAD_ERR_OK
                    ) {

                        $fileExtension = pathinfo($_FILES['new_variants']['name'][$index]['image'], PATHINFO_EXTENSION);
                        $newImgFileName = $categoryName . '_' . $variantNumber . '.' . $fileExtension;
                        $uploadPath = $productDir . $newImgFileName;

                        if (move_uploaded_file($_FILES['new_variants']['tmp_name'][$index]['image'], $uploadPath)) {
                            $imageUrl = 'assets/images/uploads/BB/' . $blindboxId . '/product/' . $newImgFileName;
                            $productImageId = IDGenerator::productImageID();

                            $insertImgSql = "INSERT INTO product_images (image_id, product_id, image_url, is_front, is_deleted) 
                                             VALUES (?, ?, ?, ?, 0)";
                            $productImageModel->query($insertImgSql, [$productImageId, $newProductId, $imageUrl, $isFront]);
                        }
                    }

                    $variantNumber++;
                }
            }

            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    public function deleteProduct()
    {
        header('Content-Type: application/json');

        $blindboxId = $_POST['blindbox_id'] ?? '';

        if (empty($blindboxId)) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            return;
        }

        try {
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/ProductImage.php';
            require_once __DIR__ . '/../../models/BlindboxImage.php';

            $productModel = new Product();
            $productImageModel = new ProductImage();
            $blindboxImageModel = new BlindboxImage();

            // Soft delete blindbox
            $sqlBlindbox = "UPDATE blindbox SET is_deleted = 1 WHERE blindbox_id = ?";
            $this->blindboxModel->query($sqlBlindbox, [$blindboxId]);

            // Soft delete all products
            $sqlProducts = "UPDATE products SET is_deleted = 1 WHERE blindbox_id = ?";
            $productModel->query($sqlProducts, [$blindboxId]);

            // Soft delete all product images
            $sqlProductImages = "UPDATE product_images pi 
                                INNER JOIN products p ON pi.product_id = p.product_id 
                                SET pi.is_deleted = 1 
                                WHERE p.blindbox_id = ?";
            $productImageModel->query($sqlProductImages, [$blindboxId]);

            // Soft delete blindbox images
            $sqlBlindboxImages = "UPDATE blindbox_images SET is_deleted = 1 WHERE blindbox_id = ?";
            $blindboxImageModel->query($sqlBlindboxImages, [$blindboxId]);

            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }

    public function checkCategoryProducts()
    {
        header('Content-Type: application/json');

        $categoryId = $_GET['category_id'] ?? '';

        if (empty($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            return;
        }

        try {
            // Check if category has products
            $sql = "SELECT blindbox_id, product_name FROM blindbox WHERE category_id = ? AND is_deleted = 0";
            $stmt = Database::query($sql, [$categoryId]);
            $products = $stmt->fetchAll();

            $hasProducts = count($products) > 0;

            // Get all other categories for reassignment
            $categories = [];
            if ($hasProducts) {
                $sqlCategories = "SELECT category_id, category_name FROM categories WHERE is_deleted = 0 ORDER BY category_name";
                $categories = Database::fetchAll($sqlCategories);
            }

            echo json_encode([
                'success' => true,
                'hasProducts' => $hasProducts,
                'products' => $products,
                'categories' => $categories
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to check category: ' . $e->getMessage()]);
        }
    }

    public function reassignProducts()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $reassignments = $input['reassignments'] ?? [];

        if (empty($reassignments)) {
            echo json_encode(['success' => false, 'message' => 'No reassignments provided']);
            return;
        }

        try {
            // Update each product's category
            foreach ($reassignments as $blindboxId => $newCategoryId) {
                $sql = "UPDATE blindbox SET category_id = ? WHERE blindbox_id = ?";
                Database::query($sql, [$newCategoryId, $blindboxId]);
            }

            echo json_encode(['success' => true, 'message' => 'Products reassigned successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to reassign products: ' . $e->getMessage()]);
        }
    }

    public function deleteCategory()
    {
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true);
        $categoryId = $input['category_id'] ?? '';

        if (empty($categoryId)) {
            echo json_encode(['success' => false, 'message' => 'Category ID is required']);
            return;
        }

        try {
            // Check if category still has products
            $sql = "SELECT COUNT(*) as count FROM blindbox WHERE category_id = ? AND is_deleted = 0";
            $result = Database::fetch($sql, [$categoryId]);

            if ($result['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Category still has products assigned to it']);
                return;
            }

            // Soft delete category
            $sqlDelete = "UPDATE categories SET is_deleted = 1 WHERE category_id = ?";
            Database::query($sqlDelete, [$categoryId]);

            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category: ' . $e->getMessage()]);
        }
    }

    public function addProduct()
    {
        header('Content-Type: application/json');

        // Validate required fields
        $productName = $_POST['product_name'] ?? '';
        $categoryId = $_POST['category_id'] ?? '';
        $price = $_POST['price'] ?? '';
        $stockQuantity = $_POST['stock_quantity'] ?? '';
        $description = $_POST['description'] ?? '';
        $frontImageIndex = $_POST['front_image_index'] ?? 0;

        if (empty(trim($productName)) || empty($categoryId) || empty($price) || empty($stockQuantity)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            return;
        }

        // Validate blindbox image upload
        if (!isset($_FILES['blindbox_image']) || $_FILES['blindbox_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Blindbox image is required']);
            return;
        }

        // Validate variants
        if (!isset($_POST['variants']) || empty($_POST['variants'])) {
            echo json_encode(['success' => false, 'message' => 'At least one product variant is required']);
            return;
        }

        try {
            require_once __DIR__ . '/../../lib/IDGenerator.php';
            require_once __DIR__ . '/../../models/BlindboxImage.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/ProductImage.php';

            $blindboxModel = $this->blindboxModel;
            $blindboxImageModel = new BlindboxImage();
            $productModel = new Product();
            $productImageModel = new ProductImage();

            // Get category name for folder naming
            $category = $this->categoryModel->getCategoryById($categoryId);
            $categoryName = preg_replace('/[^a-zA-Z0-9_]/', '_', $category->getCategoryName());

            // Create blindbox
            $blindboxId = IDGenerator::blindboxID();
            $sql = "INSERT INTO blindbox (blindbox_id, category_id, product_name, price, stock_quantity, description, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $blindboxModel->query($sql, [$blindboxId, $categoryId, $productName, $price, $stockQuantity, $description]);

            // Create folder structure: BB/{blindbox_id}/blindbox and BB/{blindbox_id}/product
            $baseUploadDir = __DIR__ . '/../../assets/images/uploads/BB/';
            $blindboxDir = $baseUploadDir . $blindboxId . '/blindbox/';
            $productDir = $baseUploadDir . $blindboxId . '/product/';

            if (!is_dir($blindboxDir)) {
                mkdir($blindboxDir, 0777, true);
            }
            if (!is_dir($productDir)) {
                mkdir($productDir, 0777, true);
            }

            // Upload and save blindbox image
            $blindboxImageFile = $_FILES['blindbox_image'];
            $blindboxImageExt = pathinfo($blindboxImageFile['name'], PATHINFO_EXTENSION);
            $blindboxImageName = $categoryName . '_blindbox.' . $blindboxImageExt;
            $blindboxImagePath = $blindboxDir . $blindboxImageName;

            if (move_uploaded_file($blindboxImageFile['tmp_name'], $blindboxImagePath)) {
                $blindboxImageId = IDGenerator::blindboxImageID();
                $imageUrl = 'assets/images/uploads/BB/' . $blindboxId . '/blindbox/' . $blindboxImageName;
                $sqlImg = "INSERT INTO blindbox_images (image_id, blindbox_id, image_url, is_deleted) VALUES (?, ?, ?, 0)";
                $blindboxImageModel->query($sqlImg, [$blindboxImageId, $blindboxId, $imageUrl]);
            }

            // Process variants - get data from $_POST and $_FILES
            $variants = $_POST['variants'];
            $variantFiles = [];

            // Collect variant files from $_FILES (PHP reorganizes nested arrays)
            if (isset($_FILES['variants']['tmp_name'])) {
                foreach ($_FILES['variants']['tmp_name'] as $index => $fileData) {
                    if (isset($fileData['image']) && $_FILES['variants']['error'][$index]['image'] === UPLOAD_ERR_OK) {
                        $variantFiles[$index] = [
                            'name' => $_FILES['variants']['name'][$index]['image'],
                            'tmp_name' => $_FILES['variants']['tmp_name'][$index]['image'],
                            'error' => $_FILES['variants']['error'][$index]['image'],
                            'size' => $_FILES['variants']['size'][$index]['image'],
                            'type' => $_FILES['variants']['type'][$index]['image']
                        ];
                    }
                }
            }

            // Process variants - ensure front image is always Variant 1
            $processedVariants = [];
            foreach ($variants as $index => $variant) {
                $variantName = trim($variant['name'] ?? '');
                $processedVariants[$index] = [
                    'name' => $variantName,
                    'is_front' => ($index == $frontImageIndex),
                    'has_file' => isset($variantFiles[$index])
                ];
            }

            // Reorder so front image is first
            $orderedVariants = [];
            $nonFrontVariants = [];
            foreach ($processedVariants as $index => $variant) {
                if ($variant['is_front']) {
                    array_unshift($orderedVariants, ['index' => $index, 'data' => $variant]);
                } else {
                    $nonFrontVariants[] = ['index' => $index, 'data' => $variant];
                }
            }
            $orderedVariants = array_merge($orderedVariants, $nonFrontVariants);

            // 5. Create products and upload variant images
            $variantNumber = 1;
            foreach ($orderedVariants as $orderedVariant) {
                $originalIndex = $orderedVariant['index'];
                $variant = $orderedVariant['data'];

                // Set variant name - if blank, use "CategoryName - Variant X"
                $variantName = $variant['name'];
                if (empty($variantName)) {
                    $variantName = $category->getCategoryName() . ' - Variant ' . $variantNumber;
                }

                // Create product
                $productId = IDGenerator::productID();
                $sqlProduct = "INSERT INTO products (product_id, blindbox_id, product_name, is_deleted) VALUES (?, ?, ?, 0)";
                $productModel->query($sqlProduct, [$productId, $blindboxId, $variantName]);

                // Upload variant image with naming: CategoryName_1, CategoryName_2, etc.
                if (isset($variantFiles[$originalIndex])) {
                    $variantImageFile = $variantFiles[$originalIndex];

                    $variantImageExt = pathinfo($variantImageFile['name'], PATHINFO_EXTENSION);
                    $variantImageName = $categoryName . '_' . $variantNumber . '.' . $variantImageExt;
                    $variantImagePath = $productDir . $variantImageName;

                    if (move_uploaded_file($variantImageFile['tmp_name'], $variantImagePath)) {
                        $productImageId = IDGenerator::productImageID();
                        $variantImageUrl = 'assets/images/uploads/BB/' . $blindboxId . '/product/' . $variantImageName;
                        $isFront = $variant['is_front'] ? 1 : 0;

                        $sqlProdImg = "INSERT INTO product_images (image_id, product_id, image_url, is_front, is_deleted) VALUES (?, ?, ?, ?, 0)";
                        $productImageModel->query($sqlProdImg, [$productImageId, $productId, $variantImageUrl, $isFront]);
                    }
                }

                $variantNumber++;
            }

            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        } catch (Exception $e) {
            error_log("Error adding product: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()]);
        }
    }

    public function getBlindboxById()
    {
        header('Content-Type: application/json');

        $blindboxId = $_GET['id'] ?? '';

        if (empty($blindboxId)) {
            echo json_encode(['success' => false, 'message' => 'Product ID is required']);
            return;
        }

        try {
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/ProductImage.php';
            require_once __DIR__ . '/../../models/BlindboxImage.php';

            // Query blindbox directly
            $blindboxSql = "SELECT * FROM blindbox WHERE blindbox_id = ? AND is_deleted = 0";
            $blindboxResult = $this->blindboxModel->query($blindboxSql, [$blindboxId])->fetchAll();

            if (empty($blindboxResult)) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }

            $blindbox = $blindboxResult[0];
            $productModel = new Product();
            $productImageModel = new ProductImage();
            $blindboxImageModel = new BlindboxImage();

            // Get category name
            $categoryName = 'Unknown';
            try {
                $cat = $this->categoryModel->getCategoryById($blindbox['category_id']);
                $categoryName = $cat->getCategoryName();
            } catch (Exception $e) {
                error_log("Failed to retrieve category name for category_id {$blindbox['category_id']}: " . $e->getMessage());
            }

            // Get all product variants
            $productsSql = "SELECT * FROM products WHERE blindbox_id = ? AND is_deleted = 0";
            $products = $productModel->query($productsSql, [$blindboxId])->fetchAll();

            $variants = [];
            foreach ($products as $product) {
                // Get product images
                $imagesSql = "SELECT * FROM product_images WHERE product_id = ? AND is_deleted = 0";
                $images = $productImageModel->query($imagesSql, [$product['product_id']])->fetchAll();

                $imageUrls = [];
                $isFront = 0;
                foreach ($images as $img) {
                    // Add cache-busting timestamp to image URL
                    $imageUrl = $img['image_url'] . '?t=' . time();
                    $imageUrls[] = $imageUrl;
                    if ($img['is_front'] == 1) {
                        $isFront = 1;
                    }
                }

                // Parse size and color from variant name (e.g., "M - Red")
                $variantParts = explode(' - ', $product['product_name']);
                $size = $variantParts[0] ?? $product['product_name'];
                $color = $variantParts[1] ?? '';

                $variants[] = [
                    'product_id' => $product['product_id'],
                    'variant_name' => $product['product_name'],
                    'size' => $size,
                    'color' => $color,
                    'images' => $imageUrls,
                    'is_front' => $isFront
                ];
            }

            // Get blindbox image
            $blindboxImagesSql = "SELECT * FROM blindbox_images WHERE blindbox_id = ? AND is_deleted = 0";
            $blindboxImages = $blindboxImageModel->query($blindboxImagesSql, [$blindboxId])->fetchAll();
            // Add cache-busting timestamp to blindbox image URL
            $blindboxImageUrl = !empty($blindboxImages) ? $blindboxImages[0]['image_url'] . '?t=' . time() : '';

            $data = [
                'blindbox_id' => $blindbox['blindbox_id'],
                'product_name' => $blindbox['product_name'],
                'category_id' => $blindbox['category_id'],
                'category_name' => $categoryName,
                'price' => $blindbox['price'],
                'stock_quantity' => $blindbox['stock_quantity'],
                'description' => $blindbox['description'] ?: 'N/A',
                'status' => $blindbox['status'],
                'blindbox_image' => $blindboxImageUrl,
                'variants' => $variants,
                'created_date' => date('Y-m-d H:i', strtotime($blindbox['created_date']))
            ];

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getProductsData()
    {
        header('Content-Type: application/json');

        $productSortBy = $_GET['product_sort_by'] ?? 'blindbox_id';
        $productSortOrder = $_GET['product_sort_order'] ?? 'ASC';
        $statusFilter = $_GET['status_filter'] ?? 'all';
        $productPage = isset($_GET['product_page']) ? max(1, (int)$_GET['product_page']) : 1;
        $productSearch = $_GET['product_search'] ?? '';

        // Validate product sort parameters
        $allowedProductSortColumns = ['blindbox_id', 'product_name', 'category_id', 'price', 'stock_quantity'];
        if (!in_array($productSortBy, $allowedProductSortColumns)) {
            $productSortBy = 'blindbox_id';
        }
        if (!in_array($productSortOrder, ['ASC', 'DESC'])) {
            $productSortOrder = 'ASC';
        }

        // Validate status filter
        if (!in_array($statusFilter, ['all', 'active', 'inactive'])) {
            $statusFilter = 'all';
        }

        try {
            // Build query with filters
            $productQuery = "SELECT * FROM blindbox WHERE is_deleted = 0";
            $productParams = [];
            if ($statusFilter === 'active') {
                $productQuery .= " AND stock_quantity > 0";
            } elseif ($statusFilter === 'inactive') {
                $productQuery .= " AND stock_quantity = 0";
            }
            if (!empty($productSearch)) {
                $productQuery .= " AND (blindbox_id LIKE ? OR product_name LIKE ? OR category_id LIKE ?)";
                $searchTerm = '%' . $productSearch . '%';
                $productParams = [$searchTerm, $searchTerm, $searchTerm];
            }
            $productQuery .= " ORDER BY $productSortBy $productSortOrder";

            $productPager = new SimplePager($productQuery, $productParams, 10, $productPage);
            $blindboxes = $productPager->result;

            // Get actual total count
            $productCountQuery = "SELECT COUNT(*) as total FROM blindbox WHERE is_deleted = 0";
            if ($statusFilter === 'active') {
                $productCountQuery .= " AND stock_quantity > 0";
            } elseif ($statusFilter === 'inactive') {
                $productCountQuery .= " AND stock_quantity = 0";
            }
            if (!empty($productSearch)) {
                $productCountQuery .= " AND (blindbox_id LIKE ? OR product_name LIKE ? OR category_id LIKE ?)";
            }
            $productTotalCount = $this->db->query($productCountQuery, $productParams)->fetch()['total'];

            $data = [];
            foreach ($blindboxes as $blindboxRow) {
                // Get category name
                $categoryName = 'Unknown';
                try {
                    $cat = $this->categoryModel->getCategoryById($blindboxRow['category_id']);
                    if ($cat) {
                        $categoryName = $cat->getCategoryName();
                    }
                } catch (Exception $e) {
                    error_log("Failed to retrieve category name for category_id {$blindboxRow['category_id']}: " . $e->getMessage());
                }

                $data[] = [
                    'blindbox_id' => $blindboxRow['blindbox_id'],
                    'product_name' => $blindboxRow['product_name'],
                    'category_name' => $categoryName,
                    'price' => number_format($blindboxRow['price'], 2),
                    'stock_quantity' => $blindboxRow['stock_quantity'],
                    'status' => $blindboxRow['stock_quantity'] > 0 ? 'active' : 'out_of_stock'
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data,
                'pagination' => [
                    'current_page' => $productPager->page,
                    'total_pages' => $productPager->page_count,
                    'total_items' => $productTotalCount
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}

// Handle AJAX requests only for admin product-specific actions to avoid intercepting unrelated requests
$action = $_GET['action'] ?? $_POST['action'] ?? null;
$validActions = ['get_categories', 'get_category', 'get_products', 'get_product', 'add_category', 'update_category', 'update_product', 'delete_product', 'add_product', 'check_category_products', 'reassign_products', 'delete_category'];
if ($action !== null && in_array($action, $validActions, true)) {
    $controller = new AdminProductController();

    switch ($action) {
        case 'get_categories':
            $controller->getCategoriesData();
            break;
        case 'get_category':
            $controller->getCategoryById();
            break;
        case 'get_products':
            $controller->getProductsData();
            break;
        case 'get_product':
            $controller->getBlindboxById();
            break;
        case 'add_category':
            $controller->addCategory();
            break;
        case 'update_category':
            $controller->updateCategory();
            break;
        case 'update_product':
            $controller->updateProduct();
            break;
        case 'delete_product':
            $controller->deleteProduct();
            break;
        case 'add_product':
            $controller->addProduct();
            break;
        case 'check_category_products':
            $controller->checkCategoryProducts();
            break;
        case 'reassign_products':
            $controller->reassignProducts();
            break;
        case 'delete_category':
            $controller->deleteCategory();
            break;
    }
    exit;
}
