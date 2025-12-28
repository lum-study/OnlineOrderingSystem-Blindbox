<?php
require_once __DIR__ . '/../../../../includes/admin_guard.php';
require_once __DIR__ . '/../../../../controllers/ReviewController.php';
require_once __DIR__ . '/../../../../controllers/admin/AdminOrderController.php';

// Handle AJAX update order request
if (isset($_GET['action']) && $_GET['action'] === 'update_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $controller = new AdminOrderController();
    $result = $controller->updateOrder($_POST['order_id'], $_POST);
    echo json_encode($result);
    exit;
}

$role = Session::get('role');
$canEdit = in_array($role, ['admin', 'manager']);

// Pagination, Search, and Sort parameters
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$search = trim($_GET['search'] ?? '');
$sortBy = $_GET['sort'] ?? 'created_date';
$sortOrder = strtoupper($_GET['order'] ?? 'DESC');

// Validate sort parameters
$allowedSortColumns = ['order_id', 'fullname', 'created_date', 'total_amount', 'status'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_date';
}
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Handle sorting
$sortColumn = $sortBy === 'fullname' ? 'u.fullname' : 'o.' . $sortBy;

// Build the base query - use subquery to get latest payment per order
$baseQuery = "FROM orders o 
    JOIN user_data u ON o.user_id = u.user_id 
    LEFT JOIN (
        SELECT p1.order_id, p1.payment_method 
        FROM payments p1
        INNER JOIN (
            SELECT order_id, MAX(created_date) as max_date
            FROM payments
            GROUP BY order_id
        ) p2 ON p1.order_id = p2.order_id AND p1.created_date = p2.max_date
    ) p ON o.order_id = p.order_id 
    WHERE o.is_deleted = 0";
$params = [];

// Add search condition (customer name only)
if ($search !== '') {
    $baseQuery .= " AND u.fullname LIKE ?";
    $params = ['%' . $search . '%'];
}

// Get total count
$countQuery = "SELECT COUNT(*) as total " . $baseQuery;
$totalResult = Database::query($countQuery, $params)->fetch();
$totalOrders = $totalResult['total'] ?? 0;
$totalPages = max(1, ceil($totalOrders / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

// Fetch orders with pagination and sorting - use DISTINCT to avoid duplicates
$dataQuery = "SELECT DISTINCT o.*, u.fullname, u.email, p.payment_method " . $baseQuery . " ORDER BY {$sortColumn} {$sortOrder} LIMIT {$perPage} OFFSET {$offset}";
$orders = Database::fetchAll($dataQuery, $params);

// Get order items for each order
foreach ($orders as &$order) {
    $order['items'] = Database::fetchAll("SELECT oi.*, b.product_name FROM order_items oi JOIN blindbox b ON oi.blindbox_id = b.blindbox_id WHERE oi.order_id = ?", [$order['order_id']]);
    $order['reviews'] = ReviewController::getOrderReviews($order['order_id']);
    $order['has_reviews'] = count($order['reviews']) > 0;
}
unset($order);

// Get status counts (removed 'paid' status)
$statusCounts = ['to_pay' => 0, 'pending' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
$analysis = Database::fetchAll("SELECT status, COUNT(*) as count FROM orders WHERE is_deleted = 0 GROUP BY status");
foreach ($analysis as $row) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = $row['count'];
    }
}

$pageTitle = 'Order Management';
include __DIR__ . '/../../../../includes/admin/header.php';

function formatDate($date) {
    return date('M d, Y H:i', strtotime($date));
}

function formatCurrency($amount) {
    return 'RM ' . number_format($amount, 2);
}

function getStatusBadge($status) {
    $classname = [
        'pending' => 'order-status status-pending',
        'to_pay' => 'order-status status-to_pay',
        'shipped' => 'order-status status-shipped',
        'completed' => 'order-status status-completed',
        'cancelled' => 'order-status status-cancelled'
    ];
    $class = $classname[$status] ?? 'order-status status-unknown';
    return '<span class="' . $class . '">' . ucfirst($status) . '</span>';
}

function buildOrderUrl($newParams = [])
{
    $params = [
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'created_date',
        'order' => $_GET['order'] ?? 'DESC'
    ];
    $params = array_merge($params, $newParams);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return BASE_URL . 'views/pages/admin/orders/admin_list.php' . (empty($params) ? '' : '?' . http_build_query($params));
}

function getSortUrl($column)
{
    $currentSort = $_GET['sort'] ?? 'created_date';
    $currentOrder = strtoupper($_GET['order'] ?? 'DESC');
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return buildOrderUrl(['sort' => $column, 'order' => $newOrder, 'page' => $_GET['page'] ?? 1]);
}

function getSortIcon($column)
{
    $currentSort = $_GET['sort'] ?? 'created_date';
    $currentOrder = strtoupper($_GET['order'] ?? 'DESC');
    if ($currentSort !== $column) return '↕';
    return $currentOrder === 'ASC' ? '↑' : '↓';
}
?>

<div class="admin-header-bar">
    <div>
        <p class="admin-breadcrumb">Admin / Orders</p>
        <h1>ORDER MANAGEMENT</h1>
    </div>
</div>

<div class="admin-stats">
    <div class="stat-card">
        <h3><?= $statusCounts['to_pay'] ?></h3>
        <p>To Pay</p>
    </div>
    <div class="stat-card">
        <h3><?= $statusCounts['pending'] ?></h3>
        <p>Pending</p>
    </div>
    <div class="stat-card">
        <h3><?= $statusCounts['shipped'] ?></h3>
        <p>Shipped</p>
    </div>
    <div class="stat-card">
        <h3><?= $statusCounts['completed'] ?></h3>
        <p>Completed</p>
    </div>
    <div class="stat-card">
        <h3><?= $statusCounts['cancelled'] ?></h3>
        <p>Cancelled</p>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-header">Orders</h2>

    <!-- Search and Sort Controls -->
    <div class="table-controls">
        <form method="GET" action="<?= BASE_URL ?>views/pages/admin/orders/admin_list.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search by customer name..."
                class="form-control search-input">
            <?php if ($sortBy !== 'created_date' || $sortOrder !== 'DESC'): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sortOrder) ?>">
            <?php endif; ?>
            <button type="submit" class="admin-btn">Search</button>
            <?php if ($search !== ''): ?>
                <a href="<?= buildOrderUrl(['search' => '', 'page' => 1]) ?>" class="admin-btn admin-btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <div class="sort-group">
            <label>Sort by:</label>
            <select onchange="window.location.href=this.value" class="form-control" style="width: auto; min-width: 140px;">
                <option value="<?= getSortUrl('created_date') ?>" <?= $sortBy === 'created_date' ? 'selected' : '' ?>>Date</option>
                <option value="<?= getSortUrl('order_id') ?>" <?= $sortBy === 'order_id' ? 'selected' : '' ?>>Order ID</option>
                <option value="<?= getSortUrl('fullname') ?>" <?= $sortBy === 'fullname' ? 'selected' : '' ?>>Customer</option>
                <option value="<?= getSortUrl('total_amount') ?>" <?= $sortBy === 'total_amount' ? 'selected' : '' ?>>Total</option>
                <option value="<?= getSortUrl('status') ?>" <?= $sortBy === 'status' ? 'selected' : '' ?>>Status</option>
            </select>
            <a href="<?= buildOrderUrl(['order' => $sortOrder === 'ASC' ? 'DESC' : 'ASC']) ?>"
                class="admin-btn" title="Toggle sort order" style="min-width: 80px;">
                <?= $sortOrder === 'ASC' ? '↑ ASC' : '↓ DESC' ?>
            </a>
        </div>
    </div>

    <!-- Results info -->
    <div class="results-info">
        <?php if ($search !== ''): ?>
            Showing <?= count($orders) ?> of <?= $totalOrders ?> orders matching "<?= htmlspecialchars($search) ?>"
        <?php else: ?>
            Showing <?= (($page - 1) * $perPage) + 1 ?>-<?= min($page * $perPage, $totalOrders) ?> of <?= $totalOrders ?> orders
        <?php endif; ?>
    </div>

    <div class="table-wrapper">
    <table class="admin-table">
        <thead>
            <tr>
                <th><a href="<?= getSortUrl('order_id') ?>" style="color: inherit; text-decoration: none;">Order ID <?= getSortIcon('order_id') ?></a></th>
                <th><a href="<?= getSortUrl('fullname') ?>" style="color: inherit; text-decoration: none;">Customer <?= getSortIcon('fullname') ?></a></th>
                <th><a href="<?= getSortUrl('created_date') ?>" style="color: inherit; text-decoration: none;">Date <?= getSortIcon('created_date') ?></a></th>
                <th><a href="<?= getSortUrl('total_amount') ?>" style="color: inherit; text-decoration: none;">Total <?= getSortIcon('total_amount') ?></a></th>
                <th>Payment</th>
                <th><a href="<?= getSortUrl('status') ?>" style="color: inherit; text-decoration: none;">Status <?= getSortIcon('status') ?></a></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--accent-gray);">
                    <?= $search !== '' ? 'No orders found matching your search' : 'No orders found' ?>
                </td></tr>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                        <td>
                            <div><?= htmlspecialchars($order['fullname']) ?></div>
                            <div class="order-customer-email"><?= htmlspecialchars($order['email']) ?></div>
                        </td>
                        <td><?= formatDate($order['created_date']) ?></td>
                        <td><?= formatCurrency($order['total_amount']) ?></td>
                        <td><?= ucfirst($order['payment_method'] ?? 'N/A') ?></td>
                        <td><?= getStatusBadge($order['status']) ?></td>
                        <td>
                            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                                <div style="display:flex;gap:0.5rem;">
                                    <button type="button" onclick="viewOrder('<?= $order['order_id'] ?>')" class="admin-btn admin-btn-small">View</button>
                                    <button type="button" onclick="editOrder('<?= $order['order_id'] ?>')" class="admin-btn admin-btn-small">Edit</button>
                                </div>
                                <?php if ($order['has_reviews']): ?>
                                    <button type="button" onclick="viewReviews('<?= $order['order_id'] ?>')" class="admin-btn admin-btn-small" style="background:#10b981;border-color:#10b981;width:100%;">View Reviews</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= buildOrderUrl(['page' => 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">&laquo; First</a>
                <a href="<?= buildOrderUrl(['page' => $page - 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">&lsaquo; Prev</a>
            <?php endif; ?>

            <?php
            $range = 2;
            $startPage = max(1, $page - $range);
            $endPage = min($totalPages, $page + $range);

            if ($startPage > 1): ?>
                <a href="<?= buildOrderUrl(['page' => 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">1</a>
                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="admin-btn admin-btn-small active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= buildOrderUrl(['page' => $i]) ?>" class="admin-btn admin-btn-small admin-btn-secondary"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="<?= buildOrderUrl(['page' => $totalPages]) ?>" class="admin-btn admin-btn-small admin-btn-secondary"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= buildOrderUrl(['page' => $page + 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Next &rsaquo;</a>
                <a href="<?= buildOrderUrl(['page' => $totalPages]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Last &raquo;</a>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>views/pages/admin/orders/admin_list.php" method="GET" class="goto-page-form">
                <?php
                if ($search !== '') echo '<input type="hidden" name="search" value="' . htmlspecialchars($search) . '">';
                if ($sortBy !== 'created_date') echo '<input type="hidden" name="sort" value="' . htmlspecialchars($sortBy) . '">';
                if ($sortOrder !== 'DESC') echo '<input type="hidden" name="order" value="' . htmlspecialchars($sortOrder) . '">';
                ?>
                <label>Go to:</label>
                <input type="number" name="page" min="1" max="<?= $totalPages ?>" class="form-control" placeholder="#">
                <button type="submit" class="admin-btn admin-btn-small">Go</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- View Modal -->
<div id="viewModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <button type="button" class="modal-close" data-modal="viewModal">&times;</button>
        <div id="receiptContent">
            <div class="receipt-header">
                <h2>BLINDEDOOS</h2>
                <p>Order Receipt</p>
            </div>
            <div id="orderDetails"></div>
        </div>
        <div class="modal-actions">
            <button type="button" onclick="printReceipt()" class="admin-btn">Print Receipt</button>
            <button type="button" class="admin-btn admin-btn-secondary" data-modal="viewModal">Close</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <button type="button" class="modal-close" data-modal="editModal">&times;</button>
        <h2>Edit Order</h2>
        <form id="editForm">
            <input type="hidden" id="editOrderId" name="order_id">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
            
            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select id="editStatus" name="status">
                    <!-- Options populated by JavaScript -->
                </select>
            </div>
            
            <div class="form-group">
                <label>Shipping Address</label>
                <textarea id="editAddress" name="shipping_address" rows="3"></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="updateOrderStatus()" class="admin-btn">Update Order</button>
                <button type="button" class="admin-btn admin-btn-secondary" data-modal="editModal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Confirmation Modal -->
<div id="editConfirmModal" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-small">
        <button type="button" class="modal-close" data-modal="editConfirmModal">&times;</button>
        <h2>Confirm Update</h2>
        <p id="editConfirmText">Are you sure you want to update this order?</p>
        <div class="modal-actions">
            <button onclick="confirmEdit()" class="admin-btn">Confirm</button>
            <button onclick="closeModal('editConfirmModal')" class="admin-btn admin-btn-secondary">Cancel</button>
        </div>
    </div>
</div>

<!-- Reviews Modal -->
<div id="reviewsModal" class="modal-overlay" style="display: none;">
    <div class="modal-content" style="max-width:800px;">
        <button type="button" class="modal-close" data-modal="reviewsModal">&times;</button>
        <h2>Customer Reviews</h2>
        <div id="reviewsContent" style="max-height:600px;overflow-y:auto;"></div>
        <div class="modal-actions">
            <button type="button" class="admin-btn admin-btn-secondary" data-modal="reviewsModal">Close</button>
        </div>
    </div>
</div>

<meta name="csrf-token" content="<?= Security::generateCSRF() ?>">
<script>
window.ordersData = <?= json_encode($orders, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.orderItemsData = <?= json_encode(array_column($orders, 'items', 'order_id'), JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.orderReviewsData = <?= json_encode(array_column($orders, 'reviews', 'order_id'), JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.baseUrl = '<?= BASE_URL ?>';
</script>
<script src="<?= BASE_URL ?>assets/js/admin_orders.js"></script>

<?php include __DIR__ . '/../../../../includes/admin/footer.php'; ?>
