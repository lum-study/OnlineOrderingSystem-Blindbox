const config = {
    stripeKey: getStripeKey(),
    urls: {
        addPayment: '/online_shopping_system/payment/add',
        processCheckout: '/online_shopping_system/checkout/process',
        initiatePayment: '/online_shopping_system/payment/initiate',
        home: '/online_shopping_system/',
        sendConfirmationEmail: '/online_shopping_system/checkout/sendmail'
    }
};

function sendConfirmationEmail(order_id, shipping_id) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: config.urls.sendConfirmationEmail,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                order_id: order_id,
                shipping_id: shipping_id,
            }),
            success: function (data) {
                if (data.success) {
                    console.log(data);
                    resolve(data);
                } else {
                    reject(new Error(`Checkout failed: ${data.message}`));
                }
            },
            error: function (xhr) {
                reject(new Error(`PHP Mailer error: ${xhr.responseText || 'Unknown error'}`));
            }
        });
    });
}

$(document).ready(function () {
    // Configuration and state
    let currentButton = null;

    // Utility Functions
    function setButtonState($button, isEnabled, text = null) {
        if ($button) {
            $button.prop('disabled', !isEnabled);
            if (text !== null) {
                $button.text(text);
            }
        }
    }

    function resetButtonState() {
        if (currentButton) {
            setButtonState(currentButton, true, 'Complete Order');
            currentButton = null;
        }
    }

    function showError(message, $button = null) {
        console.error(message);
        showToast(message, "error");
        if ($button) {
            setButtonState($button, true, 'Complete Order');
        } else {
            resetButtonState();
        }
    }

    // Step Navigation
    function navigateToStep(stepNumber) {
        try {
            $('.step').each(function () {
                const $step = $(this);
                const stepNum = parseInt($step.data('step'));

                if (stepNum === stepNumber) {
                    $step.addClass('active').removeClass('completed');
                } else if (stepNum < stepNumber) {
                    $step.removeClass('active').addClass('completed');
                } else {
                    $step.removeClass('active completed');
                }
            });

            $('.step-content').each(function () {
                const $content = $(this);
                $content.toggleClass('active', $content.attr('id') === `step-${stepNumber}`);
            });
        } catch (error) {
            console.error('Error in navigateToStep:', error);
            showError('Failed to navigate between steps');
        }
    }

    function showAddressModal(modal) {
        modal.addClass("active");
    }

    function hideAddressModal(modal) {
        modal.removeClass("active");
    }

    function changeAddress(addr) {
        // Find the main address card body
        const $addressCard = $('.address-card-body');

        // Map each field dynamically
        $addressCard.find('.address-value').each(function () {
            const $field = $(this);
            const label = $field.closest('.address-field').find('.address-label').text().toLowerCase();

            if (label.includes('name')) {
                $field.text(addr.receiver_name);
            } else if (label.includes('email')) {
                $field.text(addr.email || '');
            } else if (label.includes('phone')) {
                $field.text(addr.phone_number);
            } else if (label.includes('street') || label.includes('address')) {
                $field.text(addr.address);
            } else if (label.includes('city')) {
                $field.text(addr.city);
            } else if (label.includes('state')) {
                $field.text(addr.state);
            } else if (label.includes('postal')) {
                $field.text(addr.postal_code);
            } else if (label.includes('country')) {
                $field.text(addr.country);
            }
        });

        // Update hidden input for form submission
        $('#selected-address-id').val(addr.address_id);

        // Close modal
        hideAddressModal($("#select-address-modal"));
    }

    function updateOrderSummary() {
        let subtotal = 0;
        const TAX_RATE = 0.06;

        // Calculate subtotal from cart items
        $('.item-price').each(function () {
            const text = $(this).text();
            if (text.startsWith('RM')) {
                const price = parseFloat(text.replace('RM', '').replace(/,/g, '').trim());
                subtotal += price;
            }
        });

        // Get shipping cost
        let shippingCost = 0;
        const selectedOption = $('.delivery-option.selected').data('option');
        if (selectedOption === 'home') {
            shippingCost = 5.00;
        } else {
            shippingCost = 0.00;
        }

        // Calculate tax (usually on subtotal only)
        const tax = parseFloat(subtotal * TAX_RATE);

        // Calculate total
        const total = parseFloat(subtotal + shippingCost + tax);

        // Update the DOM
        $('#shipping-cost').text(`RM ${shippingCost.toFixed(2)}`);
        $('#tax').text(`RM ${tax.toFixed(2)}`);
        $('#total-amount').text(`RM ${total.toFixed(2)}`);
    }

    function processCheckout(shippingID, paymentMethod, deliveryMethod) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: config.urls.processCheckout,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({
                    shipping_id: shippingID,
                    payment_method: paymentMethod,
                    delivery_method: deliveryMethod,
                }),
                success: function (data) {
                    if (data.success) {
                        resolve(data);
                    } else {
                        reject(new Error(`Checkout failed: ${data.message}`));
                    }
                },
                error: function (xhr) {
                    reject(new Error(`Server error: ${xhr.responseText || 'Unknown error'}`));
                }
            });
        });
    }

    // Delivery and Store Selection
    function setupDeliveryOptions() {
        $('.delivery-option').click(function () {
            try {
                $('.delivery-option').removeClass('selected');
                $(this).addClass('selected');

                const option = $(this).data('option');
                if (option === "home") {
                    $('.address-card').slideDown();
                    $('.store-pickup-list').slideUp();
                } else {
                    $('.address-card').slideUp();
                    $('.store-pickup-list').slideDown();
                }
            } catch (error) {
                console.error('Error in delivery option selection:', error);
            }
        });
    }

    function setupStoreSelection() {
        const $storeCards = $('.store-card');
        const $storeRadios = $('input[name="selected-store"]');

        $storeRadios.on('change', function () {
            try {
                $storeCards.removeClass('selected');
                $(this).closest('.store-card').addClass('selected');
            } catch (error) {
                console.error('Error in store selection:', error);
            }
        });

        // Set the first store as default selected
        if ($storeRadios.length > 0) {
            try {
                $storeRadios.eq(0).prop('checked', true);
                $storeCards.eq(0).addClass('selected');
            } catch (error) {
                console.error('Error setting default store:', error);
            }
        }
    }

    function setupPaymentMethods() {
        $('.payment-method').click(function () {
            try {
                $('.payment-method').removeClass('selected');
                $(this).addClass('selected');

                if ($(this).data('method') === 'card') {
                    $('#card-form').slideDown();
                } else {
                    $('#card-form').slideUp();
                }
            } catch (error) {
                console.error('Error in payment method selection:', error);
            }
        });
    }

    // Main Checkout Flow
    async function completeOrder() {
        const $btn = $('#to-step-3');
        currentButton = $btn;

        let order_id = null;
        try {
            setButtonState($btn, false, 'Processing...');

            // Validate inputs
            const deliveryMethod = $('.delivery-option.selected').data('option');
            const paymentMethod = $('.payment-method.selected').data('method');

            if (paymentMethod == "card") {
                if ($("#card-element").hasClass("StripeElement--empty")) {
                    showToast("Please fill in your card details.", "error");
                    return;
                }

                if ($("#card-element").hasClass("StripeElement--invalid")) {
                    showToast("Your card information is invalid.", "error");
                    return;
                }
            }

            if (!deliveryMethod || !paymentMethod) {
                showToast('Please select delivery and payment methods', "error");
                return;
            }

            let shippingID;
            if (deliveryMethod === 'home') {
                shippingID = $('#selected-address-id').val();
                if (!shippingID) {
                    navigateToStep(1);
                    showToast('Please select a shipping address', "error");
                    return;
                }
            } else {
                shippingID = $('input[name="selected-store"]:checked').val();
                if (!shippingID) {
                    navigateToStep(1);
                    showToast('Please select a store for pickup', "error");
                    return;
                }
            }

            // Process checkout
            const checkoutData = await processCheckout(shippingID, paymentMethod, deliveryMethod);

            // Update order number display
            order_id = checkoutData.order_id;
            $('.order-number').text("ORDER #" + checkoutData.order_id);

            if (paymentMethod === 'card') {
                // 1. Get Secret
                const initData = await initiatePayment(order_id, paymentMethod);

                // 2. Confirm with Stripe
                await processStripePayment(initData.client_secret);
            }

            await addPaymentRecord(checkoutData.order_id, paymentMethod);
            await sendConfirmationEmail(checkoutData.order_id, deliveryMethod === 'home' ? shippingID : '');

            if (paymentMethod == 'card') {
                showToast("Order and Payment successful!");
            } else {
                showToast("Order placed successfully!");
            }
            updateCartCount();
            navigateToStep(3);
        } catch (error) {
            console.error("Checkout flow error:", error);

            if (order_id) {
                showToast("Order placed, but payment validation failed. Please check your order history.", "warning");

                updateCartCount();
                navigateToStep(3);
            } else {
                showError(error.message, $btn);
            }
        } finally {
            resetButtonState();
        }
    }

    // Event Handlers
    function setupEventHandlers() {
        try {
            // Navigation buttons
            $('#to-step-1').click(function () {
                navigateToStep(1);
            });

            $('#to-step-2').click(function () {
                updateOrderSummary();
                navigateToStep(2);
                initializeStripe();
            });

            //Select Address
            $('.btn-change-address').click(() => showAddressModal($("#select-address-modal")));

            $('#select-address-modal').click(function (e) {
                if (!$(e.target).closest('.modal-content').length) {
                    $(this).removeClass("active");
                }
            });

            $(document).on('click', '.address-select-btn', function () {
                const data = $(this).attr('data-address');
                if (!data) return console.error('data-address missing');

                const addr = JSON.parse(data);
                changeAddress(addr);
            });

            $('.modal-close').click(() => hideAddressModal($("#select-address-modal")));

            //Add Address
            $('.btn-add-address').click(() => showAddressModal($("#add-address-modal")));

            // Complete order button
            $('#to-step-3').click(completeOrder);

            // Continue shopping button
            $('.btn-continue').click(function () {
                try {
                    window.location.href = config.urls.home;
                } catch (error) {
                    console.error('Error navigating to home:', error);
                    showError('Failed to navigate to home page');
                }
            });

            // Theme toggle
            $('#theme-toggle').click(updateStripeTheme);

        } catch (error) {
            console.error('Error setting up event handlers:', error);
        }
    }

    // Initialize
    function initialize() {
        try {
            setupDeliveryOptions();
            setupStoreSelection();
            setupPaymentMethods();
            setupEventHandlers();
            console.log('Checkout page initialized successfully');
        } catch (error) {
            console.error('Failed to initialize checkout page:', error);
            showError('Failed to initialize checkout system');
        }
    }

    // Start initialization
    initialize();
});