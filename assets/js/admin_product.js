let currentCategorySort = {
    sortBy: 'category_id',
    sortOrder: 'ASC'
};

let currentProductSort = {
    sortBy: 'blindbox_id',
    sortOrder: 'ASC',
    statusFilter: 'all'
};

let currentCategorySearch = '';
let currentProductSearch = '';
let currentCategoryPage = 1;
let currentProductPage = 1;

let variantCounter = 0;
let editVariantCounter = 0;

// Track cropped images for product upload
let croppedBlobs = {};
let currentImageType = null;
let currentInputId = null;
let existingVariants = {};

// Utility Functions
function toggleDropdown(header) {
    const content = header.nextElementSibling;
    header.classList.toggle('active');
    content.classList.toggle('show');

    // Determine which dropdown this is based on the header text
    const headerText = header.querySelector('h2').textContent;
    const isActive = header.classList.contains('active');

    if (headerText.includes('CATEGORIES')) {
        localStorage.setItem('categoryDropdownState', isActive ? 'open' : 'closed');
    } else if (headerText.includes('PRODUCTS')) {
        localStorage.setItem('productDropdownState', isActive ? 'open' : 'closed');
    }
}

// Restore dropdown states on page load
document.addEventListener('DOMContentLoaded', function () {
    const categoryState = localStorage.getItem('categoryDropdownState');
    const productState = localStorage.getItem('productDropdownState');

    document.querySelectorAll('.dropdown-header').forEach(header => {
        const text = header.querySelector('h2').textContent;
        const content = header.nextElementSibling;

        if (text.includes('CATEGORIES')) {
            if (categoryState === 'open') {
                header.classList.add('active');
                content.classList.add('show');
            } else if (categoryState === 'closed') {
                header.classList.remove('active');
                content.classList.remove('show');
            }
        } else if (text.includes('PRODUCTS')) {
            if (productState === 'open') {
                header.classList.add('active');
                content.classList.add('show');
            } else if (productState === 'closed') {
                header.classList.remove('active');
                content.classList.remove('show');
            }
        }
    });

    // Intercept PHP-generated pagination links for categories
    document.addEventListener('click', function(e) {
        const target = e.target.closest('a[href*="category_page="]');
        if (target && !target.getAttribute('onclick')) {
            e.preventDefault();
            const url = new URL(target.href);
            const page = parseInt(url.searchParams.get('category_page')) || 1;
            loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder, page);
        }
    });

    // Intercept PHP-generated pagination links for products  
    document.addEventListener('click', function(e) {
        const target = e.target.closest('a[href*="product_page="]');
        if (target && !target.getAttribute('onclick')) {
            e.preventDefault();
            const url = new URL(target.href);
            const page = parseInt(url.searchParams.get('product_page')) || 1;
            loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter, page);
        }
    });
});

function escapeHtml(text) {
    if (!text) return 'N/A';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global variable for delete category tracking
let deleteCategoryId = null;
let productReassignments = {};
let deletedVariantIds = [];

// Sort and Search Functions
function sortCategory(column) {
    if (currentCategorySort.sortBy === column) {
        currentCategorySort.sortOrder = currentCategorySort.sortOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        currentCategorySort.sortBy = column;
        currentCategorySort.sortOrder = 'ASC';
    }
    loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
}

function sortProduct(column) {
    if (currentProductSort.sortBy === column) {
        currentProductSort.sortOrder = currentProductSort.sortOrder === 'ASC' ? 'DESC' : 'ASC';
    } else {
        currentProductSort.sortBy = column;
        currentProductSort.sortOrder = 'ASC';
    }
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

function changeCategorySort(sortBy) {
    currentCategorySort.sortBy = sortBy;
    loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
}

function toggleCategorySortOrder() {
    currentCategorySort.sortOrder = currentCategorySort.sortOrder === 'ASC' ? 'DESC' : 'ASC';
    loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
}

function changeProductSort(sortBy) {
    currentProductSort.sortBy = sortBy;
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

function toggleProductSortOrder() {
    currentProductSort.sortOrder = currentProductSort.sortOrder === 'ASC' ? 'DESC' : 'ASC';
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

function changeProductStatusFilter(statusFilter) {
    currentProductSort.statusFilter = statusFilter;
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

function searchCategories(event) {
    event.preventDefault();
    const searchInput = document.querySelector('input[name="category_search"]');
    currentCategorySearch = searchInput ? searchInput.value : '';
    loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
}

function clearCategorySearch() {
    currentCategorySearch = '';
    const searchInput = document.querySelector('input[name="category_search"]');
    if (searchInput) searchInput.value = '';
    loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
}

function searchProducts(event) {
    event.preventDefault();
    const searchInput = document.querySelector('input[name="product_search"]');
    currentProductSearch = searchInput ? searchInput.value : '';
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

function clearProductSearch() {
    currentProductSearch = '';
    const searchInput = document.querySelector('input[name="product_search"]');
    if (searchInput) searchInput.value = '';
    loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
}

// Category Functions
function loadCategories(sortBy, sortOrder, page = null) {
    currentCategorySort = { sortBy, sortOrder };
    if (page !== null) {
        currentCategoryPage = page;
    }

    let url = `${BASE_URL}controllers/admin/AdminProductController.php?action=get_categories&sort_by=${sortBy}&sort_order=${sortOrder}&category_page=${currentCategoryPage}`;
    if (currentCategorySearch) {
        url += `&category_search=${encodeURIComponent(currentCategorySearch)}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderCategoriesTable(result.data, result.pagination);
                updateCategoryHeaders(sortBy, sortOrder);
                
                // Scroll to categories dropdown header after rendering completes
                setTimeout(() => {
                    const categoriesHeader = document.querySelector('#categories-table').closest('.dropdown-container').querySelector('.dropdown-header');
                    if (categoriesHeader) {
                        categoriesHeader.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            } else {
                console.error('Failed to load categories:', result.message);
            }
        })
        .catch(error => console.error('Error loading categories:', error));
}

function renderCategoriesTable(categories, pagination) {
    const tbody = document.querySelector('#categories-table tbody');

    if (categories.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--accent-gray);">No categories found</td></tr>';
        return;
    }

    tbody.innerHTML = categories.map(category => `
        <tr>
            <td style="width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(category.category_id)}">${escapeHtml(category.category_id)}</td>
            <td style="width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(category.category_name)}">${escapeHtml(category.category_name)}</td>
            <td style="width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(category.description)}">${escapeHtml(category.description)}</td>
            <td style="width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${category.created_date}</td>
            <td>
                <a href="#" class="admin-btn admin-btn-small" onclick="viewCategory('${category.category_id}'); return false;">View</a>
                <a href="#" class="admin-btn admin-btn-small admin-btn-secondary" onclick="editCategory('${category.category_id}'); return false;">Edit</a>
                <a href="#" class="admin-btn admin-btn-small admin-btn-danger" 
                onclick="deleteCategory('${category.category_id}'); return false;">Delete</a>
            </td>
        </tr>
    `).join('');

    // Update sort dropdown and button
    const sortSelect = document.getElementById('categorySortSelect');
    if (sortSelect) sortSelect.value = currentCategorySort.sortBy;

    const sortBtn = document.getElementById('categorySortOrderBtn');
    if (sortBtn) sortBtn.innerHTML = currentCategorySort.sortOrder === 'ASC' ? '↑ ASC' : '↓ DESC';

    // Update clear button visibility
    const clearBtn = document.getElementById('clearCategorySearchBtn');
    if (clearBtn) clearBtn.style.display = currentCategorySearch ? '' : 'none';

    // Update sort indicators in headers
    updateCategorySortIndicators();

    // Update results info
    const resultsInfo = document.querySelector('#categories-table').closest('.dropdown-content').querySelector('.results-info');
    if (resultsInfo && pagination) {
        const start = ((pagination.current_page - 1) * 10) + 1;
        const end = Math.min(pagination.current_page * 10, pagination.total_items);
        if (currentCategorySearch) {
            resultsInfo.textContent = `Showing ${categories.length} of ${pagination.total_items} categories matching "${currentCategorySearch}"`;
        } else {
            resultsInfo.textContent = `Showing ${start}-${end} of ${pagination.total_items} categories`;
        }
    }

    // Render pagination
    const paginationContainer = document.querySelector('#categories-table').closest('.dropdown-content').querySelector('.pagination-container');
    if (paginationContainer) {
        if (pagination && pagination.total_pages > 1) {
            renderCategoryPagination(pagination);
        } else {
            paginationContainer.innerHTML = '';
        }
    }
}

function updateCategoryHeaders(sortBy, sortOrder) {
    document.querySelectorAll('#categories-table th[data-sort]').forEach(th => {
        const column = th.getAttribute('data-sort');
        const icon = th.querySelector('.sort-icon');

        if (icon) {
            if (column === sortBy) {
                icon.textContent = sortOrder === 'ASC' ? '▲' : '▼';
                icon.style.display = 'inline';
            } else {
                icon.style.display = 'none';
            }
        }
    });
}

function updateCategorySortIndicators() {
    const columns = ['category_id', 'category_name', 'created_date'];
    columns.forEach(col => {
        const indicator = document.getElementById(`cat-sort-${col}`);
        if (indicator) {
            if (col === currentCategorySort.sortBy) {
                indicator.textContent = currentCategorySort.sortOrder === 'ASC' ? '↑' : '↓';
            } else {
                indicator.textContent = '↕';
            }
        }
    });
}

// Reload category dropdowns in Add/Edit Product modals
function reloadCategoryDropdowns() {
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=get_categories&sort_by=category_name&sort_order=ASC`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const categories = result.data;

                // Update Add Product modal category dropdown
                const addCategorySelect = document.getElementById('category_id');
                if (addCategorySelect) {
                    const currentValue = addCategorySelect.value;
                    addCategorySelect.innerHTML = '<option value="">- Select Category -</option>' +
                        categories.map(cat => `<option value="${escapeHtml(cat.category_id)}">${escapeHtml(cat.category_name)}</option>`).join('');
                    if (currentValue) addCategorySelect.value = currentValue;
                }

                // Update Edit Product modal category dropdown
                const editCategorySelect = document.getElementById('category_id_edit');
                if (editCategorySelect) {
                    const currentValue = editCategorySelect.value;
                    editCategorySelect.innerHTML = '<option value="">- Select Category -</option>' +
                        categories.map(cat => `<option value="${escapeHtml(cat.category_id)}">${escapeHtml(cat.category_name)}</option>`).join('');
                    if (currentValue) editCategorySelect.value = currentValue;
                }
            }
        })
        .catch(error => {
            console.error('Error reloading category dropdowns:', error);
        });
}

// Product Functions
function loadProducts(sortBy, sortOrder, statusFilter, page = null) {
    currentProductSort = { sortBy, sortOrder, statusFilter };
    if (page !== null) {
        currentProductPage = page;
    }

    let url = `${BASE_URL}controllers/admin/AdminProductController.php?action=get_products&product_sort_by=${sortBy}&product_sort_order=${sortOrder}&status_filter=${statusFilter}&product_page=${currentProductPage}`;
    if (currentProductSearch) {
        url += `&product_search=${encodeURIComponent(currentProductSearch)}`;
    }

    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderProductsTable(result.data, result.pagination);
                updateProductHeaders(sortBy, sortOrder, statusFilter);
                
                // Scroll to products dropdown header after rendering completes
                setTimeout(() => {
                    const productsHeader = document.querySelector('#products-table').closest('.dropdown-container').querySelector('.dropdown-header');
                    if (productsHeader) {
                        productsHeader.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            } else {
                console.error('Failed to load products:', result.message);
            }
        })
        .catch(error => console.error('Error loading products:', error));
}

function renderProductsTable(products, pagination) {
    const tbody = document.querySelector('#products-table tbody');

    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--accent-gray);">No products found</td></tr>';
        return;
    }

    tbody.innerHTML = products.map(product => `
        <tr>
            <td style="width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(product.blindbox_id)}">${escapeHtml(product.blindbox_id)}</td>
            <td style="width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(product.product_name)}">${escapeHtml(product.product_name)}</td>
            <td style="width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(product.category_name)}">${escapeHtml(product.category_name)}</td>
            <td style="width: 100px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${product.price}</td>
            <td style="width: 80px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${product.stock_quantity}</td>
            <td style="width: 100px; text-align: center;">
                ${product.status === 'active'
            ? '<span style="color: #22c55e;">Active</span>'
            : '<span style="color: #ef4444;">Out of Stock</span>'}
            </td>
            <td style="white-space: normal;">
                <a href="#" class="admin-btn admin-btn-small" onclick="viewProduct('${product.blindbox_id}'); return false;">View</a>
                <a href="#" class="admin-btn admin-btn-small admin-btn-secondary" onclick="editProduct('${product.blindbox_id}'); return false;">Edit</a>
                <a href="#" class="admin-btn admin-btn-small admin-btn-danger" 
                onclick="deleteProduct('${product.blindbox_id}'); return false;">Delete</a>
            </td>
        </tr>
    `).join('');

    // Update sort dropdown, button, and status filter
    const sortSelect = document.getElementById('productSortSelect');
    if (sortSelect) sortSelect.value = currentProductSort.sortBy;

    const sortBtn = document.getElementById('productSortOrderBtn');
    if (sortBtn) sortBtn.innerHTML = currentProductSort.sortOrder === 'ASC' ? '↑ ASC' : '↓ DESC';

    const statusFilter = document.getElementById('productStatusFilter');
    if (statusFilter) statusFilter.value = currentProductSort.statusFilter;

    // Update clear button visibility
    const clearBtn = document.getElementById('clearProductSearchBtn');
    if (clearBtn) clearBtn.style.display = currentProductSearch ? '' : 'none';

    // Update sort indicators in headers
    updateProductSortIndicators();

    // Update results info
    const resultsInfo = document.querySelector('#products-table').closest('.dropdown-content').querySelector('.results-info');
    if (resultsInfo && pagination) {
        const start = ((pagination.current_page - 1) * 10) + 1;
        const end = Math.min(pagination.current_page * 10, pagination.total_items);
        if (currentProductSearch) {
            resultsInfo.textContent = `Showing ${products.length} of ${pagination.total_items} products matching "${currentProductSearch}"`;
        } else {
            resultsInfo.textContent = `Showing ${start}-${end} of ${pagination.total_items} products`;
        }
    }

    // Render pagination
    const paginationContainer = document.querySelector('#products-table').closest('.dropdown-content').querySelector('.pagination-container');
    if (paginationContainer) {
        if (pagination && pagination.total_pages > 1) {
            renderProductPagination(pagination);
        } else {
            paginationContainer.innerHTML = '';
        }
    }
}

function updateProductHeaders(sortBy, sortOrder, statusFilter) {
    document.querySelectorAll('#products-table th[data-sort]').forEach(th => {
        const column = th.getAttribute('data-sort');
        const icon = th.querySelector('.sort-icon');

        if (icon) {
            if (column === sortBy) {
                if (icon && column !== 'status') {
                    icon.textContent = sortOrder === 'ASC' ? '▲' : '▼';
                    icon.style.display = 'inline';
                }
            } else if (column !== 'status') {
                if (icon) {
                    icon.style.display = 'none';
                }
            }
        }
    });

    // Update status filter label
    const statusTh = document.querySelector('#products-table th[data-sort="status"]');
    if (statusTh) {
        const label = statusTh.querySelector('.status-label');
        if (label) {
            const statusLabels = {
                'all': 'Status (All)',
                'active': 'Status (Active)',
                'out_of_stock': 'Status (Out of Stock)'
            };
            label.textContent = statusLabels[statusFilter] || 'Status';
        }
    }
}

function updateProductSortIndicators() {
    const columns = ['blindbox_id', 'product_name', 'category_id', 'price', 'stock_quantity'];
    columns.forEach(col => {
        const indicator = document.getElementById(`prod-sort-${col}`);
        if (indicator) {
            if (col === currentProductSort.sortBy) {
                indicator.textContent = currentProductSort.sortOrder === 'ASC' ? '↑' : '↓';
            } else {
                indicator.textContent = '↕';
            }
        }
    });
}

// Category Modal Functions
function showAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'flex';
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').style.display = 'none';
    document.getElementById('addCategoryForm').reset();
}

function submitAddCategory(event) {
    event.preventDefault();

    const formData = new FormData(event.target);
    formData.append('action', 'add_category');

    fetch(`${BASE_URL}controllers/admin/AdminProductController.php`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                closeAddCategoryModal();
                // Reload categories table without full page reload
                currentCategoryPage = 1;
                loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
                showToast(result.message, 'success');
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
}

// Product Modal Functions
function showAddProductModal() {
    const modal = document.getElementById('addProductModal');
    modal.style.display = 'flex';

    // Reset scroll position to top
    const modalContent = modal.querySelector('.modal-content');
    if (modalContent) {
        modalContent.scrollTop = 0;
    }

    // Add first variant automatically if none exist
    if (document.getElementById('productVariantsContainer').children.length === 0) {
        addProductVariant();
    }
}

function closeAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
    document.getElementById('addProductForm').reset();
    document.getElementById('productVariantsContainer').innerHTML = '';
    variantCounter = 0;
    croppedBlobs = {};

    // Reset blindbox image preview
    const blindboxFrame = document.getElementById('blindbox-preview-frame');
    const existingImg = blindboxFrame.querySelector('img:not(.image-preview-placeholder *)');
    if (existingImg) {
        existingImg.remove();
    }
    const placeholder = blindboxFrame.querySelector('.image-preview-placeholder');
    if (placeholder) {
        placeholder.style.display = 'flex';
    }
}

function addProductVariant() {
    variantCounter++;
    const container = document.getElementById('productVariantsContainer');

    const variantHtml = `
        <div class="product-variant" id="variant-${variantCounter}" data-variant-id="${variantCounter}">
            <div class="variant-header">
                <h4>Variant ${variantCounter}</h4>
                <button type="button" class="variant-remove" onclick="removeProductVariant(${variantCounter})">&times;</button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Variant Image <span class="required">*</span></label>
                    <div class="image-preview-frame" id="variant-preview-${variantCounter}" onclick="document.getElementById('variant_image_${variantCounter}').click()">
                        <div class="image-preview-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <div>Click to upload</div>
                        </div>
                        <div class="image-preview-overlay">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Upload Picture</span>
                        </div>
                    </div>
                    <input type="file" id="variant_image_${variantCounter}" name="variants[${variantCounter}][image]" accept="image/*" style="display: none;">
                </div>
                
                <div class="form-group">
                    <label>Variant Name</label>
                    <input type="text" name="variants[${variantCounter}][name]"
                    placeholder="Leave blank for auto-naming">
                </div>
            </div>
            
            <div class="front-image-selector">
                <input type="radio" name="front_image" value="${variantCounter}"
                id="front_${variantCounter}" ${variantCounter === 1 ? 'checked' : ''}>
                <label for="front_${variantCounter}">Set as front image</label>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', variantHtml);

    // Attach event listener for image upload
    attachImageUploadListener(`variant_image_${variantCounter}`, `variant-${variantCounter}`);
}

function removeProductVariant(id) {
    const variant = document.getElementById(`variant-${id}`);
    const container = document.getElementById('productVariantsContainer');

    // Don't allow removing the last variant
    if (container.children.length <= 1) {
        showToast('At least one variant is required', 'error');
        return;
    }

    const wasFront = document.getElementById(`front_${id}`).checked;
    variant.remove();

    // If removed variant was front image, select first remaining variant as front
    if (wasFront) {
        const firstRadio = container.querySelector('input[name="front_image"]');
        if (firstRadio) {
            firstRadio.checked = true;
        }
    }
}

// Image Upload and Crop Functions
function attachImageUploadListener(inputId, imageType) {
    const input = document.getElementById(inputId);
    if (!input) return;

    input.addEventListener('change', function (e) {
        const file = e.target.files[0];

        if (!file) {
            console.log('[Product-Upload] No file selected');
            return;
        }

        console.log('[Product-Upload] File selected:', file.name, file.type, file.size);

        // Validate file size (must be > 0 and <= 2MB)
        const maxSize = 2 * 1024 * 1024;

        if (file.size <= 0) {
            showToast('Selected file is empty (0 bytes)', 'error');
            e.target.value = '';
            return;
        }

        if (file.size > maxSize) {
            showToast('File size must be less than 2MB', 'error');
            e.target.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            showToast('Only JPG, PNG, and GIF files are allowed', 'error');
            e.target.value = '';
            return;
        }

        // Validate file extension
        const fileName = file.name.toLowerCase();
        const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif'];
        const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

        if (!hasValidExtension) {
            showToast('Only JPG, JPEG, PNG, and GIF files are allowed', 'error');
            e.target.value = '';
            return;
        }

        console.log('[Product-Upload] Validations passed, proceeding');

        currentInputId = inputId;
        currentImageType = imageType;

        const reader = new FileReader();
        reader.onload = function (event) {
            const img = new Image();
            img.onload = function () {
                // Validate image dimensions
                const maxWidth = 5000;
                const maxHeight = 5000;
                if (img.width > maxWidth || img.height > maxHeight) {
                    showToast(`Image dimensions too large. Maximum: ${maxWidth}x${maxHeight}px`, 'error');
                    e.target.value = '';
                    return;
                }

                // Check if image is square
                if (img.width === img.height) {
                    croppedBlobs[currentImageType] = file;
                    updateImagePreview(currentImageType, event.target.result);
                } else {
                    showProductCropModal(event.target.result, img.width, img.height);
                }
            };
            img.onerror = function () {
                showToast('Failed to load image. File may be corrupted', 'error');
                e.target.value = '';
            };
            img.src = event.target.result;
        };
        reader.onerror = function () {
            showToast('Failed to read file', 'error');
            e.target.value = '';
        };
        reader.readAsDataURL(file);
    });
}

// Wrapper function to show crop modal using photo_upload.js library
function showProductCropModal(imageSrc, imgWidth, imgHeight) {
    const cropModal = document.getElementById('crop-modal');
    const cropImage = document.getElementById('crop-image');
    const cropContainer = document.getElementById('crop-container');

    if (!cropModal || !cropImage || !cropContainer) {
        console.error('[Product-Upload] Crop modal elements not found');
        return;
    }

    cropImage.src = imageSrc;
    cropImage.style.width = 'auto';
    cropImage.style.height = 'auto';
    cropImage.style.maxWidth = 'none';
    cropImage.style.maxHeight = 'none';
    cropImage.style.transform = 'none';
    cropImage.style.position = 'absolute';
    cropImage.style.top = '0';
    cropImage.style.left = '0';

    cropModal.classList.add('active');

    setTimeout(() => {
        // Use initCropSelection from photo_upload.js library
        if (typeof window.initCropSelection === 'function') {
            window.initCropSelection(imgWidth, imgHeight);
        }
    }, 50);
}

// Update image preview after cropping
function updateImagePreview(imageType, imageSrc) {
    let frame;
    if (imageType === 'blindbox') {
        frame = document.getElementById('blindbox-preview-frame');
    } else if (imageType === 'blindbox_edit') {
        frame = document.getElementById('blindbox-preview-frame-edit');
    } else if (imageType.startsWith('edit-variant-')) {
        const variantId = imageType.replace('edit-variant-', '');
        frame = document.getElementById(`edit-variant-preview-${variantId}`);
    } else {
        const variantId = imageType.replace('variant-', '');
        frame = document.getElementById(`variant-preview-${variantId}`);
    }

    if (!frame) return;

    // Remove existing image if any
    const existingImg = frame.querySelector('img:not(.image-preview-placeholder *)');
    if (existingImg) {
        existingImg.remove();
    }

    // Hide placeholder
    const placeholder = frame.querySelector('.image-preview-placeholder');
    if (placeholder) {
        placeholder.style.display = 'none';
    }

    // Add new image
    const img = document.createElement('img');
    img.src = imageSrc;
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';
    frame.insertBefore(img, frame.firstChild);
}

let isSubmittingProduct = false;

function submitAddProduct(event) {
    event.preventDefault();

    // Prevent double submission
    if (isSubmittingProduct) {
        console.log('Already submitting, ignoring...');
        return;
    }
    isSubmittingProduct = true;

    const formData = new FormData();

    // Add basic fields
    formData.append('product_name', document.getElementById('product_name').value);
    formData.append('category_id', document.getElementById('category_id').value);
    formData.append('price', document.getElementById('price').value);
    formData.append('stock_quantity', document.getElementById('stock_quantity').value);

    // Get description from the form itself to avoid ID conflicts
    const descriptionField = document.querySelector('#addProductForm textarea[name="description"]');
    formData.append('description', descriptionField ? descriptionField.value : '');

    // Check blindbox image
    if (!croppedBlobs.blindbox) {
        showToast('Please upload a blindbox image', 'error');
        isSubmittingProduct = false;
        return;
    }

    formData.append('blindbox_image', croppedBlobs.blindbox, 'blindbox.jpg');

    // Add variants
    const variants = document.getElementById('productVariantsContainer').querySelectorAll('.product-variant');

    if (variants.length === 0) {
        showToast('At least one variant is required', 'error');
        return;
    }

    // Get front image index
    let frontImageIndex = null;
    const frontImageRadio = document.querySelector('input[name="front_image"]:checked');
    if (frontImageRadio) {
        const frontVariantId = frontImageRadio.value;
        frontImageIndex = Array.from(variants).findIndex(v => v.dataset.variantId === frontVariantId);
    } else {
        frontImageIndex = 0; // Default to first
    }

    // Process variants with auto-naming
    let autoNameCounter = 1;
    const categorySelect = document.getElementById('category_id');
    const categoryName = categorySelect.options[categorySelect.selectedIndex].text;

    let missingImage = false;
    variants.forEach((variant, index) => {
        const variantId = variant.dataset.variantId;
        const variantNameInput = variant.querySelector(`input[name="variants[${variantId}][name]"]`);
        let variantName = variantNameInput.value.trim();
        const variantImage = croppedBlobs[`variant-${variantId}`];

        if (!variantImage) {
            showToast(`Please upload an image for variant ${parseInt(index) + 1}`, 'error');
            missingImage = true;
            return;
        }

        // Auto-name logic: if blank name, use "CategoryName - Variant X"
        // Front image is always Variant 1
        if (!variantName) {
            if (index === frontImageIndex) {
                variantName = `${categoryName} - Variant 1`;
            } else {
                autoNameCounter++;
                variantName = `${categoryName} - Variant ${autoNameCounter}`;
            }
        }

        formData.append(`variants[${index}][name]`, variantName);
        formData.append(`variants[${index}][image]`, variantImage, `variant_${index}.jpg`);
    });

    if (missingImage) {
        isSubmittingProduct = false;
        return;
    }

    formData.append('front_image_index', frontImageIndex);

    // Submit
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=add_product`, {
        method: 'POST',
        body: formData
    })
        .then(response => {
            // Log the response for debugging
            return response.text().then(text => {
                console.log('Server response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Failed to parse JSON:', e);
                    console.error('Response text:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(result => {
            isSubmittingProduct = false;
            if (result.success) {
                showToast(result.message, 'success');
                closeAddProductModal();
                // Redirect to page 1 preserving existing parameters
                const params = new URLSearchParams(window.location.search);
                params.set('product_page', '1');
                window.location.href = `${window.location.pathname}?${params.toString()}`;
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            isSubmittingProduct = false;
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
}

// View Functions
function viewCategory(categoryId) {
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=get_category&id=${categoryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                document.getElementById('view_category_id').textContent = data.category_id;
                document.getElementById('view_category_name').textContent = data.category_name;
                document.getElementById('view_category_description').textContent = data.description;
                document.getElementById('view_category_created').textContent = data.created_date;
                document.getElementById('viewCategoryModal').style.display = 'flex';
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to load category details', 'error');
        });
}

function closeViewCategoryModal() {
    document.getElementById('viewCategoryModal').style.display = 'none';
}

function viewProduct(blindboxId) {
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=get_product&id=${blindboxId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                document.getElementById('view_product_id').textContent = data.blindbox_id;
                document.getElementById('view_product_name').textContent = data.product_name;
                document.getElementById('view_product_category').textContent = data.category_name;
                document.getElementById('view_product_price').textContent = 'RM ' + data.price;
                document.getElementById('view_product_stock').textContent = data.stock_quantity;
                document.getElementById('view_product_description').textContent = data.description;
                document.getElementById('view_product_status').textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                document.getElementById('view_product_created').textContent = data.created_date;

                // Set blindbox image
                if (data.blindbox_image) {
                    document.getElementById('view_blindbox_image').src = BASE_URL + data.blindbox_image;
                } else {
                    document.getElementById('view_blindbox_image').src = '';
                }

                // Display variants
                const variantsContainer = document.getElementById('viewProductVariantsContainer');
                variantsContainer.innerHTML = '';

                data.variants.forEach(variant => {
                    const variantDiv = document.createElement('div');
                    variantDiv.style.border = '1px solid var(--border-color)';
                    variantDiv.style.padding = '1rem';
                    variantDiv.style.textAlign = 'center';

                    if (variant.images.length > 0) {
                        const img = document.createElement('img');
                        img.src = BASE_URL + variant.images[0];
                        img.style.width = '100%';
                        img.style.height = '150px';
                        img.style.objectFit = 'contain';
                        img.style.marginBottom = '0.5rem';
                        variantDiv.appendChild(img);
                    }

                    const name = document.createElement('div');
                    name.textContent = variant.variant_name;
                    name.style.fontWeight = '600';
                    name.style.marginBottom = '0.25rem';
                    variantDiv.appendChild(name);

                    if (variant.is_front) {
                        const badge = document.createElement('div');
                        badge.textContent = 'Front Image';
                        badge.style.fontSize = '0.75rem';
                        badge.style.color = '#22c55e';
                        variantDiv.appendChild(badge);
                    }

                    variantsContainer.appendChild(variantDiv);
                });

                document.getElementById('viewProductModal').style.display = 'flex';
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to load product details', 'error');
        });
}

function closeViewProductModal() {
    document.getElementById('viewProductModal').style.display = 'none';
}

// Edit Product Functions
function editProduct(blindboxId) {
    // Clear deleted variants tracking when opening edit modal
    deletedVariantIds = [];
    // Clear the variants container to ensure fresh start
    document.getElementById('editProductVariantsContainer').innerHTML = '';
    editVariantCounter = 0;
    
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=get_product&id=${blindboxId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                console.log('[Edit Product] Loaded product data:', data);

                // Populate basic fields
                document.getElementById('blindbox_id_edit').value = data.blindbox_id;
                document.getElementById('product_name_edit').value = data.product_name;
                document.getElementById('category_id_edit').value = data.category_id;
                document.getElementById('price_edit').value = data.price;
                document.getElementById('stock_quantity_edit').value = data.stock_quantity;
                document.getElementById('product_description_edit').value = data.description;

                // Set blindbox image
                const blindboxImgEdit = document.getElementById('current_blindbox_image_edit');
                if (blindboxImgEdit) {
                    if (data.blindbox_image && data.blindbox_image !== 'N/A') {
                        blindboxImgEdit.src = `${BASE_URL}${data.blindbox_image}`;
                        blindboxImgEdit.style.display = 'block';
                    } else {
                        blindboxImgEdit.src = '';
                        blindboxImgEdit.style.display = 'none';
                    }
                }

                // Load variants
                const variantsContainer = document.getElementById('editProductVariantsContainer');
                variantsContainer.innerHTML = '';
                editVariantCounter = 0;
                existingVariants = {};

                if (data.variants && data.variants.length > 0) {
                    data.variants.forEach((variant, index) => {
                        editVariantCounter++;
                        const variantImagePath = (variant.images && variant.images.length > 0) ? variant.images[0] : '';
                        existingVariants[editVariantCounter] = {
                            product_id: variant.product_id,
                            image_path: variantImagePath
                        };

                        const imageSrc = (variantImagePath && variantImagePath !== 'N/A')
                            ? `${BASE_URL}${variantImagePath}`
                            : '';

                        const imageDisplay = imageSrc
                            ? `style="display: block;"`
                            : `style="display: none;"`;

                        const placeholderDisplay = imageSrc
                            ? `style="display: none;"`
                            : `style="display: flex;"`;

                        const variantHtml = `
                            <div class="product-variant" id="edit-variant-${editVariantCounter}" data-variant-id="${editVariantCounter}" data-product-id="${variant.product_id || ''}">
                                <div class="variant-header">
                                    <h4>${escapeHtml(variant.variant_name || 'Variant ' + editVariantCounter)}</h4>
                                    <button type="button" class="variant-remove" onclick="removeEditProductVariant(${editVariantCounter})">&times;</button>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Variant Image <span class="required">*</span></label>
                                        <div class="image-preview-frame" id="edit-variant-preview-${editVariantCounter}">
                                            <img src="${imageSrc || ''}" alt="Variant" style="width: 100%; height: 100%; object-fit: contain; ${imageSrc ? '' : 'display: none;'}">
                                            <div class="image-preview-placeholder" ${placeholderDisplay}>
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                <div>No Image</div>
                                            </div>
                                            <div class="image-preview-overlay" onclick="document.getElementById('edit_variant_image_${editVariantCounter}').click()">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                <span>Change Picture</span>
                                            </div>
                                        </div>
                                        <input type="file" id="edit_variant_image_${editVariantCounter}" name="edit_variants[${editVariantCounter}][image]" accept="image/*" style="display: none;">
                                        <input type="hidden" name="edit_variants[${editVariantCounter}][product_id]" value="${variant.product_id || ''}">
                                        <input type="hidden" name="edit_variants[${editVariantCounter}][current_image]" value="${variantImagePath || ''}">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Variant Name</label>
                                        <input type="text" name="edit_variants[${editVariantCounter}][name]" value="${escapeHtml(variant.variant_name || '')}">
                                    </div>
                                </div>
                                
                                <div class="front-image-selector">
                                    <input type="radio" name="edit_front_image" value="${editVariantCounter}"
                                    id="edit_front_${editVariantCounter}" ${variant.is_front ? 'checked' : ''}>
                                    <label for="edit_front_${editVariantCounter}">Set as front image</label>
                                </div>
                            </div>
                        `;

                        variantsContainer.insertAdjacentHTML('beforeend', variantHtml);

                        // Attach event listener for image upload
                        attachImageUploadListener(`edit_variant_image_${editVariantCounter}`, `edit-variant-${editVariantCounter}`);
                    });
                }

                // Attach event listener for blindbox image in edit mode
                attachImageUploadListener('blindbox_image_edit', 'blindbox_edit');

                // Show modal and reset scroll position
                const editModal = document.getElementById('editProductModal');
                editModal.style.display = 'flex';
                const editModalContent = editModal.querySelector('.modal-content');
                if (editModalContent) {
                    editModalContent.scrollTop = 0;
                }
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to load product details', 'error');
        });
}

function closeEditProductModal() {
    document.getElementById('editProductModal').style.display = 'none';
    document.getElementById('editProductForm').reset();
    document.getElementById('editProductVariantsContainer').innerHTML = '';
    editVariantCounter = 0;
    existingVariants = {};
    // Clear deleted variants tracking when closing modal
    deletedVariantIds = [];
    // Clear any cropped blobs related to edit mode
    Object.keys(croppedBlobs).forEach(key => {
        if (key.includes('edit')) {
            delete croppedBlobs[key];
        }
    });
}

function addEditProductVariant() {
    editVariantCounter++;
    const container = document.getElementById('editProductVariantsContainer');

    const variantHtml = `
        <div class="product-variant" id="edit-variant-${editVariantCounter}" data-variant-id="${editVariantCounter}" data-is-new="true">
            <div class="variant-header">
                <h4>New Variant ${editVariantCounter}</h4>
                <button type="button" class="variant-remove" onclick="removeEditProductVariant(${editVariantCounter})">&times;</button>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Variant Image <span class="required">*</span></label>
                    <div class="image-preview-frame" id="edit-variant-preview-${editVariantCounter}" onclick="document.getElementById('edit_variant_image_${editVariantCounter}').click()">
                        <div class="image-preview-placeholder">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <div>Click to upload</div>
                        </div>
                        <div class="image-preview-overlay">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Upload Picture</span>
                        </div>
                    </div>
                    <input type="file" id="edit_variant_image_${editVariantCounter}" name="new_variants[${editVariantCounter}][image]" accept="image/*" style="display: none;">
                </div>
                
                <div class="form-group">
                    <label>Variant Name</label>
                    <input type="text" name="new_variants[${editVariantCounter}][name]" placeholder="Leave blank for auto-naming">
                </div>
            </div>
            
            <div class="front-image-selector">
                <input type="radio" name="edit_front_image" value="${editVariantCounter}"
                id="edit_front_${editVariantCounter}">
                <label for="edit_front_${editVariantCounter}">Set as front image</label>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', variantHtml);

    // Attach event listener for image upload
    attachImageUploadListener(`edit_variant_image_${editVariantCounter}`, `edit-variant-${editVariantCounter}`);
}

function removeEditProductVariant(id) {
    const variant = document.getElementById(`edit-variant-${id}`);
    const container = document.getElementById('editProductVariantsContainer');

    // Don't allow removing the last variant
    if (container.children.length <= 1) {
        showToast('At least one variant is required', 'error');
        return;
    }

    // Track deletion if it's an existing variant (has productId)
    const productId = variant.dataset.productId;
    const isNew = variant.dataset.isNew === 'true';
    if (!isNew && productId) {
        deletedVariantIds.push(productId);
    }

    const wasFront = document.getElementById(`edit_front_${id}`).checked;
    variant.remove();

    // If removed variant was front image, select first remaining variant as front
    if (wasFront) {
        const firstRadio = container.querySelector('input[name="edit_front_image"]');
        if (firstRadio) {
            firstRadio.checked = true;
        }
    }

    // Clean up cropped blob if exists
    if (croppedBlobs[`edit-variant-${id}`]) {
        delete croppedBlobs[`edit-variant-${id}`];
    }
}

function submitEditProduct(event) {
    event.preventDefault();

    const formData = new FormData();

    // Add blindbox ID
    const blindboxId = document.getElementById('blindbox_id_edit').value;
    formData.append('blindbox_id', blindboxId);

    // Add basic fields
    formData.append('product_name', document.getElementById('product_name_edit').value);
    formData.append('category_id', document.getElementById('category_id_edit').value);
    formData.append('price', document.getElementById('price_edit').value);
    formData.append('stock_quantity', document.getElementById('stock_quantity_edit').value);
    formData.append('description', document.getElementById('product_description_edit').value);

    // Handle blindbox image - only if a new one was uploaded
    if (croppedBlobs.blindbox_edit) {
        formData.append('new_blindbox_image', croppedBlobs.blindbox_edit, 'blindbox.jpg');
    }

    // Process variants
    const variants = document.getElementById('editProductVariantsContainer').querySelectorAll('.product-variant');

    if (variants.length === 0) {
        showToast('At least one variant is required', 'error');
        return;
    }

    // Get front image selection
    const frontImageRadio = document.querySelector('input[name="edit_front_image"]:checked');
    let frontImageVariantId = frontImageRadio ? frontImageRadio.value : null;

    let existingVariantIndex = 0;
    let newVariantIndex = 0;
    let hasError = false;

    variants.forEach((variant) => {
        const variantId = variant.dataset.variantId;
        const isNew = variant.dataset.isNew === 'true';
        const productId = variant.dataset.productId;

        if (isNew) {
            // New variant
            const variantNameInput = variant.querySelector(`input[name="new_variants[${variantId}][name]"]`);
            const variantName = variantNameInput ? variantNameInput.value.trim() : '';
            const variantImage = croppedBlobs[`edit-variant-${variantId}`];

            if (!variantImage) {
                showToast(`Please upload an image for new variant`, 'error');
                hasError = true;
                return;
            }

            formData.append(`new_variants[${newVariantIndex}][name]`, variantName);
            formData.append(`new_variants[${newVariantIndex}][image]`, variantImage, `variant_${newVariantIndex}.jpg`);
            formData.append(`new_variants[${newVariantIndex}][is_front]`, variantId === frontImageVariantId ? '1' : '0');
            newVariantIndex++;
        } else {
            // Existing variant
            const variantNameInput = variant.querySelector(`input[name="edit_variants[${variantId}][name]"]`);
            const variantName = variantNameInput ? variantNameInput.value.trim() : '';
            const variantImage = croppedBlobs[`edit-variant-${variantId}`];
            const currentImageInput = variant.querySelector(`input[name="edit_variants[${variantId}][current_image]"]`);
            const currentImagePath = currentImageInput ? currentImageInput.value : '';

            formData.append(`existing_variants[${existingVariantIndex}][product_id]`, productId);
            formData.append(`existing_variants[${existingVariantIndex}][name]`, variantName);
            formData.append(`existing_variants[${existingVariantIndex}][is_front]`, variantId === frontImageVariantId ? '1' : '0');
            formData.append(`existing_variants[${existingVariantIndex}][current_image]`, currentImagePath);

            // Only append new image if one was uploaded
            if (variantImage) {
                formData.append(`existing_variants[${existingVariantIndex}][image]`, variantImage, `variant_${existingVariantIndex}.jpg`);
            }

            existingVariantIndex++;
        }
    });

    if (hasError) {
        return;
    }

    // Add deleted variants
    if (deletedVariantIds.length > 0) {
        deletedVariantIds.forEach((productId, index) => {
            formData.append(`variants_to_delete[${index}]`, productId);
        });
    }

    // Submit
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=update_product`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                // Clear deleted variants tracking
                deletedVariantIds = [];
                closeEditProductModal();
                // Force reload products table
                loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter, currentProductPage);
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
}

// Edit Category Function
function editCategory(categoryId) {
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=get_category&id=${categoryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const data = result.data;
                document.getElementById('edit_cat_category_id').value = data.category_id;
                document.getElementById('category_name_edit').value = data.category_name;
                document.getElementById('description_edit').value = data.description === 'N/A' ? '' : data.description;
                document.getElementById('editCategoryModal').style.display = 'flex';
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to load category details', 'error');
        });
}

function closeEditCategoryModal() {
    document.getElementById('editCategoryModal').style.display = 'none';
    document.getElementById('editCategoryForm').reset();
}

function submitEditCategory(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=update_category`, {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                closeEditCategoryModal();
                loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
                reloadCategoryDropdowns();
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to update category', 'error');
        });
}

// Delete Category Functions
function deleteCategory(categoryId) {
    deleteCategoryId = categoryId;
    productReassignments = {};

    // Check if category has products
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=check_category_products&category_id=${categoryId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                if (result.hasProducts) {
                    // Show reassignment modal with products
                    showProductReassignmentModal(result.products, result.categories);
                } else {
                    // No products, show direct confirmation
                    showFinalDeleteCategoryModal();
                }
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to check category products', 'error');
        });
}

function showProductReassignmentModal(products, categories) {
    const listContainer = document.getElementById('productReassignmentList');
    listContainer.innerHTML = '';

    products.forEach(product => {
        const productItem = document.createElement('div');
        productItem.style.cssText = 'margin-bottom: 1rem; padding: 1rem; border: 1px solid var(--border-color); border-radius: 4px;';

        productItem.innerHTML = `
            <div style="margin-bottom: 0.5rem;">
                <strong>${escapeHtml(product.product_name)}</strong>
                <span style="color: var(--accent-gray); font-size: 0.875rem;"> (${escapeHtml(product.blindbox_id)})</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="flex-shrink: 0;">New Category:</label>
                <select class="reassignment-select" data-product-id="${escapeHtml(product.blindbox_id)}" 
                        style="flex-grow: 1; padding: 0.5rem; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-color); font-family: inherit;">
                    <option value="">- Select Category -</option>
                    ${categories.map(cat => {
            if (cat.category_id !== deleteCategoryId) {
                return `<option value="${escapeHtml(cat.category_id)}">${escapeHtml(cat.category_name)}</option>`;
            }
            return '';
        }).join('')}
                </select>
            </div>
        `;

        listContainer.appendChild(productItem);
    });

    // Add change listeners to track selections
    document.querySelectorAll('.reassignment-select').forEach(select => {
        select.addEventListener('change', function () {
            const productId = this.getAttribute('data-product-id');
            const newCategoryId = this.value;
            if (newCategoryId) {
                productReassignments[productId] = newCategoryId;
            } else {
                delete productReassignments[productId];
            }
        });
    });

    document.getElementById('deleteCategoryModal').style.display = 'flex';
}

function closeDeleteCategoryModal() {
    document.getElementById('deleteCategoryModal').style.display = 'none';
    deleteCategoryId = null;
    productReassignments = {};
}

function confirmDeleteCategory() {
    // Check if all products have been reassigned
    const selectElements = document.querySelectorAll('.reassignment-select');
    const allAssigned = Array.from(selectElements).every(select => select.value !== '');

    if (!allAssigned) {
        showToast('Please assign a new category to all products', 'error');
        return;
    }

    // Reassign products first
    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=reassign_products`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            reassignments: productReassignments
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Refresh products table to show reassigned categories
                loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
                // Close reassignment modal without clearing deleteCategoryId
                document.getElementById('deleteCategoryModal').style.display = 'none';
                productReassignments = {};
                showFinalDeleteCategoryModal();
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to reassign products', 'error');
        });
}

function showFinalDeleteCategoryModal() {
    document.getElementById('finalDeleteCategoryModal').style.display = 'flex';
}

function closeFinalDeleteCategoryModal() {
    document.getElementById('finalDeleteCategoryModal').style.display = 'none';
    deleteCategoryId = null;
    productReassignments = {};
}

function executeFinalDeleteCategory() {
    if (!deleteCategoryId) return;

    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=delete_category`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            category_id: deleteCategoryId
        })
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                closeFinalDeleteCategoryModal();
                loadCategories(currentCategorySort.sortBy, currentCategorySort.sortOrder);
                reloadCategoryDropdowns();
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to delete category', 'error');
        });
}

// Delete Product Function
let productToDelete = null;

function deleteProduct(blindboxId) {
    productToDelete = blindboxId;
    document.getElementById('deleteConfirmModal').style.display = 'flex';
}

function closeDeleteConfirmModal() {
    document.getElementById('deleteConfirmModal').style.display = 'none';
    productToDelete = null;
}

function confirmDeleteProduct() {
    if (!productToDelete) return;

    fetch(`${BASE_URL}controllers/admin/AdminProductController.php?action=delete_product`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `blindbox_id=${encodeURIComponent(productToDelete)}`
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                showToast(result.message, 'success');
                closeDeleteConfirmModal();
                loadProducts(currentProductSort.sortBy, currentProductSort.sortOrder, currentProductSort.statusFilter);
            } else {
                showToast(result.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Failed to delete product', 'error');
        });
}

// Event Listeners
window.onclick = function (event) {
    const categoryModal = document.getElementById('addCategoryModal');
    const productModal = document.getElementById('addProductModal');
    const editProductModal = document.getElementById('editProductModal');
    const cropModal = document.getElementById('product-crop-modal');
    const viewCategoryModal = document.getElementById('viewCategoryModal');
    const viewProductModal = document.getElementById('viewProductModal');
    const editCategoryModal = document.getElementById('editCategoryModal');
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');

    if (event.target === categoryModal) {
        closeAddCategoryModal();
    } else if (event.target === productModal) {
        closeAddProductModal();
    } else if (event.target === editProductModal) {
        closeEditProductModal();
    } else if (event.target === cropModal) {
        closeCropModal();
    } else if (event.target === viewCategoryModal) {
        closeViewCategoryModal();
    } else if (event.target === viewProductModal) {
        closeViewProductModal();
    } else if (event.target === editCategoryModal) {
        closeEditCategoryModal();
    } else if (event.target === deleteConfirmModal) {
        closeDeleteConfirmModal();
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // Override crop confirm behavior for product images
    const cropConfirmBtn = document.getElementById('crop-confirm-btn');
    if (cropConfirmBtn) {
        // Remove existing listeners and add product-specific handler
        const newCropConfirmBtn = cropConfirmBtn.cloneNode(true);
        cropConfirmBtn.parentNode.replaceChild(newCropConfirmBtn, cropConfirmBtn);

        newCropConfirmBtn.addEventListener('click', function () {
            // Get crop data from photo_upload.js library
            const cropImage = document.getElementById('crop-image');
            const cropContainer = document.getElementById('crop-container');

            if (!cropImage || !cropContainer) return;

            // Create canvas from crop selection (photo_upload.js manages the selection)
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // Get selection from photo_upload.js global state
            const cropModal = document.getElementById('crop-modal');
            const selection = cropModal.querySelector('div[style*="position: absolute"][style*="border"]');

            if (!selection) return;

            const selectionRect = selection.getBoundingClientRect();
            const containerRect = cropContainer.getBoundingClientRect();
            const imageRect = cropImage.getBoundingClientRect();

            const size = parseFloat(selection.style.width);
            canvas.width = size;
            canvas.height = size;

            const img = new Image();
            img.src = cropImage.src;

            const scaleX = img.width / cropImage.offsetWidth;
            const scaleY = img.height / cropImage.offsetHeight;

            const selectionX = parseFloat(selection.style.left);
            const selectionY = parseFloat(selection.style.top);

            const sourceX = selectionX * scaleX;
            const sourceY = selectionY * scaleY;
            const sourceSize = size * scaleX;

            ctx.drawImage(img, sourceX, sourceY, sourceSize, sourceSize, 0, 0, size, size);

            canvas.toBlob(function (blob) {
                if (!blob) {
                    showToast('Failed to create cropped image', 'error');
                    return;
                }

                // Validate cropped blob
                const maxSize = 2 * 1024 * 1024;
                if (blob.size <= 0) {
                    showToast('Cropped image is empty', 'error');
                    return;
                }
                if (blob.size > maxSize) {
                    showToast('Cropped image size exceeds 2MB', 'error');
                    return;
                }

                // Store blob
                croppedBlobs[currentImageType] = blob;

                // Update preview
                const croppedImage = canvas.toDataURL('image/jpeg', 0.9);
                updateImagePreview(currentImageType, croppedImage);

                // Close modal
                cropModal.classList.remove('active');

                currentInputId = null;
                currentImageType = null;
            }, 'image/jpeg', 0.9);
        });
    }

    // Handle crop cancel
    const cropCancelBtn = document.getElementById('crop-cancel-btn');
    const cropModalClose = document.getElementById('crop-modal-close');

    [cropCancelBtn, cropModalClose].forEach(btn => {
        if (btn) {
            const newBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(newBtn, btn);

            newBtn.addEventListener('click', function () {
                const cropModal = document.getElementById('crop-modal');
                if (cropModal) {
                    cropModal.classList.remove('active');
                }

                // Reset file input
                if (currentInputId) {
                    const input = document.getElementById(currentInputId);
                    if (input) {
                        input.value = '';
                    }
                }

                currentInputId = null;
                currentImageType = null;
            });
        }
    });

    // Category table sorting
    document.querySelectorAll('#categories-table th[data-sort] .sortable-header').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const th = this.closest('th');
            const sortBy = th.getAttribute('data-sort');

            // Build URL with pagination preserved
            const params = new URLSearchParams(window.location.search);

            // Read current sort from URL parameters
            const currentSortBy = params.get('sort_by') || 'category_id';
            const currentOrder = params.get('sort_order') || 'ASC';
            const newOrder = (currentSortBy === sortBy && currentOrder === 'ASC') ? 'DESC' : 'ASC';

            params.set('sort_by', sortBy);
            params.set('sort_order', newOrder);
            // Reset to page 1 when sorting
            params.set('category_page', '1');
            window.location.href = window.location.pathname + '?' + params.toString();
        });
    });

    // Product table sorting and status filter
    document.querySelectorAll('#products-table th[data-sort] .sortable-header').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const th = this.closest('th');
            const sortBy = th.getAttribute('data-sort');

            // Build URL with pagination preserved
            const params = new URLSearchParams(window.location.search);

            if (sortBy === 'status') {
                // Cycle through status filters
                const currentStatus = params.get('status_filter') || 'all';
                const statusCycle = { 'all': 'active', 'active': 'inactive', 'inactive': 'all' };
                const newStatus = statusCycle[currentStatus];
                params.set('status_filter', newStatus);
            } else {
                const currentSortBy = params.get('product_sort_by') || 'blindbox_id';
                const currentOrder = params.get('product_sort_order') || 'ASC';
                const newOrder = (currentSortBy === sortBy && currentOrder === 'ASC') ? 'DESC' : 'ASC';
                params.set('product_sort_by', sortBy);
                params.set('product_sort_order', newOrder);
            }

            // Reset to page 1 when sorting or filtering
            params.set('product_page', '1');

            window.location.href = window.location.pathname + '?' + params.toString();
        });
    });

    // Attach image upload listener for blindbox
    attachImageUploadListener('blindbox_image', 'blindbox');
});

// Pagination rendering functions
function renderCategoryPagination(pagination) {
    const container = document.querySelector('#categories-table').closest('.dropdown-content').querySelector('.pagination-container');
    if (!container) return;

    const { current_page, total_pages } = pagination;
    
    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<nav class="pager"><div class="pager-group">';

    // Previous button
    const prevDisabled = current_page === 1;
    const prevClass = prevDisabled ? 'pager-item pager-prev disabled' : 'pager-item pager-prev';
    if (prevDisabled) {
        html += `<a href="#" class="${prevClass}" aria-disabled="true" tabindex="-1">◀</a>`;
    } else {
        html += `<a href="#" class="${prevClass}" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', ${current_page - 1}); return false;">◀</a>`;
    }

    // Page numbers
    if (total_pages <= 5) {
        for (let p = 1; p <= total_pages; p++) {
            const activeClass = p === current_page ? ' active' : '';
            html += `<a href="#" class="pager-item${activeClass}" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', ${p}); return false;">${p}</a>`;
        }
    } else {
        let startPage = Math.max(1, current_page - 2);
        let endPage = Math.min(total_pages, current_page + 2);

        if (current_page <= 3) endPage = Math.min(5, total_pages);
        if (current_page > total_pages - 3) startPage = Math.max(1, total_pages - 4);

        if (startPage > 1) {
            html += `<a href="#" class="pager-item" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', 1); return false;">1</a>`;
            if (startPage > 2) html += '<span class="pager-ellipsis">...</span>';
        }

        for (let p = startPage; p <= endPage; p++) {
            const activeClass = p === current_page ? ' active' : '';
            html += `<a href="#" class="pager-item${activeClass}" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', ${p}); return false;">${p}</a>`;
        }

        if (endPage < total_pages) {
            if (endPage < total_pages - 1) html += '<span class="pager-ellipsis">...</span>';
            html += `<a href="#" class="pager-item" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', ${total_pages}); return false;">${total_pages}</a>`;
        }
    }

    // Next button
    const nextDisabled = current_page === total_pages;
    const nextClass = nextDisabled ? 'pager-item pager-next disabled' : 'pager-item pager-next';
    if (nextDisabled) {
        html += `<a href="#" class="${nextClass}" aria-disabled="true" tabindex="-1">▶</a>`;
    } else {
        html += `<a href="#" class="${nextClass}" onclick="loadCategories('${currentCategorySort.sortBy}', '${currentCategorySort.sortOrder}', ${current_page + 1}); return false;">▶</a>`;
    }

    html += '</div></nav>';
    container.innerHTML = html;
}

function renderProductPagination(pagination) {
    const container = document.querySelector('#products-table').closest('.dropdown-content').querySelector('.pagination-container');
    if (!container) return;

    const { current_page, total_pages } = pagination;
    if (total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<nav class="pager"><div class="pager-group">';

    // Previous button
    const prevDisabled = current_page === 1;
    const prevClass = prevDisabled ? 'pager-item pager-prev disabled' : 'pager-item pager-prev';
    if (prevDisabled) {
        html += `<a href="#" class="${prevClass}" aria-disabled="true" tabindex="-1">◀</a>`;
    } else {
        html += `<a href="#" class="${prevClass}" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', ${current_page - 1}); return false;">◀</a>`;
    }

    // Page numbers
    if (total_pages <= 5) {
        for (let p = 1; p <= total_pages; p++) {
            const activeClass = p === current_page ? ' active' : '';
            html += `<a href="#" class="pager-item${activeClass}" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', ${p}); return false;">${p}</a>`;
        }
    } else {
        let startPage = Math.max(1, current_page - 2);
        let endPage = Math.min(total_pages, current_page + 2);

        if (current_page <= 3) endPage = Math.min(5, total_pages);
        if (current_page > total_pages - 3) startPage = Math.max(1, total_pages - 4);

        if (startPage > 1) {
            html += `<a href="#" class="pager-item" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', 1); return false;">1</a>`;
            if (startPage > 2) html += '<span class="pager-ellipsis">...</span>';
        }

        for (let p = startPage; p <= endPage; p++) {
            const activeClass = p === current_page ? ' active' : '';
            html += `<a href="#" class="pager-item${activeClass}" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', ${p}); return false;">${p}</a>`;
        }

        if (endPage < total_pages) {
            if (endPage < total_pages - 1) html += '<span class="pager-ellipsis">...</span>';
            html += `<a href="#" class="pager-item" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', ${total_pages}); return false;">${total_pages}</a>`;
        }
    }

    // Next button
    const nextDisabled = current_page === total_pages;
    const nextClass = nextDisabled ? 'pager-item pager-next disabled' : 'pager-item pager-next';
    if (nextDisabled) {
        html += `<a href="#" class="${nextClass}" aria-disabled="true" tabindex="-1">▶</a>`;
    } else {
        html += `<a href="#" class="${nextClass}" onclick="loadProducts('${currentProductSort.sortBy}', '${currentProductSort.sortOrder}', '${currentProductSort.statusFilter}', ${current_page + 1}); return false;">▶</a>`;
    }

    html += '</div></nav>';
    container.innerHTML = html;
}
