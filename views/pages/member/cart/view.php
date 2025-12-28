<?php
$trashIcon = file_get_contents(__DIR__ . "/../../../../assets/images/icons/fa-trash-can.svg");
$favIcon = file_get_contents(__DIR__ . "/../../../../assets/images/icons/fa-heart.svg");
?>

<?php if (!empty($_SESSION['error'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            showToast("<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES) ?>", "error");
        });
    </script>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="cart-container">
    <?php if (!empty($cartItems)): ?>
        <div class="cart-header bold">
            <div class="cart-select">
                <input type="checkbox" class="select-item" id="select-all">
            </div>
            <p>Product</p>
            <p>Quantity</p>
            <p>Subtotal</p>
        </div>

        <?php foreach ($cartItems as $item): ?>
            <?php
            $isOutOfStock = $item['stock_qty'] <= 0;
            $inWishlist = $item['isWishlist'];
            ?>
            <div class="cart-row" data-cart-item="<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>">
                <div class="cart-select">
                    <input type="checkbox" class="select-item" <?= $isOutOfStock ? "disabled" : "" ?>>
                </div>

                <div class="cart-product">
                    <div style="position: relative;">
                        <img src="<?= BASE_URL . $item['img'] ?>" alt="<?= $item['name'] ?>">
                        <?php if ($isOutOfStock): ?>
                            <div class="overlay">
                                <span class="sold-out-badge" style="font-size: 16px;">SOLD OUT</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="product-name"><?= $item['name'] ?></p>
                        <p class="product-price">Price: RM <span class="price"><?= number_format($item['price'], 2) ?></span>
                        </p>
                        <div class="flex items-center" style="gap: 24px;">
                            <a class="remove-btn" title="Remove Item"><?= $trashIcon ?></a>
                            <a class="wishlist <?= $inWishlist ? "is-wishlist" : "" ?>"
                                title="<?= $inWishlist ? "Remove from Wishlist" : "Add to Wishlist" ?>"
                                data-item-id="<?= htmlspecialchars($item["blindboxID"]) ?>"
                                data-wish="<?= htmlspecialchars($inWishlist) ?>"><?= $favIcon ?></a>
                        </div>
                    </div>
                </div>

                <div class="cart-qty">
                    <input type="number" value="<?= $isOutOfStock ? 0 : $item['qty'] ?>" min="0" max="<?= $item['stock_qty'] ?>"
                        <?= $isOutOfStock ? "disabled" : "" ?>>
                </div>

                <div class="cart-subtotal bold">RM <span
                        class="subtotal"><?= number_format($item['price'] * $item['qty'], 2) ?></span></div>
            </div>
        <?php endforeach; ?>

        <hr class="divider">

        <div class="summary">
            <p>Subtotal: RM <span id="summary-subtotal">0.00</span></p>
            <p>Tax: RM <span id="summary-tax">0.00</span></p>
            <p class="bold">Total: RM <span id="summary-total">0.00</span></p>
        </div>

        <form id="checkoutForm" action="/online_shopping_system/checkout" method="post" class="flex justify-end">
            <input type="hidden" name="cart_data" id="cartData">
            <button type="submit" class="checkout-btn">Proceed to checkout →</button>
        </form>
    <?php else: ?>
        <div class="text-center flex flex-col" style="gap:50px;">
            <h2 class="brand-font" style="font-size: 3rem; margin-bottom: 1rem;">SHOPPING CART</h2>
            <p>Your cart is empty.</p>
            <a href="/online_shopping_system/views/pages/products/product_list.php" class="shopping-btn">Go Shopping Now</a>
        </div>
    <?php endif; ?>
</div>