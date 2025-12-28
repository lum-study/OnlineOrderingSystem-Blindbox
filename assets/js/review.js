// Review Modal Functions
function openReviewModal(orderId) {
    document.getElementById('review-order-id').value = orderId;
    
    fetch(window.location.pathname + '?action=get_order_items&order_id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items) {
                displayReviewItems(data.items);
                document.getElementById('review-modal').classList.add('active');
            } else {
                showToast(data.message || 'Failed to load order items', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading order items', 'error');
        });
}

function displayReviewItems(items) {
    const container = document.getElementById('review-items-container');
    let html = '';
    
    const unreviewed = items.filter(item => !item.review_id);
    if (unreviewed.length === 0) {
        html = '<p>All items have been reviewed</p>';
        container.innerHTML = html;
        return;
    }
    
    // Display all items with rating, comment, and images
    unreviewed.forEach(item => {
        html += `
            <div class="review-item-card" style="margin-bottom:1.5rem;padding:1rem;border:1px solid var(--border-color);border-radius:4px;">
                <h3>${item.product_name}</h3>
                <input type="hidden" name="order_item_ids[]" value="${item.order_item_id}">
                <input type="hidden" name="blindbox_ids[]" value="${item.blindbox_id}">
                <div class="star-rating" data-item-id="${item.order_item_id}">
                    ${[5,4,3,2,1].map(star => `
                        <input type="radio" id="star${star}-${item.order_item_id}" name="rating-${item.order_item_id}" value="${star}" required>
                        <label for="star${star}-${item.order_item_id}" title="${star} stars">★</label>
                    `).join('')}
                </div>
                <textarea name="comment-${item.order_item_id}" placeholder="Comment (Optional)" rows="3" style="width:100%;margin-top:1rem;padding:0.5rem;border:1px solid var(--border-color);border-radius:4px;"></textarea>
                
                <div class="image-upload-section" style="margin-top:1rem;">
                    <label style="display:block;margin-bottom:0.5rem;font-weight:600;">Upload Photos (Max 5, Optional)</label>
                    <input type="file" name="images-${item.order_item_id}[]" accept="image/*" multiple class="review-image-input" data-item-id="${item.order_item_id}" style="margin-bottom:0.5rem;">
                    <div class="image-preview-container" id="preview-${item.order_item_id}" style="display:flex;gap:0.5rem;flex-wrap:wrap;"></div>
                </div>
            </div>
        `;
    });
    
    html += '<button type="button" onclick="submitAllReviews()" class="user-btn" style="margin-top:1.5rem;width:100%;">Submit Reviews</button>';
    
    container.innerHTML = html;
    
    // Add image preview for each item
    unreviewed.forEach(item => {
        const fileInput = document.querySelector(`input[name="images-${item.order_item_id}[]"]`);
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                handleImagePreview(e, item.order_item_id);
            });
        }
    });
}

function handleImagePreview(event, itemId) {
    const files = event.target.files;
    const previewContainer = document.getElementById('preview-' + itemId);
    previewContainer.innerHTML = '';
    
    if (files.length > 5) {
        showToast('Maximum 5 images allowed per item', 'error');
        event.target.value = '';
        return;
    }
    
    Array.from(files).forEach((file, index) => {
        if (file.size > 2 * 1024 * 1024) {
            showToast('Image ' + (index + 1) + ' exceeds 2MB', 'error');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '80px';
            img.style.height = '80px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '4px';
            img.style.border = '1px solid var(--border-color)';
            previewContainer.appendChild(img);
        };
        reader.readAsDataURL(file);
    });
}

function submitAllReviews() {
    const orderId = document.getElementById('review-order-id').value;
    const orderItemIds = Array.from(document.querySelectorAll('input[name="order_item_ids[]"]')).map(i => i.value);
    const blindboxIds = Array.from(document.querySelectorAll('input[name="blindbox_ids[]"]')).map(i => i.value);
    
    // Validate all ratings and collect comments
    const ratings = [];
    const comments = [];
    for (let itemId of orderItemIds) {
        const rating = document.querySelector(`input[name="rating-${itemId}"]:checked`);
        if (!rating) {
            showToast('Please rate all items', 'error');
            return;
        }
        ratings.push(rating.value);
        
        const comment = document.querySelector(`textarea[name="comment-${itemId}"]`).value;
        comments.push(comment);
    }
    
    const formData = new FormData();
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    formData.append('order_id', orderId);
    formData.append('order_item_ids', JSON.stringify(orderItemIds));
    formData.append('blindbox_ids', JSON.stringify(blindboxIds));
    formData.append('ratings', JSON.stringify(ratings));
    formData.append('comments', JSON.stringify(comments));
    
    // Append images for each item
    orderItemIds.forEach(itemId => {
        const imageInput = document.querySelector(`input[name="images-${itemId}[]"]`);
        if (imageInput && imageInput.files.length > 0) {
            Array.from(imageInput.files).forEach((file) => {
                formData.append(`images-${itemId}[]`, file);
            });
        }
    });
    
    fetch(window.location.pathname + '?action=submit_review', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Response is not JSON:', text);
                throw new Error('Server returned invalid response');
            }
        });
    })
    .then(data => {
        if (data.success) {
            closeReviewModal();
            showToast(data.message || 'Reviews submitted successfully', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'Error submitting reviews', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error submitting reviews', 'error');
    });
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('DOMContentLoaded', function() {
    const reviewModal = document.getElementById('review-modal');
    if (reviewModal) {
        reviewModal.addEventListener('click', function(e) {
            if (e.target === reviewModal) closeReviewModal();
        });
    }
});
