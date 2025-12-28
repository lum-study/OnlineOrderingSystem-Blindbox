<?php
require_once __DIR__ . '/../../../../includes/admin_guard.php';
require_once __DIR__ . '/../../../../models/UserData.php';

$pageTitle = 'User Management';
include __DIR__ . '/../../../../includes/admin/header.php';

$csrfToken = Security::generateCSRF();

// Pagination, Search, and Sort parameters from URL
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$search = trim($_GET['search'] ?? '');
$sortBy = $_GET['sort'] ?? 'created_date';
$sortOrder = strtoupper($_GET['order'] ?? 'DESC');

// Validate sort parameters
$allowedSortColumns = ['user_id', 'username', 'fullname', 'email', 'created_date', 'last_login'];
if (!in_array($sortBy, $allowedSortColumns)) {
    $sortBy = 'created_date';
}
if (!in_array($sortOrder, ['ASC', 'DESC'])) {
    $sortOrder = 'DESC';
}

// Handle sorting - ensure proper table prefix for joined columns
$sortColumn = $sortBy;
if (in_array($sortBy, ['username', 'last_login'])) {
    $sortColumn = 'ul.' . $sortBy;
} else {
    $sortColumn = 'ud.' . $sortBy;
}

// Build the base query
$baseQuery = "FROM user_data ud JOIN user_logins ul ON ud.user_id = ul.user_id WHERE ud.is_deleted = 0";
$params = [];

// Add search condition
if ($search !== '') {
    $baseQuery .= " AND (ud.user_id LIKE ? OR ul.username LIKE ? OR ud.fullname LIKE ? OR ud.email LIKE ? OR ud.contact_number LIKE ?)";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total " . $baseQuery;
$totalResult = Database::query($countQuery, $params)->fetch();
$totalUsers = $totalResult['total'] ?? 0;
$totalPages = max(1, ceil($totalUsers / $perPage));

// Ensure current page is within bounds
if ($page > $totalPages) {
    $page = $totalPages;
}

// Calculate offset
$offset = ($page - 1) * $perPage;

// Stats for top cards
$tmp = Database::query("SELECT COUNT(*) as cnt FROM user_data WHERE is_deleted = 0 AND is_blocked = 0")->fetch();
$activeUsers = (int)($tmp['cnt'] ?? 0);
$tmp = Database::query("SELECT COUNT(*) as cnt FROM user_data WHERE is_deleted = 0 AND is_blocked = 1")->fetch();
$blockedUsers = (int)($tmp['cnt'] ?? 0);
$tmp = Database::query("SELECT COUNT(*) as cnt FROM user_data WHERE is_deleted = 0 AND created_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch();
$newUsers = (int)($tmp['cnt'] ?? 0);

// Fetch users with pagination and sorting
$dataQuery = "SELECT ud.*, ul.username, ul.last_login " . $baseQuery . " ORDER BY {$sortColumn} {$sortOrder} LIMIT {$perPage} OFFSET {$offset}";
$users = Database::fetchAll($dataQuery, $params);

// Helper function to build URL with current params
function buildUserUrl($newParams = [])
{
    $params = [
        'page' => $_GET['page'] ?? 1,
        'search' => $_GET['search'] ?? '',
        'sort' => $_GET['sort'] ?? 'created_date',
        'order' => $_GET['order'] ?? 'DESC'
    ];
    $params = array_merge($params, $newParams);

    // Remove empty params
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);

    return BASE_URL . 'admin/users' . (empty($params) ? '' : '?' . http_build_query($params));
}

// Helper for sort links (use $_GET so it always reflects current request state)
function getSortUrl($column)
{
    $currentSort = $_GET['sort'] ?? 'created_date';
    $currentOrder = strtoupper($_GET['order'] ?? 'DESC');
    $newOrder = ($currentSort === $column && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
    return buildUserUrl(['sort' => $column, 'order' => $newOrder, 'page' => $_GET['page'] ?? 1]);
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
        <p class="admin-breadcrumb">Admin / User Management</p>
        <h1>USER MANAGEMENT</h1>
    </div>
    <div>
        <a href="#" onclick="addUser(); return false;" class="admin-btn">+ Add User</a>
    </div>
</div>

<!-- Stats -->
<div class="admin-stats" aria-hidden="false">
    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($totalUsers) ?></h3>
            <p>Total Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke-width="2" fill="none" />
                <path d="M9 12l2 2 5-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($activeUsers) ?></h3>
            <p>Active Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke-width="2" fill="none" />
                <path d="M7 7l10 10M17 7l-10 10" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($blockedUsers) ?></h3>
            <p>Blocked Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
                <rect x="3" y="4" width="18" height="18" rx="2" stroke-width="2" fill="none" />
                <path d="M16 2v4M8 2v4M3 10h18" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($newUsers) ?></h3>
            <p>New (30d)</p>
        </div>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-header">All Users</h2>

    <!-- Search and Sort Controls -->
    <div class="table-controls">
        <form method="GET" action="<?= BASE_URL ?>admin/users">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                placeholder="Search..."
                class="form-control search-input">
            <?php if ($sortBy !== 'created_date' || $sortOrder !== 'DESC'): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortBy) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($sortOrder) ?>">
            <?php endif; ?>
            <button type="submit" class="admin-btn">Search</button>
            <?php if ($search !== ''): ?>
                <a href="<?= buildUserUrl(['search' => '', 'page' => 1]) ?>" class="admin-btn admin-btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <div class="sort-group">
            <label>Sort by:</label>
            <select onchange="window.location.href=this.value" class="form-control" style="width: auto; min-width: 140px;">
                <option value="<?= getSortUrl('created_date') ?>" <?= $sortBy === 'created_date' ? 'selected' : '' ?>>Created Date</option>
                <option value="<?= getSortUrl('user_id') ?>" <?= $sortBy === 'user_id' ? 'selected' : '' ?>>User ID</option>
                <option value="<?= getSortUrl('username') ?>" <?= $sortBy === 'username' ? 'selected' : '' ?>>Username</option>
                <option value="<?= getSortUrl('fullname') ?>" <?= $sortBy === 'fullname' ? 'selected' : '' ?>>Full Name</option>
                <option value="<?= getSortUrl('email') ?>" <?= $sortBy === 'email' ? 'selected' : '' ?>>Email</option>
                <option value="<?= getSortUrl('last_login') ?>" <?= $sortBy === 'last_login' ? 'selected' : '' ?>>Last Login</option>
            </select>
            <a href="<?= buildUserUrl(['order' => $sortOrder === 'ASC' ? 'DESC' : 'ASC']) ?>"
                class="admin-btn" title="Toggle sort order" style="min-width: 80px;">
                <?= $sortOrder === 'ASC' ? '↑ ASC' : '↓ DESC' ?>
            </a>
        </div>
    </div>

    <!-- Results info -->
    <div class="results-info">
        <?php if ($search !== ''): ?>
            Showing <?= count($users) ?> of <?= $totalUsers ?> users matching "<?= htmlspecialchars($search) ?>"
        <?php else: ?>
            Showing <?= (($page - 1) * $perPage) + 1 ?>-<?= min($page * $perPage, $totalUsers) ?> of <?= $totalUsers ?> users
        <?php endif; ?>
    </div>

    <div class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><a href="<?= getSortUrl('user_id') ?>" style="color: inherit; text-decoration: none;">User ID <?= getSortIcon('user_id') ?></a></th>
                    <th><a href="<?= getSortUrl('username') ?>" style="color: inherit; text-decoration: none;">Username <?= getSortIcon('username') ?></a></th>
                    <th><a href="<?= getSortUrl('fullname') ?>" style="color: inherit; text-decoration: none;">Full Name <?= getSortIcon('fullname') ?></a></th>
                    <th><a href="<?= getSortUrl('email') ?>" style="color: inherit; text-decoration: none;">Email <?= getSortIcon('email') ?></a></th>
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th><a href="<?= getSortUrl('last_login') ?>" style="color: inherit; text-decoration: none;">Last Login <?= getSortIcon('last_login') ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--accent-gray);">
                            <?= $search !== '' ? 'No users found matching your search' : 'No users found' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr data-user-id="<?= htmlspecialchars($user['user_id']) ?>">
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td>
                                <div class="truncate-text" title="<?= htmlspecialchars($user['username']) ?>"><?= htmlspecialchars($user['username']) ?></div>
                            </td>
                            <td>
                                <div class="truncate-text" title="<?= htmlspecialchars($user['fullname']) ?>"><?= htmlspecialchars($user['fullname']) ?></div>
                            </td>
                            <td>
                                <div class="truncate-email" title="<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($user['contact_number'] ?? 'N/A') ?></td>
                            <td><?= $user['is_blocked'] ? '<span class="order-status status-cancelled">Blocked</span>' : '<span class="order-status status-completed">Active</span>' ?></td>
                            <td class="col-last-login"><?= $user['last_login'] ? date('m/d/Y, g:i:s A', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="#" onclick="viewUser('<?= htmlspecialchars($user['user_id']) ?>'); return false;" class="admin-btn">View</a>
                                    <a href="#" onclick="editUser('<?= htmlspecialchars($user['user_id']) ?>'); return false;" class="admin-btn admin-btn-secondary">Edit</a>
                                    <a href="#" onclick="blockUser('<?= htmlspecialchars($user['user_id']) ?>', '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['fullname']) ?>'); return false;" class="admin-btn admin-btn-danger"><?= $user['is_blocked'] ? 'Unblock' : 'Block' ?></a>
                                    <a href="#" onclick="permanentDeleteUser('<?= htmlspecialchars($user['user_id']) ?>', '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['fullname']) ?>'); return false;" class="admin-btn admin-btn-danger" title="Permanently delete user">Delete</a>
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
                <a href="<?= buildUserUrl(['page' => 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">&laquo; First</a>
                <a href="<?= buildUserUrl(['page' => $page - 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">&lsaquo; Prev</a>
            <?php endif; ?>

            <?php
            // Show page numbers with ellipsis
            $range = 2; // Pages to show on each side of current page
            $startPage = max(1, $page - $range);
            $endPage = min($totalPages, $page + $range);

            if ($startPage > 1): ?>
                <a href="<?= buildUserUrl(['page' => 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">1</a>
                <?php if ($startPage > 2): ?>
                    <span>...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i === $page): ?>
                    <span class="admin-btn admin-btn-small active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= buildUserUrl(['page' => $i]) ?>" class="admin-btn admin-btn-small admin-btn-secondary"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                    <span>...</span>
                <?php endif; ?>
                <a href="<?= buildUserUrl(['page' => $totalPages]) ?>" class="admin-btn admin-btn-small admin-btn-secondary"><?= $totalPages ?></a>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= buildUserUrl(['page' => $page + 1]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Next &rsaquo;</a>
                <a href="<?= buildUserUrl(['page' => $totalPages]) ?>" class="admin-btn admin-btn-small admin-btn-secondary">Last &raquo;</a>
            <?php endif; ?>

            <!-- Go to page form -->
            <form action="<?= BASE_URL ?>admin/users" method="GET" class="goto-page-form">
                <?php
                // Preserve current search and sort params
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

<?php include __DIR__ . '/../../../../includes/admin/user_management_modal.php'; ?>
<?php include __DIR__ . '/../../../../includes/admin/footer.php'; ?>