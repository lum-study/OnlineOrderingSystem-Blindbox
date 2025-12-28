<?php
// Prevent direct access - this view must be loaded through the controller
if (!isset($pageTitle) || $pageTitle !== 'Product Management') {
    http_response_code(403);
    die('Direct access to this file is not allowed. Please use the proper route.');
}

include __DIR__ . '/../../../../includes/admin/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin_product.css">
<script>
    const BASE_URL = '<?= BASE_URL ?>';
</script>

<div class="admin-header-bar">
    <div>
        <p class="admin-breadcrumb">Admin / Product Management</p>
        <h1>PRODUCT MANAGEMENT</h1>
    </div>
    <div class="admin-header-actions">
        <a href="#" class="admin-btn" onclick="showAddCategoryModal(); return false;">+ Add Category</a>
        <a href="#" class="admin-btn" onclick="showAddProductModal(); return false;">+ Add Product</a>
    </div>
</div>

<?php if (!empty($alertProducts)): ?>
    <div class="stock-alert-container">
        <div class="stock-alert-header">
            <span class="stock-alert-icon">⚠️</span>
            <h3 class="stock-alert-title">STOCK ALERT</h3>
        </div>
        <p class="stock-alert-message">The following products require attention:</p>
        <div class="stock-alert-table-wrapper">
            <table class="admin-table stock-alert-table">
                <thead>
                    <tr>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alertProducts as $product): ?>
                        <?php
                        $catName = 'Unknown';
                        try {
                            $cat = $categoryModel->getCategoryById($product['category_id']);
                            if ($cat) {
                                $catName = $cat->getCategoryName();
                            } else {
                                error_log("Stock Alert: Category not found for ID: {$product['category_id']} (Product: {$product['blindbox_id']})");
                            }
                        } catch (Exception $e) {
                            error_log("Stock Alert: Failed to fetch category {$product['category_id']} for product {$product['blindbox_id']}: " . $e->getMessage());
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($product['blindbox_id']) ?></td>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td><?= htmlspecialchars($catName) ?></td>
                            <td class="stock-alert-quantity <?= $product['stock_quantity'] == 0 ? 'out-of-stock' : 'low-stock' ?>">
                                <?= $product['stock_quantity'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="brand-font">ADD NEW CATEGORY</h2>
            <span class="modal-close" onclick="closeAddCategoryModal()">&times;</span>
        </div>
        <form id="addCategoryForm" onsubmit="submitAddCategory(event)">
            <div class="form-group">
                <label for="category_name">Category Name <span class="required">*</span></label>
                <?php
                $category_name = '';
                require_once __DIR__ . '/../../../../lib/HtmlHelpers.php';
                html_text('category_name', 'required');
                ?>
            </div>
            <div class="form-group">
                <label for="category_description">Description</label>
                <textarea id="category_description" name="description" rows="4" placeholder="Enter category description"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeAddCategoryModal()">Cancel</button>
                <button type="submit" class="admin-btn">Add Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Product Modal -->
<div id="addProductModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 class="brand-font">ADD NEW PRODUCT</h2>
            <span class="modal-close" onclick="closeAddProductModal()">&times;</span>
        </div>
        <form id="addProductForm" onsubmit="submitAddProduct(event)" enctype="multipart/form-data">
            <!-- Blindbox Section -->
            <div class="modal-section">
                <h3 class="section-title">BLINDBOX INFORMATION</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_name">Product Name <span class="required">*</span></label>
                        <?php
                        $product_name = '';
                        html_text('product_name', 'required');
                        ?>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <?php
                        $category_id = '';
                        $categoryOptions = [];
                        foreach ($categories as $cat) {
                            $categoryOptions[$cat['category_id']] = $cat['category_name'];
                        }
                        html_select('category_id', $categoryOptions, '- Select Category -', 'required');
                        ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price (RM) <span class="required">*</span></label>
                        <input type="number" id="price" name="price" step="0.01" min="0" max="99999.99" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity <span class="required">*</span></label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="product_description">Description</label>
                    <textarea id="product_description" name="description" rows="3" placeholder="Enter product description"></textarea>
                </div>
                <div class="form-group">
                    <label for="blindbox_image">Blindbox Image <span class="required">*</span></label>
                    <div class="image-preview-frame" id="blindbox-preview-frame" onclick="document.getElementById('blindbox_image').click()">
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
                    <input type="file" id="blindbox_image" name="blindbox_image" accept="image/*" required class="hidden-file-input">
                    <input type="hidden" id="blindbox_cropped_data" name="blindbox_cropped_data">
                    <small class="form-helper-text">This is the main box packaging image</small>
                </div>
            </div>

            <!-- Product Variants Section -->
            <div class="modal-section">
                <div class="section-header">
                    <h3 class="section-title">PRODUCT VARIANTS</h3>
                    <button type="button" class="admin-btn admin-btn-small" onclick="addProductVariant()">+ Add Variant</button>
                </div>
                <div id="productVariantsContainer">
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeAddProductModal()">Cancel</button>
                <button type="submit" class="admin-btn">Add Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modal -->
<div id="editProductModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 class="brand-font">EDIT PRODUCT</h2>
            <span class="modal-close" onclick="closeEditProductModal()">&times;</span>
        </div>
        <form id="editProductForm" onsubmit="submitEditProduct(event)">
            <input type="hidden" id="blindbox_id_edit" name="blindbox_id">
            <div class="modal-section">
                <h3 class="section-title">BLINDBOX INFORMATION</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="product_name_edit">Product Name <span class="required">*</span></label>
                        <input type="text" id="product_name_edit" name="product_name" required>
                    </div>
                    <div class="form-group">
                        <label for="category_id_edit">Category <span class="required">*</span></label>
                        <select id="category_id_edit" name="category_id" required>
                            <option value="">- Select Category -</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['category_id']) ?>">
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="price_edit">Price (RM) <span class="required">*</span></label>
                        <input type="number" id="price_edit" name="price" step="0.01" min="0" max="99999.99" required>
                    </div>
                    <div class="form-group">
                        <label for="stock_quantity_edit">Stock Quantity <span class="required">*</span></label>
                        <input type="number" id="stock_quantity_edit" name="stock_quantity" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="product_description_edit">Description</label>
                    <textarea id="product_description_edit" name="description" rows="3" placeholder="Enter product description"></textarea>
                </div>

                <!-- Blindbox Image Section -->
                <div class="form-group">
                    <label for="blindbox_image_edit">Blindbox Image</label>
                    <div class="image-preview-frame" id="blindbox-preview-frame-edit">
                        <img id="current_blindbox_image_edit" src="" alt="Current Image" class="current-blindbox-image">
                        <div class="image-preview-overlay" onclick="document.getElementById('blindbox_image_edit').click()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Change Picture</span>
                        </div>
                    </div>
                    <input type="file" id="blindbox_image_edit" name="new_blindbox_image" accept="image/*" class="hidden-file-input">
                    <input type="hidden" id="blindbox_cropped_data_edit" name="blindbox_cropped_data">
                    <small class="form-helper-text">Click image to replace (optional)</small>
                </div>
            </div>

            <!-- Product Variants Section -->
            <div class="modal-section">
                <div class="section-header">
                    <h3 class="section-title">PRODUCT VARIANTS</h3>
                    <button type="button" class="admin-btn admin-btn-small" onclick="addEditProductVariant()">+ Add Variant</button>
                </div>
                <div id="editProductVariantsContainer">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeEditProductModal()">Cancel</button>
                <button type="submit" class="admin-btn">Update Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div id="editCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="brand-font">EDIT CATEGORY</h2>
            <span class="modal-close" onclick="closeEditCategoryModal()">&times;</span>
        </div>
        <form id="editCategoryForm" onsubmit="submitEditCategory(event)">
            <input type="hidden" id="edit_cat_category_id" name="category_id">
            <div class="form-group">
                <label for="category_name_edit">Category Name <span class="required">*</span></label>
                <input type="text" id="category_name_edit" name="category_name" required>
            </div>
            <div class="form-group">
                <label for="description_edit">Description</label>
                <textarea id="description_edit" name="description" rows="4" placeholder="Enter category description"></textarea>
            </div>
            <div class="form-actions">
                <button type="button" class="admin-btn admin-btn-secondary" onclick="closeEditCategoryModal()">Cancel</button>
                <button type="submit" class="admin-btn">Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- View Category Modal -->
<div id="viewCategoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="brand-font">CATEGORY DETAILS</h2>
            <span class="modal-close" onclick="closeViewCategoryModal()">&times;</span>
        </div>
        <div class="view-modal-body">
            <div class="form-group">
                <label>Category ID</label>
                <p id="view_category_id" class="view-field"></p>
            </div>
            <div class="form-group">
                <label>Category Name</label>
                <p id="view_category_name" class="view-field"></p>
            </div>
            <div class="form-group">
                <label>Description</label>
                <p id="view_category_description" class="view-field-large"></p>
            </div>
            <div class="form-group">
                <label>Created Date</label>
                <p id="view_category_created" class="view-field"></p>
            </div>
        </div>
        <div class="view-modal-actions">
            <button type="button" class="admin-btn" onclick="closeViewCategoryModal()">Close</button>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div id="viewProductModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 class="brand-font">PRODUCT DETAILS</h2>
            <span class="modal-close" onclick="closeViewProductModal()">&times;</span>
        </div>
        <div class="modal-section">
            <h3 class="section-title">BLINDBOX INFORMATION</h3>
            <div class="view-product-info-container">
                <div class="view-product-image-container">
                    <img id="view_blindbox_image" src="" alt="Blindbox" class="view-product-image">
                </div>
                <div class="view-product-details">
                    <div class="form-group">
                        <label>Product ID</label>
                        <p id="view_product_id" class="view-field"></p>
                    </div>
                    <div class="form-group">
                        <label>Product Name</label>
                        <p id="view_product_name" class="view-field"></p>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category</label>
                    <p id="view_product_category" class="view-field"></p>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <p id="view_product_status" class="view-field"></p>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price (RM)</label>
                    <p id="view_product_price" class="view-field"></p>
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <p id="view_product_stock" class="view-field"></p>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <p id="view_product_description" class="view-field-large"></p>
            </div>
            <div class="form-group">
                <label>Created Date</label>
                <p id="view_product_created" class="view-field"></p>
            </div>
        </div>
        <div class="modal-section">
            <h3 class="section-title">PRODUCT VARIANTS</h3>
            <div id="viewProductVariantsContainer" class="view-variants-grid">
            </div>
        </div>
        <div class="view-modal-actions">
            <button type="button" class="admin-btn" onclick="closeViewProductModal()">Close</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="modal">
    <div class="modal-content modal-content-small">
        <div class="modal-header">
            <h2 class="brand-font">CONFIRM DELETE</h2>
            <span class="modal-close" onclick="closeDeleteConfirmModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this product?</p>
            <p>This will also delete all its variants and images.</p>
        </div>
        <div class="form-actions">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeDeleteConfirmModal()">Cancel</button>
            <button type="button" class="admin-btn admin-btn-danger" onclick="confirmDeleteProduct()">Delete</button>
        </div>
    </div>
</div>

<!-- Delete Category Modal with Product Reassignment -->
<div id="deleteCategoryModal" class="modal">
    <div class="modal-content modal-content-medium">
        <div class="modal-header">
            <h2 class="brand-font">DELETE CATEGORY</h2>
            <span class="modal-close" onclick="closeDeleteCategoryModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p class="modal-primary-message">This category has products that need to be reassigned.</p>
            <p class="modal-secondary-message">Please select a new category for each product before deleting.</p>

            <div id="productReassignmentList" class="product-reassignment-list">
            </div>
        </div>
        <div class="form-actions">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeDeleteCategoryModal()">Cancel</button>
            <button type="button" class="admin-btn admin-btn-danger" onclick="confirmDeleteCategory()">Delete Category</button>
        </div>
    </div>
</div>

<!-- Final Delete Category Confirmation Modal -->
<div id="finalDeleteCategoryModal" class="modal">
    <div class="modal-content modal-content-small">
        <div class="modal-header">
            <h2 class="brand-font">CONFIRM DELETE</h2>
            <span class="modal-close" onclick="closeFinalDeleteCategoryModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this category?</p>
            <p>All products have been reassigned. This action cannot be undone.</p>
        </div>
        <div class="form-actions">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="closeFinalDeleteCategoryModal()">Cancel</button>
            <button type="button" class="admin-btn admin-btn-danger" onclick="executeFinalDeleteCategory()">Delete</button>
        </div>
    </div>
</div>

<!-- Categories Section -->
<div class="dropdown-container">
    <div class="dropdown-header active" onclick="toggleDropdown(this)">
        <h2 class="brand-font">CATEGORIES</h2>
        <span class="dropdown-icon">▼</span>
    </div>
    <div class="dropdown-content show">
        <!-- Search and Sort Controls -->
        <div class="table-controls">
            <form method="GET" action="">
                <input type="text" name="category_search" value="<?= htmlspecialchars($categorySearch) ?>"
                    placeholder="Search categories..."
                    class="form-control search-input">
                <?php if ($productPage > 1): ?><input type="hidden" name="product_page" value="<?= $productPage ?>"><?php endif; ?>
                <?php if (!empty($productSortBy)): ?><input type="hidden" name="product_sort_by" value="<?= htmlspecialchars($productSortBy) ?>"><?php endif; ?>
                <?php if (!empty($productSortOrder)): ?><input type="hidden" name="product_sort_order" value="<?= htmlspecialchars($productSortOrder) ?>"><?php endif; ?>
                <?php if (!empty($statusFilter) && $statusFilter !== 'all'): ?><input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>"><?php endif; ?>
                <?php if (!empty($productSearch)): ?><input type="hidden" name="product_search" value="<?= htmlspecialchars($productSearch) ?>"><?php endif; ?>
                <?php if ($sortBy !== 'category_id' || $sortOrder !== 'ASC'): ?>
                    <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sortBy) ?>">
                    <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>">
                <?php endif; ?>
                <button type="submit" class="admin-btn">Search</button>
                <?php if (!empty($categorySearch)): ?>
                    <a href="?sort_by=<?= urlencode($sortBy) ?>&sort_order=<?= urlencode($sortOrder) ?><?= $productPage > 1 ? '&product_page=' . $productPage : '' ?><?= !empty($productSortBy) ? '&product_sort_by=' . urlencode($productSortBy) : '' ?><?= !empty($productSortOrder) ? '&product_sort_order=' . urlencode($productSortOrder) : '' ?><?= !empty($statusFilter) && $statusFilter !== 'all' ? '&status_filter=' . urlencode($statusFilter) : '' ?><?= !empty($productSearch) ? '&product_search=' . urlencode($productSearch) : '' ?>" class="admin-btn admin-btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <div class="sort-group">
                <label>Sort by:</label>
                <select onchange="changeCategorySort(this.value)" class="form-control" id="categorySortSelect">
                    <option value="category_id" <?= $sortBy === 'category_id' ? 'selected' : '' ?>>Category ID</option>
                    <option value="category_name" <?= $sortBy === 'category_name' ? 'selected' : '' ?>>Category Name</option>
                    <option value="created_date" <?= $sortBy === 'created_date' ? 'selected' : '' ?>>Created Date</option>
                </select>
                <button type="button" onclick="toggleCategorySortOrder()" class="admin-btn" title="Toggle sort order" id="categorySortOrderBtn">
                    <?= $sortOrder === 'ASC' ? '↑ ASC' : '↓ DESC' ?>
                </button>
            </div>
        </div>

        <!-- Results info -->
        <div class="results-info">
            <?php if (!empty($categorySearch)): ?>
                Showing <?= count($categories) ?> of <?= $categoryTotalCount ?> categories matching "<?= htmlspecialchars($categorySearch) ?>"
            <?php else: ?>
                Showing <?= (($categoryPage - 1) * 10) + 1 ?>-<?= min($categoryPage * 10, $categoryTotalCount) ?> of <?= $categoryTotalCount ?> categories
            <?php endif; ?>
        </div>
        <table class="admin-table" id="categories-table">
            <thead>
                <tr>
                    <th>
                        <a href="#" onclick="sortCategory('category_id'); return false;">
                            Category ID <span id="cat-sort-category_id"><?= $sortBy === 'category_id' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="#" onclick="sortCategory('category_name'); return false;">
                            Category Name <span id="cat-sort-category_name"><?= $sortBy === 'category_name' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>Description</th>
                    <th>
                        <a href="#" onclick="sortCategory('created_date'); return false;" style="cursor:pointer;">
                            Created Date <span id="cat-sort-created_date"><?= $sortBy === 'created_date' ? ($sortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 2rem; color: var(--accent-gray);">No categories found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories as $categoryRow): ?>
                        <tr>
                            <td><?= htmlspecialchars($categoryRow['category_id']) ?></td>
                            <td><?= htmlspecialchars($categoryRow['category_name']) ?></td>
                            <td><?= htmlspecialchars($categoryRow['description'] ?: 'N/A') ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($categoryRow['created_date'])) ?></td>
                            <td>
                                <a href="#" class="admin-btn admin-btn-small" onclick="viewCategory('<?= htmlspecialchars($categoryRow['category_id']) ?>'); return false;">View</a>
                                <a href="#" class="admin-btn admin-btn-small admin-btn-secondary" onclick="editCategory('<?= htmlspecialchars($categoryRow['category_id']) ?>'); return false;">Edit</a>
                                <a href="#" class="admin-btn admin-btn-small admin-btn-danger"
                                    onclick="deleteCategory('<?= htmlspecialchars($categoryRow['category_id']) ?>'); return false;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($categoryPager): ?>
            <?php
            // Build URL preserving product page
            $categoryHref = 'sort_by=' . urlencode($sortBy) . '&sort_order=' . urlencode($sortOrder);
            if (!empty($categorySearch)) {
                $categoryHref .= '&category_search=' . urlencode($categorySearch);
            }
            if ($productPage > 1) {
                $categoryHref .= '&product_page=' . urlencode($productPage);
            }
            if (!empty($productSortBy)) {
                $categoryHref .= '&product_sort_by=' . urlencode($productSortBy);
            }
            if (!empty($productSortOrder)) {
                $categoryHref .= '&product_sort_order=' . urlencode($productSortOrder);
            }
            if (!empty($statusFilter) && $statusFilter !== 'all') {
                $categoryHref .= '&status_filter=' . urlencode($statusFilter);
            }
            if (!empty($productSearch)) {
                $categoryHref .= '&product_search=' . urlencode($productSearch);
            }
            ?>
            <div class="pagination-container">
                <?php $renderPagination('category_page', $categoryPager->page, $categoryPager->page_count, $categoryHref); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Products Section -->
<div class="dropdown-container">
    <div class="dropdown-header active" onclick="toggleDropdown(this)">
        <h2 class="brand-font">PRODUCTS (BLINDBOX)</h2>
        <span class="dropdown-icon">▼</span>
    </div>
    <div class="dropdown-content show">
        <!-- Search and Sort Controls -->
        <div class="table-controls">
            <form method="GET" action="">
                <input type="text" name="product_search" value="<?= htmlspecialchars($productSearch) ?>"
                    placeholder="Search products..."
                    class="form-control search-input">
                <?php if ($categoryPage > 1): ?><input type="hidden" name="category_page" value="<?= $categoryPage ?>"><?php endif; ?>
                <?php if (!empty($sortBy)): ?><input type="hidden" name="sort_by" value="<?= htmlspecialchars($sortBy) ?>"><?php endif; ?>
                <?php if (!empty($sortOrder)): ?><input type="hidden" name="sort_order" value="<?= htmlspecialchars($sortOrder) ?>"><?php endif; ?>
                <?php if (!empty($categorySearch)): ?><input type="hidden" name="category_search" value="<?= htmlspecialchars($categorySearch) ?>"><?php endif; ?>
                <?php if ($productSortBy !== 'blindbox_id' || $productSortOrder !== 'ASC' || $statusFilter !== 'all'): ?>
                    <input type="hidden" name="product_sort_by" value="<?= htmlspecialchars($productSortBy) ?>">
                    <input type="hidden" name="product_sort_order" value="<?= htmlspecialchars($productSortOrder) ?>">
                    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
                <?php endif; ?>
                <button type="submit" class="admin-btn">Search</button>
                <?php if (!empty($productSearch)): ?>
                    <a href="?product_sort_by=<?= urlencode($productSortBy) ?>&product_sort_order=<?= urlencode($productSortOrder) ?>&status_filter=<?= urlencode($statusFilter) ?><?= $categoryPage > 1 ? '&category_page=' . $categoryPage : '' ?><?= !empty($sortBy) ? '&sort_by=' . urlencode($sortBy) : '' ?><?= !empty($sortOrder) ? '&sort_order=' . urlencode($sortOrder) : '' ?><?= !empty($categorySearch) ? '&category_search=' . urlencode($categorySearch) : '' ?>" class="admin-btn admin-btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <div class="sort-group">
                <label>Sort by:</label>
                <select onchange="changeProductSort(this.value)" class="form-control" id="productSortSelect">
                    <option value="blindbox_id" <?= $productSortBy === 'blindbox_id' ? 'selected' : '' ?>>Product ID</option>
                    <option value="product_name" <?= $productSortBy === 'product_name' ? 'selected' : '' ?>>Product Name</option>
                    <option value="category_id" <?= $productSortBy === 'category_id' ? 'selected' : '' ?>>Category</option>
                    <option value="price" <?= $productSortBy === 'price' ? 'selected' : '' ?>>Price</option>
                    <option value="stock_quantity" <?= $productSortBy === 'stock_quantity' ? 'selected' : '' ?>>Stock Quantity</option>
                </select>
                <button type="button" onclick="toggleProductSortOrder()" class="admin-btn" title="Toggle sort order" id="productSortOrderBtn">
                    <?= $productSortOrder === 'ASC' ? '↑ ASC' : '↓ DESC' ?>
                </button>
                <select onchange="changeProductStatusFilter(this.value)" class="form-control" id="productStatusFilter">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Out of Stock</option>
                </select>
            </div>
        </div>

        <!-- Results info -->
        <div class="results-info">
            <?php if (!empty($productSearch)): ?>
                Showing <?= count($blindboxes) ?> of <?= $productTotalCount ?> products matching "<?= htmlspecialchars($productSearch) ?>"
            <?php else: ?>
                Showing <?= (($productPage - 1) * 10) + 1 ?>-<?= min($productPage * 10, $productTotalCount) ?> of <?= $productTotalCount ?> products
            <?php endif; ?>
        </div>
        <table class="admin-table" id="products-table">
            <thead>
                <tr>
                    <th>
                        <a href="#" onclick="sortProduct('blindbox_id'); return false;" style="cursor:pointer;">
                            Product ID <span id="prod-sort-blindbox_id"><?= $productSortBy === 'blindbox_id' ? ($productSortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="#" onclick="sortProduct('product_name'); return false;" style="cursor:pointer;">
                            Product Name <span id="prod-sort-product_name"><?= $productSortBy === 'product_name' ? ($productSortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="#" onclick="sortProduct('category_id'); return false;" style="cursor:pointer;">
                            Category <span id="prod-sort-category_id"><?= $productSortBy === 'category_id' ? ($productSortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="#" onclick="sortProduct('price'); return false;" style="cursor:pointer;">
                            Price (RM) <span id="prod-sort-price"><?= $productSortBy === 'price' ? ($productSortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>
                        <a href="#" onclick="sortProduct('stock_quantity'); return false;" style="cursor:pointer;">
                            Stock <span id="prod-sort-stock_quantity"><?= $productSortBy === 'stock_quantity' ? ($productSortOrder === 'ASC' ? '↑' : '↓') : '↕' ?></span>
                        </a>
                    </th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blindboxes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--accent-gray);">No products found</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($blindboxes as $blindboxRow): ?>
                        <?php
                        // Get category name
                        $categoryName = 'Unknown';
                        try {
                            $cat = $categoryModel->getCategoryById($blindboxRow['category_id']);
                            if ($cat) {
                                $categoryName = $cat->getCategoryName();
                            } else {
                                error_log("Product List: Category not found for ID: {$blindboxRow['category_id']} (Product: {$blindboxRow['blindbox_id']})");
                            }
                        } catch (Exception $e) {
                            error_log("Product List: Failed to fetch category {$blindboxRow['category_id']} for product {$blindboxRow['blindbox_id']}: " . $e->getMessage());
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($blindboxRow['blindbox_id']) ?></td>
                            <td><?= htmlspecialchars($blindboxRow['product_name']) ?></td>
                            <td><?= htmlspecialchars($categoryName) ?></td>
                            <td><?= number_format($blindboxRow['price'], 2) ?></td>
                            <td><?= $blindboxRow['stock_quantity'] ?></td>
                            <td>
                                <?php if ($blindboxRow['stock_quantity'] > 0): ?>
                                    <span style="color: #22c55e;">Active</span>
                                <?php else: ?>
                                    <span style="color: #ef4444;">Out of Stock</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="#" class="admin-btn admin-btn-small" onclick="viewProduct('<?= htmlspecialchars($blindboxRow['blindbox_id']) ?>'); return false;">View</a>
                                <a href="#" class="admin-btn admin-btn-small admin-btn-secondary" onclick="editProduct('<?= htmlspecialchars($blindboxRow['blindbox_id']) ?>'); return false;">Edit</a>
                                <a href="#" class="admin-btn admin-btn-small admin-btn-danger"
                                    onclick="deleteProduct('<?= htmlspecialchars($blindboxRow['blindbox_id']) ?>'); return false;">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if ($productPager): ?>
            <?php
            // Build URL preserving category page
            $productHref = 'product_sort_by=' . urlencode($productSortBy) . '&product_sort_order=' . urlencode($productSortOrder) . '&status_filter=' . urlencode($statusFilter);
            if (!empty($productSearch)) {
                $productHref .= '&product_search=' . urlencode($productSearch);
            }
            if ($categoryPage > 1) {
                $productHref .= '&category_page=' . urlencode($categoryPage);
            }
            if (!empty($sortBy)) {
                $productHref .= '&sort_by=' . urlencode($sortBy);
            }
            if (!empty($sortOrder)) {
                $productHref .= '&sort_order=' . urlencode($sortOrder);
            }
            if (!empty($categorySearch)) {
                $productHref .= '&category_search=' . urlencode($categorySearch);
            }
            ?>
            <div class="pagination-container">
                <?php $renderPagination('product_page', $productPager->page, $productPager->page_count, $productHref); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../../../includes/photo_upload_modal.php'; ?>

<script src="<?= BASE_URL ?>assets/js/photo_upload.js"></script>
<script src="<?= BASE_URL ?>assets/js/admin_product.js"></script>

<?php include __DIR__ . '/../../../../includes/admin/footer.php'; ?>