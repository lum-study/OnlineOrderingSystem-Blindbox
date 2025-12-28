<?php
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../models/Blindbox.php';
require_once __DIR__ . '/../../../models/BlindboxImage.php';
require_once __DIR__ . '/../../../models/Category.php';
require_once __DIR__ . '/../../../models/Product.php';
require_once __DIR__ . '/../../../models/ProductImage.php';

// Set default quantity for HtmlHelper
$GLOBALS['quantity'] = '1';

// Get ID from URL
$blindboxId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$blindboxId) {
    header('Location: ' . BASE_URL . 'views/pages/products/product_list.php');
    exit;
}

// Initialize models
$blindboxModel = new Blindbox();
$blindboxImageModel = new BlindboxImage();
$productModel = new Product();
$productImageModel = new ProductImage();

try {
    // Fetch blindbox details
    $blindbox = $blindboxModel->getBlindboxById($blindboxId);

    // Fetch blindbox images (main images)
    $blindboxImages = $blindboxImageModel->getImagesByBlindboxId($blindboxId);

    // Fetch variants (products in the box)
    $variants = $productModel->getProductsByBlindboxId($blindboxId);

    // Sort variants by their image filenames numerically
    usort($variants, function ($a, $b) use ($productImageModel) {
        $imgsA = $productImageModel->getImagesByProductId($a->getProductId());
        $imgsB = $productImageModel->getImagesByProductId($b->getProductId());

        if (empty($imgsA) || empty($imgsB)) {
            return 0;
        }

        $urlA = $imgsA[0]->getImageUrl();
        $urlB = $imgsB[0]->getImageUrl();

        // Extract numbers from filenames
        preg_match('/_(\d+)\.(jpg|png|jpeg|gif)$/i', $urlA, $matchA);
        preg_match('/_(\d+)\.(jpg|png|jpeg|gif)$/i', $urlB, $matchB);

        $numA = isset($matchA[1]) ? (int)$matchA[1] : 0;
        $numB = isset($matchB[1]) ? (int)$matchB[1] : 0;

        return $numA - $numB;
    });

    // Prepare variant images - collect ALL images from all sorted variants
    $variantImages = [];
    $variantImagesForDisplay = []; // For the variants grid display
    foreach ($variants as $variant) {
        $imgs = $productImageModel->getImagesByProductId($variant->getProductId());
        if (!empty($imgs)) {
            // Store first image for variants grid
            $variantImagesForDisplay[$variant->getProductId()] = $imgs[0]->getImageUrl();
            // Store all images for thumbnail gallery
            foreach ($imgs as $img) {
                $variantImages[] = $img->getImageUrl();
            }
        }
    }
} catch (Exception $e) {
    error_log("Error loading product detail: " . $e->getMessage());
    header('Location: ' . BASE_URL . 'views/pages/products/product_list.php');
    exit;
}

include __DIR__ . '/../../../includes/header.php';

// Fetch category AFTER header to avoid variable conflicts with navbar
$categoryModel = new Category();
$productCategory = $categoryModel->getCategoryById($blindbox->getCategoryId());
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/product.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/product_detail.css">

<div class="container product-detail-container">
    <div class="product-detail-layout">

        <!-- Left: Image Gallery -->
        <div class="gallery-container">
            <?php
            // Determine main image - prioritize product images with is_front=1
            $mainImageUrl = BASE_URL . 'assets/images/uploads/404.png';

            // Check variant images first (product images with is_front flag)
            if (!empty($variantImages)) {
                $firstVariantImg = reset($variantImages);
                $mainImageUrl = BASE_URL . ltrim($firstVariantImg, '/');
            } elseif (!empty($blindboxImages)) {
                $mainImageUrl = BASE_URL . ltrim($blindboxImages[0]->getImageUrl(), '/');
            }
            ?>
            <div class="main-image-frame">
                <img id="mainImage" src="<?= htmlspecialchars($mainImageUrl) ?>" alt="Main Product Image">
            </div>

            <div class="thumbnail-container">
                <button class="thumbnail-arrow thumbnail-arrow-left" onclick="scrollThumbnails(-1)" aria-label="Previous">&lt;</button>
                <div class="thumbnail-list" id="thumbnailList">
                    <!-- Variant Images First (with is_front ordering) -->
                    <?php foreach ($variantImages as $vImg): ?>
                        <div class="thumbnail"
                            onclick="changeImage('<?= BASE_URL . ltrim($vImg, '/') ?>')"
                            onmouseover="changeImage('<?= BASE_URL . ltrim($vImg, '/') ?>')">
                            <img src="<?= BASE_URL . ltrim($vImg, '/') ?>" alt="Variant Thumbnail">
                        </div>
                    <?php endforeach; ?>

                    <!-- Blindbox Images -->
                    <?php foreach ($blindboxImages as $img): ?>
                        <div class="thumbnail"
                            onclick="changeImage('<?= BASE_URL . ltrim($img->getImageUrl(), '/') ?>')"
                            onmouseover="changeImage('<?= BASE_URL . ltrim($img->getImageUrl(), '/') ?>')">
                            <img src="<?= BASE_URL . ltrim($img->getImageUrl(), '/') ?>" alt="Thumbnail">
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="thumbnail-arrow thumbnail-arrow-right" onclick="scrollThumbnails(1)" aria-label="Next">&gt;</button>
            </div>
        </div>

        <!-- Right: Product Info -->
        <div class="product-info-section">
            <div class="detail-category brand-font">
                <?= htmlspecialchars($productCategory->getCategoryName()) ?>
            </div>

            <h1 class="detail-title brand-font">
                <?= htmlspecialchars($blindbox->getProductName()) ?>
            </h1>

            <div class="detail-price brand-font">
                RM <?= number_format($blindbox->getPrice(), 2) ?>
                <?php if ($blindbox->getStockQuantity() > 0): ?>
                    <span class="stock-status">IN STOCK</span>
                <?php else: ?>
                    <span class="stock-status out">SOLD OUT</span>
                <?php endif; ?>
            </div>

            <div class="stock-info">
                <?php if ($blindbox->getStockQuantity() > 0): ?>
                    <?= $blindbox->getStockQuantity() ?> <?= $blindbox->getStockQuantity() == 1 ? 'item' : 'items' ?> available
                <?php else: ?>
                    Out of stock
                <?php endif; ?>
            </div>

            <div class="detail-description">
                <?= nl2br(htmlspecialchars($blindbox->getDescription())) ?>
            </div>

            <!-- Variants Display -->
            <?php if (!empty($variants)): ?>
                <div class="variants-section">
                    <div class="variants-title">CONTAINS ONE OF THE FOLLOWING:</div>
                    <div class="variants-grid">
                        <?php foreach ($variants as $variant): ?>
                            <?php
                            $vImgUrl = isset($variantImagesForDisplay[$variant->getProductId()])
                                ? BASE_URL . ltrim($variantImagesForDisplay[$variant->getProductId()], '/')
                                : BASE_URL . 'assets/images/uploads/404.png';
                            ?>
                            <div class="variant-item" title="<?= htmlspecialchars($variant->getProductName()) ?>">
                                <img src="<?= htmlspecialchars($vImgUrl) ?>" alt="<?= htmlspecialchars($variant->getProductName()) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add to Cart -->
            <div class="action-buttons">
                <div class="qty-selector">
                    <button type="button" class="qty-btn" onclick="updateQty(-1)">-</button>
                    <?php html_text('quantity', 'type="number" min="1" max="' . $blindbox->getStockQuantity() . '" class="qty-input" readonly', 'qtyInput'); ?>
                    <button type="button" class="qty-btn" onclick="updateQty(1)">+</button>
                </div>

                <button type="button" id="addToCartBtn" class="add-cart-btn"
                    data-blindbox-id="<?= htmlspecialchars($blindboxId) ?>"
                    <?= $blindbox->getStockQuantity() <= 0 ? 'disabled' : '' ?>>
                    <?= $blindbox->getStockQuantity() > 0 ? 'ADD TO CART' : 'SOLD OUT' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/toast.js"></script>
<script src="<?= BASE_URL ?>assets/js/product_detail.js"></script>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>