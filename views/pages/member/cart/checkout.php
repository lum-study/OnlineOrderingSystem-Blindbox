<?php
$confirmIcon = file_get_contents(__DIR__ . "/../../../../assets/images/icons/fa-check-icon.svg");
?>

<!-- Checkout Container -->
<div class="checkout-container">
    <h1 class="checkout-title brand-font">CHECKOUT</h1>

    <!-- Stepper -->
    <div class="stepper">
        <div class="step active" data-step="1">
            <div class="step-icon">1</div>
            <div class="step-label">Shipping</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-icon">2</div>
            <div class="step-label">Payment</div>
        </div>
        <div class="step" data-step="3">
            <div class="step-icon">3</div>
            <div class="step-label">Confirmation</div>
        </div>
    </div>

    <!-- Step 1: Shipping Information -->
    <div class="step-content active" id="step-1">
        <h2 class="step-title brand-font">SHIPPING INFORMATION</h2>

        <h3 class="form-label" style="margin-top: 2rem; margin-bottom: 1rem;">DELIVERY METHOD</h3>

        <div class="delivery-options">
            <div class="delivery-option selected" data-option="home">
                <div class="option-icon">🏠</div>
                <div class="option-title">Home Delivery</div>
                <div class="option-desc">2-3 business days</div>
            </div>
            <div class="delivery-option" data-option="pickup">
                <div class="option-icon">🏪</div>
                <div class="option-title">Store Pickup</div>
                <div class="option-desc">Collect at our KL store</div>
            </div>
        </div>

        <div class="shipping-method-fields home-delivery-fields">
            <form id="shipping-form">
                <div class="address-card">
                    <div class="address-card-header">
                        <h3 class="address-card-title">Shipping Address</h3>
                        <div>
                            <input type="hidden" id="selected-address-id"
                                value="<?= $defaultAddress?->getAddressId() ?>">
                            <button type="button" class="btn btn-add-address" data-context="checkout">Add
                                Address</button>
                            <button type="button" class="btn btn-change-address">Change Address</button>
                        </div>
                    </div>

                    <div class="address-card-body">
                        <div class="address-row">
                            <div class="address-field">
                                <span class="address-label">Name:</span>
                                <span
                                    class="address-value"><?= htmlspecialchars($defaultAddress?->getReceiverName()) ?></span>
                            </div>
                        </div>

                        <div class="address-row address-row-2col">
                            <div class="address-field">
                                <span class="address-label">Email:</span>
                                <span class="address-value"><?= htmlspecialchars($defaultAddress?->getEmail()) ?></span>
                            </div>
                            <div class="address-field">
                                <span class="address-label">Phone Number:</span>
                                <span
                                    class="address-value"><?= htmlspecialchars($defaultAddress?->getPhoneNumber()) ?></span>
                            </div>
                        </div>

                        <div class="address-row">
                            <div class="address-field">
                                <span class="address-label">Street Address:</span>
                                <span
                                    class="address-value"><?= htmlspecialchars($defaultAddress?->getAddress()) ?></span>
                            </div>
                        </div>

                        <div class="address-row address-row-3col">
                            <div class="address-field">
                                <span class="address-label">City:</span>
                                <span class="address-value"><?= htmlspecialchars($defaultAddress?->getCity()) ?></span>
                            </div>
                            <div class="address-field">
                                <span class="address-label">State:</span>
                                <span class="address-value"><?= htmlspecialchars($defaultAddress?->getState()) ?></span>
                            </div>
                            <div class="address-field">
                                <span class="address-label">Postal Code:</span>
                                <span
                                    class="address-value"><?= htmlspecialchars($defaultAddress?->getPostalCode()) ?></span>
                            </div>
                        </div>

                        <div class="address-row">
                            <div class="address-field">
                                <span class="address-label">Country:</span>
                                <span class="address-value">
                                    <?php
                                    $countryCode = $defaultAddress?->getCountry();
                                    $countries = [
                                        'MY' => 'Malaysia',
                                        'SG' => 'Singapore',
                                        'ID' => 'Indonesia',
                                        'TH' => 'Thailand'
                                    ];
                                    echo htmlspecialchars($countries[$countryCode] ?? $countryCode);
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Store Pickup Selection (Initially Hidden) -->
        <div class="shipping-method-fields store-pickup-fields">
            <div class="store-pickup-section">
                <div class="store-pickup-list" style="display: none;">
                    <?php foreach ($storeLocations as $store): ?>
                        <div class="store-card" data-store-id="<?= $store['id'] ?>">
                            <div class="store-card-header">
                                <div class="store-radio">
                                    <input type="radio" name="selected-store" id="store-<?= $store['id'] ?>"
                                        value="<?= $store['id'] ?>">
                                    <label for="store-<?= $store['id'] ?>"></label>
                                </div>
                                <div class="store-title-group">
                                    <h3 class="store-title"><?= htmlspecialchars($store['title']) ?></h3>
                                    <span class="store-distance"><?= htmlspecialchars($store['distance']) ?></span>
                                </div>
                            </div>

                            <div class="store-card-body">
                                <div class="store-details">
                                    <div class="store-detail-row">
                                        <span class="store-detail-label">Address:</span>
                                        <span class="store-detail-value"><?= htmlspecialchars($store['address']) ?></span>
                                    </div>

                                    <div class="store-detail-row store-detail-row-2col">
                                        <div class="store-detail-col">
                                            <span class="store-detail-label">Operating Hours:</span>
                                            <span class="store-detail-value"><?= htmlspecialchars($store['hours']) ?></span>
                                        </div>
                                        <div class="store-detail-col">
                                            <span class="store-detail-label">Contact:</span>
                                            <span class="store-detail-value"><?= htmlspecialchars($store['phone']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-prev" disabled>Previous</button>
            <button type="button" class="btn btn-next" id="to-step-2">Continue to Payment</button>
        </div>
    </div>

    <!-- Step 2: Payment Method -->
    <div class="step-content" id="step-2">
        <h2 class="step-title brand-font">PAYMENT METHOD</h2>

        <div class="payment-methods">
            <div class="payment-method selected" data-method="card">
                <div class="payment-icon">💳</div>
                <div class="payment-details">
                    <div class="payment-title">Credit/Debit Card</div>
                    <div class="payment-desc">Pay securely with your card</div>
                </div>
            </div>

            <div class="payment-method" data-method="cash">
                <div class="payment-icon">💵</div>
                <div class="payment-details">
                    <div class="payment-title">Cash</div>
                    <div class="payment-desc">Cash on delivery</div>
                </div>
            </div>
        </div>

        <!-- Card Payment Form (shown when card is selected) -->
        <div id="card-form">
            <div class="form-group">
                <label class="form-label" for="card-number">Card Number</label>
                <div id="card-element" class="form-input"></div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3 class="summary-title">ORDER SUMMARY</h3>

            <?php foreach ($cartData as $item): ?>
                <div class="summary-item">
                    <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['qty'] ?>)</span>
                    <span class="item-price">RM <?= number_format($item['price'] * $item['qty'], 2) ?></span>
                </div>
            <?php endforeach; ?>

            <div class="summary-item">
                <span>Shipping</span>
                <span id="shipping-cost"></span>
            </div>
            <div class="summary-item">
                <span>Tax</span>
                <span id="tax"></span>
            </div>

            <div class="summary-total">
                <span>TOTAL</span>
                <span id="total-amount"></span>
            </div>
        </div>

        <div class="btn-group">
            <button type="button" class="btn btn-prev" id="to-step-1">Previous</button>
            <button type="button" class="btn btn-next" id="to-step-3">Complete Order</button>
        </div>
    </div>

    <!-- Step 3: Confirmation -->
    <div class="step-content" id="step-3">
        <div class="success-message">
            <div class="success-icon"><?= $confirmIcon ?></div>
            <h2 class="success-title brand-font">ORDER CONFIRMED</h2>
            <p class="success-desc">Thank you for your purchase! Your order has been successfully placed and will be
                processed shortly.</p>

            <div class="order-number">
                ORDER #BD-<?php echo date("Ymd"); ?>-<?php echo rand(1000, 9999); ?>
            </div>

            <p class="success-desc">A confirmation email has been sent to your registered email address with all the
                order details.</p>

            <div class="btn-group" style="justify-content: center;">
                <button type="button" class="btn btn-continue">Continue Shopping</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../modal/select_address_modal.php" ?>
<?php
$isAdmin = false; // for frontend
$csrfToken = Security::generateCSRF();
$context = 'checkout';
require_once __DIR__ . "/../modal/add_address_modal.php"
    ?>