<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$currentPath = str_replace('/online_shopping_system/', '', $currentPath);

// Get admin session data
$staffName = Session::getAdmin('fullname') ?? 'Admin';
$staffId = Session::getAdmin('staff_id');
$staffPosition = Session::getAdmin('position') ?? 'Staff';
$staffPhoto = null;

// Get staff profile photo
if ($staffId) {
    require_once __DIR__ . '/../../models/StaffData.php';
    $staffDataModel = new StaffData();
    $staffProfile = $staffDataModel->getProfileWithLogin($staffId);
    if ($staffProfile) {
        $staffPhoto = $staffProfile['profile_photo'] ?? null;
        $staffPosition = $staffProfile['position'] ?? $staffPosition;
    }
}

// Check if current staff can access staff management (only admin and manager)
$canAccessStaffManagement = in_array(strtolower($staffPosition), ['admin', 'manager']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <h1 class="brand-font">BLINDE<span>DOOS</span></h1>
        <p class="sidebar-subtitle">Admin Panel</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?= BASE_URL ?>admin/dashboard" class="sidebar-link <?= ($currentPage === 'dashboard.php' || $currentPath === 'admin/dashboard') ? 'active' : '' ?>">
            <span>Dashboard</span>
        </a>        
        <a href="<?= BASE_URL ?>admin/users" class="sidebar-link <?= ($currentPage === 'user_list.php' || $currentPath === 'admin/users') ? 'active' : '' ?>">
            <span>User Management</span>
        </a>
        <?php if ($canAccessStaffManagement): ?>
        <a href="<?= BASE_URL ?>admin/staff" class="sidebar-link <?= ($currentPage === 'staff_list.php' || $currentPath === 'admin/staff') ? 'active' : '' ?>">
            <span>Staff Management</span>
        </a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>admin/products" class="sidebar-link <?= ($currentPage === 'admin_products.php' || $currentPath === 'admin/products') ? 'active' : '' ?>" target="_self">
            <span>Product Management</span>
        </a>
        <a href="<?= BASE_URL ?>views/pages/admin/orders/admin_list.php" class="sidebar-link <?= $currentPage === 'admin_list.php' ? 'active' : '' ?>">
            <span>Order Management</span>
        </a>
        <a href="<?= BASE_URL ?>views/pages/admin/activity_logs.php" class="sidebar-link <?= ($currentPage === 'activity_logs.php') ? 'active' : '' ?>">
            <span>Activity Logs</span>
        </a>
        <a href="<?= BASE_URL ?>admin/profile" class="sidebar-link <?= ($currentPage === 'profile.php' || $currentPath === 'admin/profile') ? 'active' : '' ?>">
            <span>Profile</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php if ($staffPhoto): ?>
                <img src="<?= BASE_URL ?>assets/images/uploads/profile/<?= htmlspecialchars($staffPhoto) ?>?t=<?= time() ?>" alt="Profile" class="admin-sidebar-avatar" id="admin-sidebar-avatar">
            <?php else: ?>
                <div class="admin-sidebar-avatar-placeholder" id="admin-sidebar-avatar">?</div>
            <?php endif; ?>
            <div class="sidebar-user-info">
                <span class="user-name"><?= htmlspecialchars($staffName) ?></span>
                <span class="user-role"><?= htmlspecialchars(ucfirst($staffPosition)) ?></span>
            </div>
        </div>
        <div class="sidebar-actions">
            <button id="theme-toggle" class="sidebar-btn" title="Toggle Theme">◐</button>
            <a href="<?= BASE_URL ?>admin/logout" class="sidebar-btn sidebar-logout-btn" title="Logout">Logout</a>
        </div>
    </div>
</aside>

<!-- Global helper to update admin sidebar (name, role, avatar) -->
<script>
    window.updateAdminSidebarFromUser = function(user) {
        if (!user) return;
        try {
            const fullname = user.fullname || user.name || user.full_name || '';
            const position = user.position || user.job || '';
            const profilePhoto = user.profile_photo || user.profile_photo_file || '';

            const nameEl = document.querySelector('.sidebar-user .user-name');
            const roleEl = document.querySelector('.sidebar-user .user-role');

            if (nameEl && fullname) {
                if ('value' in nameEl) nameEl.value = fullname;
                else nameEl.textContent = fullname;
            }

            if (roleEl) {
                const posText = position ? (position.charAt(0).toUpperCase() + position.slice(1)) : roleEl.textContent || '';
                roleEl.textContent = posText;
            }

            if (profilePhoto) {
                const avatarEl = document.getElementById('admin-sidebar-avatar');
                const newSrc = '<?= BASE_URL ?>assets/images/uploads/profile/' + encodeURIComponent(profilePhoto) + '?t=' + Date.now();
                if (avatarEl) {
                    if (avatarEl.tagName && avatarEl.tagName.toUpperCase() === 'IMG') {
                        avatarEl.src = newSrc;
                    } else if (avatarEl.parentNode) {
                        const img = document.createElement('img');
                        img.id = 'admin-sidebar-avatar';
                        img.className = 'admin-sidebar-avatar';
                        img.src = newSrc;
                        img.alt = 'Profile';
                        avatarEl.parentNode.replaceChild(img, avatarEl);
                    }
                }
            }
        } catch (err) {
            console.error('[Sidebar] updateAdminSidebarFromUser error:', err);
        }
    };
</script>
