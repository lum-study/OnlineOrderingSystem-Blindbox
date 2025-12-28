//AJAX helper function
function sendCartItemRequest(action, data, callback) {
    $.ajax({
        url: `/online_shopping_system/cartitem/${action}`, // API endpoint in CartItemController
        type: 'POST',
        data: data,
        dataType: 'json',
        success: callback,
        error: function (xhr, status, error) {
            showToast('An error occurred: ' + error, "error")
        }
    });
}

function updateCartCount() {
    sendCartItemRequest('get/count', {}, function (response) {
        if (response.success) {
            $('#cart-count').text(response.count);
        }
        else {
            console.error('Failed to fetch cart count.');
        }
    });
}

$(document).ready(function () {

    // -----------------------------
    // Helper functions
    // -----------------------------
    function updateTotals() {
        let subtotal = 0;

        $('.cart-row').each(function () {
            const $row = $(this);
            const isChecked = $row.find('.select-item').is(':checked');

            const price = parseFloat($row.find('.price').text()) || 0;
            let qty = parseInt($row.find('.cart-qty input[type="number"]').val(), 10);

            const rowSubtotal = price * qty;
            $row.find('.subtotal').text(rowSubtotal.toFixed(2));

            if (isChecked) {
                subtotal += rowSubtotal;
            }
        });

        const taxRate = 0.06; // 6% tax
        const tax = subtotal * taxRate;
        const total = subtotal + tax;

        $('#summary-subtotal').text(subtotal.toFixed(2));
        $('#summary-tax').text(tax.toFixed(2));
        $('#summary-total').text(total.toFixed(2));
    }

    function checkEmptyCart() {
        // Check if cart is empty
        if ($('.cart-row').length === 0) {
            $('.cart-container').html(`
                <div class="text-center flex flex-col" style="gap:50px;">
                    <h2 class="brand-font" style="font-size: 3rem; margin-bottom: 1rem;">SHOPPING CART</h2>
                    <p>Your cart is empty.</p>
                    <a href="/online_shopping_system/views/pages/products/product_list.php" class="shopping-btn">Go Shopping Now</a>
                </div>
            `);
        }
    }

    // -----------------------------
    // Event handlers
    // -----------------------------

    // Handle select-all checkbox
    $('#select-all').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('.cart-row .select-item:not(:disabled)').prop('checked', isChecked);
        updateTotals();
    });

    // Handle individual item selection
    $(document).on('change', '.cart-row .select-item', function () {
        const allChecked = $('.cart-row .select-item').length === $('.cart-row .select-item:checked').length;
        $('#select-all').prop('checked', allChecked);
        updateTotals();
    });

    $(document).on('focus', '.cart-qty input', function () {
        const $input = $(this);
        $input.data('prev-qty', parseInt($input.val(), 10));
    });

    // Handle quantity changes
    $(document).on('focusout keypress', '.cart-qty input', function (e) {
        if (e.type === 'focusout' || e.which === 13) {
            const $input = $(this);
            const $row = $input.closest('.cart-row');
            const cartItem = $row.data('cart-item');

            let qty = parseInt($input.val()) || 0;
            $input.val(qty);

            sendCartItemRequest('update/quantity',
                { cart_item: cartItem, cart_item_quantity: qty },
                function (response) {
                    if (response.success) {
                        updateTotals();
                    } else {
                        showToast('Failed to update quantity.', "error");
                        $input.val($input.data('prev-qty'));
                    }
                }
            );
        }
    });

    // Remove item
    $(document).on('click', '.remove-btn', function (e) {
        e.preventDefault();
        const $row = $(this).closest('.cart-row');
        const cartItemID = $row.data('cart-item')['id'];

        sendCartItemRequest('remove', { cart_item_id: cartItemID }, function (response) {
            if (response.success) {
                $row.remove();
                updateTotals();
                checkEmptyCart();
                updateCartCount();
            } else {
                showToast("Failed to remove item.", "error");
            }
        });
    });

    //Add wishlist
    $(document).on('click', '.wishlist', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const blindboxID = $btn.data('item-id');
        const isWish = $btn.data('wish');

        // AJAX request to add to wishlist
        if (!isWish) {
            $.ajax({
                url: `/online_shopping_system/wishlist/add`,
                type: 'POST',
                data: { blindbox_id: blindboxID },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast(response.message);
                        $btn.addClass('is-wishlist');
                        $btn.data('wish', true);
                    } else {
                        showToast('Failed to add to wishlist.', "error");
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText)
                    showToast('An error occurred: ' + error, "error");
                }
            });
        } else {
            $.ajax({
                url: `/online_shopping_system/wishlist/remove`,
                type: 'POST',
                data: { blindbox_id: blindboxID },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast(response.message);
                        $btn.removeClass('is-wishlist')
                        $btn.data('wish', false);
                    } else {
                        showToast('Failed to remove to wishlist.', "error");
                    }
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText)
                    showToast('An error occurred: ' + error, "error");
                }
            });
        }

        if (!isWish) {
            $btn.attr('title', 'Remove from Wishlist');
        } else {
            $btn.attr('title', 'Add to Wishlist');
        }
    });

    // Checkout button
    $('#checkoutForm').on('submit', function (e) {
        const selectedItems = [];

        $('.cart-row').each(function () {
            const $row = $(this);
            if ($row.find('.select-item').is(':checked')) {
                selectedItems.push({
                    id: $row.data('cart-item')['id'],
                    blindboxID: $row.data('cart-item')['blindboxID'],
                    name: $row.find('.product-name').text(),
                    qty: parseInt($row.find('.cart-qty input').val()),
                    price: parseFloat($row.find('.price').text())
                });
            }
        });

        if (selectedItems.length === 0) {
            e.preventDefault(); // stop form submission
            showToast("Please select at least one item to proceed checkout.", "error");
            return;
        }

        // Put the selected items JSON in the hidden input
        $('#cartData').val(JSON.stringify(selectedItems));
    });

    // -----------------------------
    // Initialization
    // -----------------------------
    updateTotals();
    checkEmptyCart();
    updateCartCount();
});
