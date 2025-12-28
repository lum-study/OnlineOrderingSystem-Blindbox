// orders.js - Order management JavaScript functions

// Modal functions
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// View order function
function viewOrder(orderId) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_order.php?id=' + encodeURIComponent(orderId), true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var order = JSON.parse(xhr.responseText);
                if (order.success) {
                    displayOrderReceipt(order.data);
                    openModal('viewModal');
                } else {
                    showToast('Error: ' + order.message, "error");
                }
            } catch (e) {
                showToast('Error loading order details', "error");
            }
        }
    };

    xhr.send();
}

// Display order as receipt
function displayOrderReceipt(order) {
    var html = '<div class="receipt-section">';
    html += '<div class="receipt-row"><strong>Order ID:</strong> <span>' + order.order_id + '</span></div>';
    html += '<div class="receipt-row"><strong>Date:</strong> <span>' + formatDate(order.created_date) + '</span></div>';
    html += '<div class="receipt-row"><strong>Payment Method:</strong> <span>' + (order.payment_method ? order.payment_method.toUpperCase() : 'N/A') + '</span></div>';
    html += '</div>';

    html += '<div class="receipt-section">';
    html += '<div class="receipt-row"><strong>Customer:</strong> <span>' + order.fullname + '</span></div>';
    html += '<div class="receipt-row"><strong>Email:</strong> <span>' + order.email + '</span></div>';
    if (order.contact_number) {
        html += '<div class="receipt-row"><strong>Contact:</strong> <span>' + order.contact_number + '</span></div>';
    }
    html += '</div>';

    if (order.shipping_address) {
        html += '<div class="receipt-section">';
        html += '<div class="receipt-row"><strong>Shipping Address:</strong></div>';
        html += '<div>' + order.shipping_address.replace(/\n/g, '<br>') + '</div>';
        html += '</div>';
    }

    if (order.items && order.items.length > 0) {
        html += '<div class="receipt-section receipt-items">';
        html += '<table><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';
        order.items.forEach(function (item) {
            html += '<tr>';
            html += '<td>' + item.product_name + '</td>';
            html += '<td>' + item.quantity + '</td>';
            html += '<td>RM ' + parseFloat(item.price).toFixed(2) + '</td>';
            html += '<td>RM ' + (parseFloat(item.subtotal) + parseFloat(item.tax_amount)).toFixed(2) + '</td>';
            html += '</tr>';
        });
        html += '</tbody></table></div>';
    }

    html += '<div class="receipt-section">';
    html += '<div class="receipt-row"><span>Subtotal:</span> <span>RM ' + parseFloat(order.sub_total_amount).toFixed(2) + '</span></div>';

    html += '<div class="receipt-row"><span>Tax:</span> <span>RM ' + parseFloat(order.tax_amount).toFixed(2) + '</span></div>';
    html += '<div class="receipt-row receipt-total"><span>TOTAL:</span> <span>RM ' + parseFloat(order.total_amount).toFixed(2) + '</span></div>';
    html += '</div>';

    document.getElementById('orderDetails').innerHTML = html;
}

// Edit order function
function editOrder(orderId) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'get_order.php?id=' + encodeURIComponent(orderId), true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var order = JSON.parse(xhr.responseText);
                if (order.success) {
                    populateEditForm(order.data);
                    openModal('editModal');
                } else {
                    showToast('Error: ' + order.message, "error");
                }
            } catch (e) {
                showToast('Error loading order details', "error");
            }
        }
    };

    xhr.send();
}

// Populate edit form
function populateEditForm(order) {
    document.getElementById('editOrderId').value = order.order_id;
    document.getElementById('editStatus').value = order.status;
    document.getElementById('editSubtotal').value = order.sub_total_amount;

    document.getElementById('editTax').value = order.tax_amount;
    document.getElementById('editTotal').value = order.total_amount;
    document.getElementById('editAddress').value = order.shipping_address || '';
}

// Delete order function
function deleteOrder(orderId) {
    document.getElementById('confirmDelete').onclick = function () {
        performDelete(orderId);
    };
    openModal('deleteModal');
}

// Perform delete
function performDelete(orderId) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'admin_delete.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                closeModal('deleteModal');
                showToast(response.message || (response.success ? 'Order deleted successfully' : 'Error deleting order'), response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (e) {
                showToast('Error processing response', "error");
            }
        }
    };

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    xhr.send('order_id=' + encodeURIComponent(orderId) + '&csrf_token=' + encodeURIComponent(csrfToken));
}

// Print receipt function
function printReceipt() {
    var printWindow = window.open('', '_blank');
    var receiptContent = document.getElementById('receiptContent').innerHTML;

    printWindow.document.write('<html><head><title>Order Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Courier New", monospace; margin: 20px; }');
    printWindow.document.write('.receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }');
    printWindow.document.write('.receipt-header h2 { font-size: 24px; margin: 0; letter-spacing: 2px; }');
    printWindow.document.write('.receipt-section { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #ccc; }');
    printWindow.document.write('.receipt-row { display: flex; justify-content: space-between; margin-bottom: 5px; }');
    printWindow.document.write('.receipt-total { font-weight: bold; font-size: 16px; border-top: 1px solid #000; padding-top: 10px; margin-top: 10px; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin: 10px 0; }');
    printWindow.document.write('th, td { padding: 8px; text-align: left; border-bottom: 1px dotted #ccc; }');
    printWindow.document.write('th { font-weight: bold; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.print();
}

// Form submission with confirmation
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('editForm').addEventListener('submit', function (e) {
        e.preventDefault();
        showEditConfirmation();
    });
});

// Show edit confirmation
function showEditConfirmation() {
    document.getElementById('editConfirmText').innerHTML = 'Are you sure you want to update this order?';
    openModal('editConfirmModal');
}

// Confirm edit action
function confirmEdit() {
    var formData = new FormData(document.getElementById('editForm'));
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_order.php', true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                closeModal('editConfirmModal');
                closeModal('editModal');
                showToast(response.message || (response.success ? 'Order updated successfully' : 'Error updating order'), response.success ? 'success' : 'error');
                if (response.success) {
                    setTimeout(() => window.location.reload(), 800);
                }
            } catch (e) {
                showToast('Error processing response', 'error');
            }
        }
    };

    xhr.send(formData);
}

// Helper function
function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}