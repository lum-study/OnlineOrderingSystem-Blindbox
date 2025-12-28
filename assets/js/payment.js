const stripeKey = "pk_test_51SWOqiAaE1I6o9Gi0xoe5M0xosZA1QhME88CTyehbOYxUL7agjLmjIKJbr1hBONztToCiJfzdnntNfGdHIk9zgjG004mTSqaD9";
let stripe, card, elements;
let currentOrderID = null;

function getTextColor() {
    return getComputedStyle(document.documentElement)
        .getPropertyValue('--text-color')
        .trim();
}

// Stripe Integration
function initializeStripe() {
    try {
        if (!stripe) {
            stripe = Stripe(stripeKey);
            elements = stripe.elements();
            card = elements.create("card", {
                style: {
                    base: {
                        color: getTextColor(),
                        fontFamily: "'Space Mono', monospace",
                        fontSize: '16px',
                        '::placeholder': {
                            color: getTextColor()
                        },
                        iconColor: getTextColor(),
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                },
            });

            card.mount("#card-element");
        }
        return stripe;
    } catch (error) {
        console.error('Error initializing Stripe:', error);
        showError('Failed to initialize payment system');
        return null;
    }
}

function updateStripeTheme() {
    try {
        if (card) {
            const newTextColor = getTextColor();
            card.update({
                style: {
                    base: {
                        color: newTextColor,
                        iconColor: newTextColor,
                        '::placeholder': {
                            color: newTextColor
                        },
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error updating Stripe theme:', error);
    }
}

function initiatePayment(order_id, paymentMethod) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: config.urls.initiatePayment,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                order_id: order_id,
                payment_method: paymentMethod,
            }),
            success: function (data) {
                if (data.success) {
                    resolve(data);
                } else {
                    reject(new Error(data.message || 'Payment initialization failed'));
                }
            },
            error: function (xhr) {
                reject(new Error(`Server error: ${xhr.responseText || 'Unknown error'}`));
            }
        });
    });
}

function processStripePayment(clientSecret) {
    return new Promise((resolve, reject) => {
        getStripeInstance().confirmCardPayment(clientSecret, {
            payment_method: { card: getCardElement() }
        }).then(function (result) {
            if (result.error) {
                reject(new Error(`Payment failed: ${result.error.message}`));
            } else if (result.paymentIntent.status === "succeeded") {
                resolve(result.paymentIntent);
            } else {
                reject(new Error('Payment was not successful'));
            }
        });
    });
}

function addPaymentRecord(order_id, payment_method) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: config.urls.addPayment,
            method: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                order_id: order_id,
                payment_method: payment_method,
            }),
            success: function (data) {
                if (data.success) {
                    resolve(true);
                } else {
                    reject(new Error(`Database update failed: ${data.message}`));
                }
            },
            error: function (xhr) {
                reject(new Error(`Backend error: ${xhr.responseText || 'Unknown error'}`));
            }
        });
    });
}

function payNowOnClick(orderID) {
    currentOrderID = orderID;
    initializeStripe();
    $("#payment-modal").addClass("active");
}

function updateOrderStatusButton(orderID) {
    const $status = $(`.status-to_pay[data-order-id="${orderID}"]`);

    $status
        .removeClass('status-to_pay')
        .addClass('status-pending')
        .removeAttr('onclick')
        .text('Pending');

    const $orderItem = $status.closest('.order-item');
    const $actions = $orderItem.find('.order-actions');

    // Remove buttons that no longer apply
    $actions.find('button').not(':first').remove();

    // Add Cancel button
    const cancelBtn = `
        <button type="button"
            onclick="cancelOrder('${orderID}')"
            class="user-btn user-btn-small"
            style="background:#ef4444;border-color:#ef4444;">
            Cancel
        </button>
    `;

    $actions.append(cancelBtn);
}

function getStripeInstance() {
    return stripe;
}

function getCardElement() {
    return card;
}

function getElementsInstance() {
    return elements;
}

function getStripeKey() {
    return stripeKey;
}


$(document).ready(function () {
    $("#confirm-payment").on("click", async function () {
        const $button = $("#confirm-payment");
        if ($button) {
            $button.prop('disabled', false);
            $button.text("Processing...");
        }

        if (!currentOrderID) {
            showToast("Order ID missing", "error");
            return;
        }

        const paymentMethod = $('.payment-method.selected').data('method');

        if (!paymentMethod) {
            showToast("Please select a payment method", "error");
            return;
        }

        try {
            // 1. Get Secret
            const initData = await initiatePayment(currentOrderID, paymentMethod);

            // 2. Confirm with Stripe
            if (paymentMethod === "card") {
                await processStripePayment(initData.client_secret);
            }

            // 3. Add Payment Record
            await addPaymentRecord(currentOrderID, paymentMethod);

            // 4. Show Message
            showToast("Payment successful!");

            // 5. Close Modal
            $("#payment-modal").removeClass("active");

            updateOrderStatusButton(currentOrderID);
            sendConfirmationEmail(currentOrderID);
        } catch (err) {
            showToast("Payment Error: " + err.message, "error");
        }
    })
})