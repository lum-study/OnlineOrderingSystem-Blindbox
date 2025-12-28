<?php
require_once __DIR__ . "/../../../controllers/ProductController.php";
require_once __DIR__ . "/../../../enum/SortOption.php";
$controller = new ProductController();

// Get current page from query
$page = isset($_GET['page']) ? max((int) $_GET['page'], 1) : 1;

// Get filters
$search = $_GET['search'] ?? '';
$GLOBALS['search'] = $search;
$categoryFilter = $_GET['category'] ?? null;
$sort = $_GET['sort'] ?? 'newest';

$priceRange = $controller->getPriceRange($categoryFilter);
$minPrice = floor($priceRange['min_price']) ?? 0;
$maxPrice = ceil($priceRange['max_price']) ?? 1000;
$selectedMinPrice = isset($_GET['min_price']) ? (int)$_GET['min_price'] : $minPrice;
$selectedMaxPrice = isset($_GET['max_price']) ? (int)$_GET['max_price'] : $maxPrice;

// Fetch products with pagination
$data = $controller->getProducts($selectedMinPrice, $selectedMaxPrice, $search, $categoryFilter, $sort, $page, 12);

$blindboxes = $data['blindboxes'];
$pager = $data['pager'];
$categories = $data['categories'];
$categoryMap = $data['categoryMap'];

include __DIR__ . '/../../../includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/product.css">

<div class="container shop-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Search -->
        <div class="sidebar-section">
            <h3 class="brand-font">SEARCH</h3>
            <form action="" method="GET" class="search-form">
                <?php html_text('search', 'value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search products..." class="search-input"') ?>
                <button type="submit" class="search-btn">></button>

                <!-- Preserve category & sort -->
                <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            </form>
        </div>

        <!-- Categories -->
        <div class="sidebar-section">
            <h3 class="brand-font">CATEGORIES</h3>
            <ul class="category-list">
                <li>
                    <a href="?sort=<?= urlencode($sort) ?>">All Categories</a>
                </li>
                <?php foreach ($categories as $category): ?>
                    <li>
                        <a href="?category=<?= urlencode($category->getCategoryId()) ?>&sort=<?= urlencode($sort) ?>">
                            <?= htmlspecialchars($category->getCategoryName()) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="sidebar-section"><!-- Sort Bar -->
            <h3 class="brand-font">SORT BY:</h3>
            <form method="GET" class="sort-bar">
                <select class="sort-select" name="sort" onchange="this.form.submit()">
                    <?php foreach (SortOption::cases() as $option): ?>
                        <option value="<?= $option->value ?>" <?= ($sort === $option->value) ? 'selected' : '' ?>>
                            <?= $option->title() ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <!-- Preserve filter -->
                <input type="hidden" name="min_price" value="<?= htmlspecialchars($selectedMinPrice) ?>">
                <input type="hidden" name="max_price" value="<?= htmlspecialchars($selectedMaxPrice) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
            </form>
        </div>

        <div class="sidebar-section">
            <h3 class="brand-font">Price Range:</h3>

            <div class="price-range-container">
                <!-- Display current price range -->
                <div class="price-range-values">
                    <span class="price-range-min-value">$<span id="price-min-value"><?= $selectedMinPrice ?></span></span>
                    <span class="price-range-max-value">$<span id="price-max-value"><?= $selectedMaxPrice ?></span></span>
                </div>

                <!-- Custom range slider -->
                <div class="price-range-slider" id="price-range-slider">
                    <div class="price-range-track" id="price-range-track"></div>
                    <div class="price-range-thumb price-range-min-thumb" id="price-min-thumb"></div>
                    <div class="price-range-thumb price-range-max-thumb" id="price-max-thumb"></div>
                </div>

                <!-- Form to submit price range -->
                <form method="GET" class="price-range-form" id="price-range-form">
                    <input type="hidden" name="min_price" id="min_price" value="<?= $selectedMinPrice ?>">
                    <input type="hidden" name="max_price" id="max_price" value="<?= $selectedMaxPrice ?>">

                    <!-- Preserve other parameters -->
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                    <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

                    <button type="submit" class="price-range-apply">Apply Filter</button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Products Section -->
        <section class="shop-section">
            <div class="shop-header">
                <h2 class="brand-font">
                    <?= !empty($categoryFilter) && isset($categoryMap[$categoryFilter])
                        ? htmlspecialchars($categoryMap[$categoryFilter])
                        : "All Categories" ?>
                </h2>
                <span>DISPLAYING <?= count($blindboxes) ?> ITEMS</span>
            </div>

            <?php if (!empty($blindboxes)): ?>
                <div class="product-grid">
                    <?php foreach ($blindboxes as $item): ?>
                        <?php
                        $blindbox = $item['entity'];
                        $imageUrl = $item['imageUrl'];
                        $categoryName = $item['categoryName'];

                        $description = $blindbox->getDescription();
                        if (strlen($description) > 50) {
                            $description = substr($description, 0, 50) . '...';
                        }
                        ?>
                        <a href="<?= BASE_URL ?>views/pages/products/product_detail.php?id=<?= urlencode($blindbox->getBlindboxId()) ?>"
                            class="product-card <?= $blindbox->getStockQuantity() <= 0 ? 'sold-out' : '' ?>">
                            <div class="card-image">
                                <span class="card-category-badge"><?= htmlspecialchars($categoryName) ?></span>
                                <img src="<?= htmlspecialchars($imageUrl) ?>"
                                    alt="<?= htmlspecialchars($blindbox->getProductName()) ?>"
                                    onerror="this.src='<?= BASE_URL ?>assets/images/uploads/404.png'">
                                <?php if ($blindbox->getStockQuantity() <= 0): ?>
                                    <div class="sold-out-overlay">
                                        <span class="sold-out-text brand-font">SOLD OUT</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-details">
                                <div>
                                    <h3 class="card-title brand-font"><?= htmlspecialchars($blindbox->getProductName()) ?></h3>
                                    <p class="card-desc"><?= htmlspecialchars($description) ?></p>
                                </div>
                                <span class="card-price">RM <?= number_format($blindbox->getPrice(), 2) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-products text-center">
                    <h2 class="brand-font">VOID DETECTED</h2>
                    <p>No collections found in the current timeline.</p>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <?php if (!empty($pager) && $pager->page_count > 1): ?>
                <?php $pager->html("min_price=$selectedMinPrice&max_price=$selectedMaxPrice&search=$search&sort=$sort&category=$categoryFilter"); ?>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include __DIR__ . '/../../../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const slider = document.getElementById('price-range-slider');
        const track = document.getElementById('price-range-track');
        const minThumb = document.getElementById('price-min-thumb');
        const maxThumb = document.getElementById('price-max-thumb');
        const minValue = document.getElementById('price-min-value');
        const maxValue = document.getElementById('price-max-value');
        const minInput = document.getElementById('min_price');
        const maxInput = document.getElementById('max_price');

        // Configuration
        const minPrice = <?= (int)$minPrice ?>;
        const maxPrice = <?= (int)$maxPrice ?>;
        let currentMin = <?= (int)$selectedMinPrice ?>;
        let currentMax = <?= (int)$selectedMaxPrice ?>;

        // Update thumb positions based on current values
        function updateThumbPositions() {
            const sliderWidth = slider.offsetWidth;
            const minPercent = ((currentMin - minPrice) / (maxPrice - minPrice)) * 100;
            const maxPercent = ((currentMax - minPrice) / (maxPrice - minPrice)) * 100;

            minThumb.style.left = `${minPercent + 5}%`;
            maxThumb.style.left = `${maxPercent - 5}%`;
            track.style.left = `${minPercent + 5}%`;
            track.style.width = `${maxPercent - minPercent - 10}%`;

            // Update display values
            minValue.textContent = currentMin;
            maxValue.textContent = currentMax;

            // Update hidden inputs
            minInput.value = currentMin;
            maxInput.value = currentMax;
        }

        // Initialize positions
        updateThumbPositions();

        // Function to get value from position
        function getValueFromPosition(position, sliderRect) {
            let percentage = ((position - sliderRect.left) / sliderRect.width) * 100;
            percentage = Math.max(0, Math.min(100, percentage));
            return Math.round(minPrice + (percentage / 100) * (maxPrice - minPrice));
        }

        // Drag functionality
        let activeThumb = null;

        function startDrag(thumb) {
            activeThumb = thumb;
            thumb.classList.add('active');
            document.addEventListener('mousemove', onDrag);
            document.addEventListener('mouseup', stopDrag);
            document.addEventListener('touchmove', onDragTouch, {
                passive: false
            });
            document.addEventListener('touchend', stopDrag);
        }

        function stopDrag() {
            if (activeThumb) {
                activeThumb.classList.remove('active');
                activeThumb = null;
            }
            document.removeEventListener('mousemove', onDrag);
            document.removeEventListener('mouseup', stopDrag);
            document.removeEventListener('touchmove', onDragTouch);
            document.removeEventListener('touchend', stopDrag);
        }

        function onDrag(e) {
            if (!activeThumb) return;
            e.preventDefault();

            const sliderRect = slider.getBoundingClientRect();
            let position = e.clientX;

            updateThumbValue(position, sliderRect);
        }

        function onDragTouch(e) {
            if (!activeThumb) return;
            e.preventDefault();

            const sliderRect = slider.getBoundingClientRect();
            let position = e.touches[0].clientX;

            updateThumbValue(position, sliderRect);
        }

        function updateThumbValue(position, sliderRect) {
            let value = getValueFromPosition(position, sliderRect);

            if (activeThumb === minThumb) {
                // Ensure min doesn't go below minPrice and doesn't exceed max
                value = Math.max(minPrice, Math.min(value, currentMax - 5));
                currentMin = value;
            } else if (activeThumb === maxThumb) {
                // Ensure max doesn't exceed maxPrice and doesn't go below min
                value = Math.min(maxPrice, Math.max(value, currentMin + 5));
                currentMax = value;
            }

            updateThumbPositions();
        }

        // Event listeners for mouse
        minThumb.addEventListener('mousedown', (e) => {
            e.preventDefault();
            startDrag(minThumb);
        });

        maxThumb.addEventListener('mousedown', (e) => {
            e.preventDefault();
            startDrag(maxThumb);
        });

        // Event listeners for touch
        minThumb.addEventListener('touchstart', (e) => {
            e.preventDefault();
            startDrag(minThumb);
        });

        maxThumb.addEventListener('touchstart', (e) => {
            e.preventDefault();
            startDrag(maxThumb);
        });

        // Click on track to move thumbs
        slider.addEventListener('click', (e) => {
            const sliderRect = slider.getBoundingClientRect();
            const position = e.clientX || e.touches[0].clientX;
            const clickValue = getValueFromPosition(position, sliderRect);
            const clickPercent = ((clickValue - minPrice) / (maxPrice - minPrice)) * 100;
            const minPercent = parseFloat(minThumb.style.left);
            const maxPercent = parseFloat(maxThumb.style.left);

            // Determine which thumb to move based on proximity
            const distToMin = Math.abs(clickPercent - minPercent);
            const distToMax = Math.abs(clickPercent - maxPercent);

            if (distToMin < distToMax) {
                currentMin = Math.max(minPrice, Math.min(clickValue, currentMax - 10));
            } else {
                currentMax = Math.min(maxPrice, Math.max(clickValue, currentMin + 10));
            }

            updateThumbPositions();
        });

        // Prevent form submission on Enter key in slider
        document.addEventListener('keydown', (e) => {
            if (e.target === slider && e.key === 'Enter') {
                e.preventDefault();
            }
        });
    });
</script>