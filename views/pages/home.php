<?php
require_once __DIR__ . '/../../config/init.php';
include __DIR__ . '/../../includes/header.php';
?>

<section class="hero">
    <div class="bg-grid"></div>
    <div class="hero-content">
        <p class="incoming-msg">/// INCOMING TRANSMISSION ///</p>
        <h1 class="hero-title">UNLOCK <br> THE VOID</h1>
        <p class="hero-desc">
            Curated mystery collectibles. No refunds. No regrets.
            The box contains everything and nothing until you open it.
        </p>
        <a href="#shop" class="cta-btn brand-font">
            GET BLIND
        </a>
    </div>
    <div class="marquee-container">
        <div class="marquee-content">
            LIMITED STOCK /// SERIES 01: NOIR /// DO NOT CONSUME /// BLINDEDOOS ORIGINAL /// RARE CHASE VARIANT 1:100
            ///
            LIMITED STOCK /// SERIES 01: NOIR /// DO NOT CONSUME /// BLINDEDOOS ORIGINAL /// RARE CHASE VARIANT 1:100
            ///
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="stat-box">
        <h3 class="brand-font">100%</h3>
        <p>AUTHENTIC GOODS</p>
    </div>
    <div class="stat-box">
        <h3 class="brand-font">1:99</h3>
        <p>SECRET CHASE ODDS</p>
    </div>
    <div class="stat-box">
        <h3 class="brand-font">24H</h3>
        <p>GLOBAL DISPATCH</p>
    </div>
</section>

<section id="shop" class="shop-section">
    <div class="mb-4">
        <div class="shop-header">
            <h2 class="brand-font">TOP SELLING PRODUCT</h2>
        </div>

        <div class="product-grid new-arrival-grid">
            <?php foreach ($topSellingProduct as $item): ?>
                <?php
                $stockClass = '';
                if ($item->getStockQuantity() <= 0) {
                    $stockClass = 'sold-out';
                }
                ?>
                <a href="/online_shopping_system/views/pages/products/product_detail.php?id=<?= $item->getBlindboxId() ?>"
                    class="popmart-product-card <?= $stockClass ?>"
                    data-id="<?= htmlspecialchars($item->getBlindboxId()) ?>">
                    <div class="popmart-image-container">
                        <?php if (!empty($images[$item->getBlindboxId()])): ?>
                            <img src="<?= htmlspecialchars($images[$item->getBlindboxId()][0]->getImageUrl()) ?>"
                                alt="<?= htmlspecialchars($item->getProductName()) ?>" class="popmart-product-image" />
                            <?php if ($item->getStockQuantity() < 1): ?>
                                <div class="popmart-sold-out-overlay">
                                    <span class="popmart-sold-out-text brand-font">SOLD OUT</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="popmart-product-name"><?= htmlspecialchars($item->getProductName()) ?></h3>
                    <p class="popmart-product-price">RM <?= number_format($item->getPrice(), 2) ?> / PICK</p>
                    <span class="popmart-pick-btn"><?= $item->getStockQuantity() < 1 ? 'Out of Stock' : 'Pick Now' ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div>
        <div class="shop-header">
            <h2 class="brand-font">NEW ARRIVAL</h2>
        </div>

        <div class="product-grid new-arrival-grid">
            <?php foreach ($newArrival as $item): ?>
                <?php
                $stockClass = '';
                if ($item->getStockQuantity() <= 0) {
                    $stockClass = 'sold-out';
                }
                ?>
                <a href="/online_shopping_system/views/pages/products/product_detail.php?id=<?= $item->getBlindboxId() ?>"
                    class="popmart-product-card <?= $stockClass ?>"
                    data-id="<?= htmlspecialchars($item->getBlindboxId()) ?>">
                    <div class="popmart-image-container">
                        <?php if (!empty($images[$item->getBlindboxId()])): ?>
                            <img src="<?= htmlspecialchars($images[$item->getBlindboxId()][0]->getImageUrl()) ?>"
                                alt="<?= htmlspecialchars($item->getProductName()) ?>" class="popmart-product-image" />
                            <?php if ($item->getStockQuantity() < 1): ?>
                                <div class="popmart-sold-out-overlay">
                                    <span class="popmart-sold-out-text brand-font">SOLD OUT</span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <h3 class="popmart-product-name"><?= htmlspecialchars($item->getProductName()) ?></h3>
                    <p class="popmart-product-price">RM <?= number_format($item->getPrice(), 2) ?> / PICK</p>
                    <span class="popmart-pick-btn"><?= $item->getStockQuantity() < 1 ? 'Out of Stock' : 'Pick Now' ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../../includes/footer.php'; ?>


<!-- <div class="product-card">
            <div class="card-image">
                <div class="shape-rect">
                    <span class="shape-text">?</span>
                </div>
            </div>
            <div class="card-details">
                <div>
                    <h3 class="card-title brand-font">MECHA SOUL</h3>
                    <p class="card-desc">Die-cast / Assembly</p>
                </div>
                <span class="card-price">$22.00</span>
            </div>
        </div>

        <div class="product-card sold-out">
            <div class="card-image">
                <div class="dashed-box">
                    <span style="font-size: 1.25rem;">EMPTY</span>
                </div>
                <div class="overlay">
                    <span class="sold-out-badge">SOLD OUT</span>
                </div>
            </div>
            <div class="card-details">
                <div>
                    <h3 class="card-title brand-font">VOID WALKER</h3>
                    <p class="card-desc">Resin Art Toy</p>
                </div>
                <span class="card-price">$45.00</span>
            </div>
        </div>

        <div class="product-card">
            <div class="card-image">
                <div class="shape-b-box shape-b">
                    <span class="shape-b-text">B</span>
                </div>
            </div>
            <div class="card-details">
                <div>
                    <h3 class="card-title brand-font">MONOLITH</h3>
                    <p class="card-desc">Blind Box Set</p>
                </div>
                <span class="card-price">$18.00</span>
            </div>
        </div> -->