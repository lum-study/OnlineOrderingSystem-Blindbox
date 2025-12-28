// Zoom functionality
const mainImageFrame = document.querySelector('.main-image-frame');
const mainImage = document.getElementById('mainImage');

if (mainImageFrame && mainImage) {
    mainImageFrame.addEventListener('mousemove', function (e) {
        const {
            left,
            top,
            width,
            height
        } = mainImageFrame.getBoundingClientRect();
        const x = e.clientX - left;
        const y = e.clientY - top;

        // Calculate percentage position
        const xPercent = (x / width) * 100;
        const yPercent = (y / height) * 100;

        // Update transform origin to follow mouse
        mainImage.style.transformOrigin = `${xPercent}% ${yPercent}%`;
    });

    // Reset on mouse leave (optional, but good for UX)
    mainImageFrame.addEventListener('mouseleave', function () {
        mainImage.style.transformOrigin = 'center center';
    });
}

function changeImage(src) {
    document.getElementById('mainImage').src = src;
}

// Thumbnail scrolling functionality
function scrollThumbnails(direction) {
    const thumbnailList = document.getElementById('thumbnailList');
    const scrollAmount = 200; // Adjust this value to control scroll distance

    if (direction === -1) {
        // Scroll left
        thumbnailList.scrollBy({
            left: -scrollAmount,
            behavior: 'smooth'
        });
    } else {
        // Scroll right
        thumbnailList.scrollBy({
            left: scrollAmount,
            behavior: 'smooth'
        });
    }

    // Update arrow visibility
    updateArrowVisibility();
}

// Update arrow button visibility based on scroll position
function updateArrowVisibility() {
    const thumbnailList = document.getElementById('thumbnailList');
    const leftArrow = document.querySelector('.thumbnail-arrow-left');
    const rightArrow = document.querySelector('.thumbnail-arrow-right');

    if (!thumbnailList || !leftArrow || !rightArrow) return;

    const isAtStart = thumbnailList.scrollLeft <= 0;
    const isAtEnd = thumbnailList.scrollLeft + thumbnailList.clientWidth >= thumbnailList.scrollWidth - 1;

    // Hide/show arrows based on scroll position
    leftArrow.style.opacity = isAtStart ? '0.3' : '1';
    leftArrow.style.pointerEvents = isAtStart ? 'none' : 'auto';

    rightArrow.style.opacity = isAtEnd ? '0.3' : '1';
    rightArrow.style.pointerEvents = isAtEnd ? 'none' : 'auto';
}

// Initialize arrow visibility on page load
document.addEventListener('DOMContentLoaded', function () {
    updateArrowVisibility();

    // Update arrow visibility when thumbnail list is scrolled
    const thumbnailList = document.getElementById('thumbnailList');
    if (thumbnailList) {
        thumbnailList.addEventListener('scroll', updateArrowVisibility);
    }
});

function updateQty(change) {
    const input = document.getElementById('qtyInput');
    let newVal = parseInt(input.value) + change;
    const max = parseInt(input.getAttribute('max'));

    if (newVal < 1) {
        showToast('Quantity cannot be less than 1', 'error');
        return;
    }

    if (newVal > max) {
        showToast(`Stock limit reached. Only ${max} items available`, 'error');
        return;
    }

    input.value = newVal;
}

// Add to Cart AJAX
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('addToCartBtn')?.addEventListener('click', function () {
        const btn = this;
        const blindboxId = btn.getAttribute('data-blindbox-id');
        const quantity = parseInt(document.getElementById('qtyInput').value);

        // Log for debugging
        console.log('Adding to cart:', {
            blindboxId,
            quantity
        });

        // Disable button during request
        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'ADDING...';

        const url = BASE_URL + 'cartitem/add';
        console.log('Request URL:', url);

        // Make AJAX request
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                blindbox_id: blindboxId,
                quantity: quantity
            })
        })
            .then(response => {
                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);

                // Check if response is ok
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                // Try to parse as JSON
                return response.text().then(text => {
                    console.log('Response text:', text);
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Response was not JSON:', text);
                        throw new Error('Invalid server response');
                    }
                });
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    // Show success message
                    showToast('Added to cart successfully!', 'success');

                    // Update cart count in header
                    updateCartCount();
                } else {
                    showToast(data.error || 'Failed to add to cart', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Re-enable button
                btn.disabled = false;
                btn.textContent = originalText;
            });
    });
});

// Update cart count in header
function updateCartCount() {
    fetch(BASE_URL + 'cartitem/get/count', {
        method: 'POST'
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountEl = document.getElementById('cart-count');
                if (cartCountEl) {
                    cartCountEl.textContent = data.count;
                }
            }
        })
        .catch(error => console.error('Error updating cart count:', error));
}