<?php
require_once __DIR__ . "/../../models/Wishlist.php";
$wishlistModel = new Wishlist();
$wishlistItemDetail = $wishlistModel->getWishlistItemDetail($_SESSION['user_id']);
?>
<div class="profile-section <?= $activeSection === 'wishlist' ? 'active' : '' ?>" id="wishlist">
    <div class="profile-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="margin: 0;">Wishlist</h2>
        </div>
        <?php if (empty($wishlistItemDetail)): ?>
            <p class="empty-state">No items in your wishlist</p>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlistItemDetail as $item): ?>
                    <div class="wishlist-item">
                        <a href="<?= BASE_URL ?>views/pages/products/product_detail.php?id=<?= $item['blindbox_id'] ?>"
                            class="wishlist-link"></a>
                        <div class="wishlist-image">
                            <!-- Hardcoded placeholder image -->
                            <img src="/online_shopping_system/<?= $item["image_url"] ?>" alt="Product Image"
                                class="wishlist-item-img">
                        </div>
                        <div class="wishlist-info">
                            <h3 class="wishlist-item-name"><?= htmlspecialchars($item['product_name'] ?? 'Product Name') ?>
                            </h3>
                            <div class="flex justify-between items-center">
                                <p class="wishlist-item-price">RM <?= number_format($item['price'], 2) ?></p>
                                <a class="wishlist is-wishlist flex items-center" title="Remove from Wishlist"
                                    data-item-id="<?= htmlspecialchars($item["blindbox_id"]) ?>"
                                    data-wish="true"><?= $favIcon ?></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>