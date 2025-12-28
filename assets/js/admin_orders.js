// Admin Order Management JavaScript

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
    modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    modal.classList.remove('active');
}

// Status progression mapping - Admin cannot cancel, only customer can
var statusProgression = {
    'pending': ['shipped'],
    'paid': ['shipped'],
    'shipped': [],
    'completed': [],
    'cancelled': []
};

// Event listeners for close buttons
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-modal');
            closeModal(modalId);
        });
    });
});

// View order function
function viewOrder(orderId) {
    const orders = window.ordersData;
    const order = orders.find(o => o.order_id == orderId);
    
    if (order) {
        order.items = window.orderItemsData[orderId] || [];
        displayOrderReceipt(order);
        openModal('viewModal');
    } else {
        showToast('Order not found', 'error');
    }
}

// Edit order function  
function editOrder(orderId) {
    const orders = window.ordersData;
    const order = orders.find(o => o.order_id == orderId);
    
    if (order) {
        document.getElementById('editOrderId').value = order.order_id;
        
        const statusSelect = document.getElementById('editStatus');
        const currentStatus = order.status;
        const allowedStatuses = getNextStatuses(currentStatus);
        
        statusSelect.innerHTML = '';
        statusSelect.innerHTML += '<option value="' + currentStatus + '">' + currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1) + ' (Current)</option>';
        
        allowedStatuses.forEach(status => {
            statusSelect.innerHTML += '<option value="' + status + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</option>';
        });
        
        document.getElementById('editAddress').value = order.shipping_address || '';
        openModal('editModal');
    } else {
        showToast('Order not found', 'error');
    }
}

// Get allowed next statuses
function getNextStatuses(currentStatus) {
    const progression = {
        'pending': ['shipped'],
        'paid': ['shipped'],
        'shipped': [],
        'completed': [],
        'cancelled': []
    };
    return progression[currentStatus] || [];
}

// Update order status function
function updateOrderStatus() {
    const orderId = document.getElementById('editOrderId').value;
    const status = document.getElementById('editStatus').value;
    const address = document.getElementById('editAddress').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(window.baseUrl + 'views/pages/admin/orders/admin_list.php?action=update_order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update_order&order_id=' + orderId + '&status=' + status + '&shipping_address=' + encodeURIComponent(address) + '&csrf_token=' + csrfToken
    })
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
    })
    .then(data => {
        closeModal('editModal');
        showToast(data.message || (data.success ? 'Order updated successfully' : 'Error updating order'), data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 800);
    })
    .catch(error => {
        closeModal('editModal');
        console.error('Error:', error);
        showToast('Error updating order', 'error');
    });
}

// Display order as receipt
function displayOrderReceipt(order) {
    var html = '<div class="receipt-section">';
    html += '<div class="receipt-row"><strong>Order ID:</strong> <span>' + order.order_id + '</span></div>';
    html += '<div class="receipt-row"><strong>Date:</strong> <span>' + new Date(order.created_date).toLocaleDateString() + '</span></div>';
    html += '<div class="receipt-row"><strong>Payment Method:</strong> <span>' + (order.payment_method ? order.payment_method.toUpperCase() : 'N/A') + '</span></div>';
    html += '</div>';
    
    html += '<div class="receipt-section">';
    html += '<div class="receipt-row"><strong>Customer:</strong> <span>' + order.fullname + '</span></div>';
    html += '<div class="receipt-row"><strong>Email:</strong> <span>' + order.email + '</span></div>';
    html += '</div>';
    
    if (order.items && order.items.length > 0) {
        html += '<div class="receipt-section">';
        html += '<h4>Items:</h4>';
        html += '<table class="receipt-items-table">';
        html += '<tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>';
        
        order.items.forEach(function(item) {
            html += '<tr>';
            html += '<td>' + (item.product_name || item.blindbox_id) + '</td>';
            html += '<td>' + item.quantity + '</td>';
            html += '<td>RM ' + parseFloat(item.price).toFixed(2) + '</td>';
            html += '<td>RM ' + parseFloat(item.subtotal).toFixed(2) + '</td>';
            html += '</tr>';
        });
        
        html += '</table></div>';
    }
    
    html += '<div class="receipt-section">';
    html += '<div class="receipt-row"><span>Subtotal:</span> <span>RM ' + parseFloat(order.sub_total_amount).toFixed(2) + '</span></div>';
    html += '<div class="receipt-row"><span>Tax:</span> <span>RM ' + parseFloat(order.tax_amount).toFixed(2) + '</span></div>';
    html += '<div class="receipt-row receipt-total"><span>TOTAL:</span> <span>RM ' + parseFloat(order.total_amount).toFixed(2) + '</span></div>';
    html += '</div>';
    
    document.getElementById('orderDetails').innerHTML = html;
}

// Print receipt function
function printReceipt() {
    var printWindow = window.open('', '_blank');
    var receiptContent = document.getElementById('receiptContent').innerHTML;
    
    printWindow.document.write('<html><head><title>Order Receipt</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('@page { size: A4 portrait; margin: 1cm; }');
    printWindow.document.write('body { font-family: "Courier New", monospace; margin: 0; }');
    printWindow.document.write('.receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px; }');
    printWindow.document.write('.receipt-section { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px dashed #ccc; }');
    printWindow.document.write('.receipt-row { display: flex; justify-content: space-between; margin-bottom: 5px; }');
    printWindow.document.write('.receipt-total { font-weight: bold; font-size: 16px; border-top: 1px solid #000; padding-top: 10px; }');
    printWindow.document.write('.receipt-items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }');
    printWindow.document.write('.receipt-items-table th, .receipt-items-table td { padding: 8px; text-align: left; border-bottom: 1px dotted #000; }');
    printWindow.document.write('.receipt-items-table th { font-weight: bold; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(receiptContent);
    printWindow.document.write('</body></html>');
    
    printWindow.document.close();
    printWindow.print();
}

// View reviews function
function viewReviews(orderId) {
    const reviews = window.orderReviewsData[orderId] || [];
    const container = document.getElementById('reviewsContent');
    
    if (reviews.length === 0) {
        container.innerHTML = '<p style="text-align:center;color:#6b7280;padding:2rem;">No reviews yet</p>';
    } else {
        let html = '';
        reviews.forEach(review => {
            const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
            html += `
                <div style="border:1px solid #e5e7eb;border-radius:8px;padding:1.5rem;margin-bottom:1rem;background:#fff;">
                    <div style="margin-bottom:1rem;">
                        <strong style="font-size:1.1rem;color:#1f2937;">${review.fullname}</strong>
                    </div>
                    <div style="margin-bottom:1rem;">
                        <strong style="color:#374151;font-size:0.95rem;">Rating:</strong>
                        <div style="color:#fbbf24;font-size:1.5rem;margin-top:0.25rem;">${stars}</div>
                    </div>
                    <div style="margin-bottom:1rem;">
                        <strong style="color:#374151;font-size:0.95rem;">Comment:</strong>
                        <p style="color:#4b5563;line-height:1.6;margin-top:0.25rem;">${review.comment}</p>
                    </div>`;
            
            if (review.images && review.images.length > 0) {
                html += '<div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1rem;">';
                review.images.forEach(img => {
                    html += `<img src="${window.baseUrl}assets/images/uploads/reviews/${img.image_url}" style="width:120px;height:120px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;cursor:pointer;" onclick="window.open(this.src, '_blank')">`;
                });
                html += '</div>';
            }
            html += '</div>';
        });
        container.innerHTML = html;
    }
    
    openModal('reviewsModal');
}
