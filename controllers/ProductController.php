<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Blindbox.php';
require_once __DIR__ . '/../models/BlindboxImage.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/OrderItem.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductImage.php';

class ProductController
{
    private $blindboxModel;
    private $blindboxImageModel;
    private $categoryModel;
    private $productModel;
    private $productImageModel;
    private $orderItemModel;

    public function __construct()
    {
        $this->blindboxModel = new Blindbox();
        $this->blindboxImageModel = new BlindboxImage();
        $this->categoryModel = new Category();
        $this->productModel = new Product();
        $this->productImageModel = new ProductImage();
        $this->orderItemModel = new OrderItem();
    }

    /**
     * Returns data ready for rendering
     */
    public function getProducts($minPrice = null, $maxPrice = null, $search = '', $category = null, $sort = 'newest', $page = 1, $limit = 12)
    {
        $categories = $this->categoryModel->getAllCategories();
        $categoryMap = [];
        foreach ($categories as $c) {
            $categoryMap[$c->getCategoryId()] = $c->getCategoryName();
        }

        // Use pager for everything
        $pager = $this->blindboxModel->getBlindboxesFiltered($minPrice, $maxPrice, $search, $category, $sort, $page, $limit);
        $rows = $pager->result;

        $blindboxesForView = [];
        foreach ($rows as $b) {
            $blindboxId = $b['blindbox_id'] ?? $b->getBlindboxId(); // depends on fetch mode
            $entity = new BlindboxEntity($b);
            $imageUrl = BASE_URL . 'assets/images/uploads/404.png';

            try {
                // Get product images (with is_front flag)
                $products = $this->productModel->getProductsByBlindboxId($blindboxId);
                if (!empty($products)) {
                    $productImages = $this->productImageModel->getImagesByProductId($products[0]->getProductId());
                    if (!empty($productImages)) {
                        // getImagesByProductId now orders by is_front DESC, so first image is the front image
                        $imageUrl = BASE_URL . ltrim($productImages[0]->getImageUrl(), '/');
                    }
                }
                // Fallback to blindbox images if no product images
                if ($imageUrl === BASE_URL . 'assets/images/uploads/404.png') {
                    $images = $this->blindboxImageModel->getImagesByBlindboxId($blindboxId);
                    if (!empty($images)) {
                        $imageUrl = BASE_URL . ltrim($images[0]->getImageUrl(), '/');
                    }
                }
            } catch (Exception $e) {
                error_log("Error fetching images for blindbox {$blindboxId}: " . $e->getMessage());
            }

            $blindboxesForView[] = [
                'entity' => $entity,
                'imageUrl' => $imageUrl,
                'categoryName' => $categoryMap[$entity->getCategoryId()] ?? 'Unknown'
            ];
        }

        return [
            'blindboxes' => $blindboxesForView,
            'pager' => $pager,
            'categories' => $categories,
            'categoryMap' => $categoryMap
        ];
    }

    public function getPriceRange($category = null)
    {
        return $this->blindboxModel->getPriceRange($category);
    }
}
