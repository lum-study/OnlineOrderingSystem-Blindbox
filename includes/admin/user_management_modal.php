<?php

/**
 * User Management Modals
 * Includes modals for both User and Staff management (View, Edit, Block, Delete)
 * Usage: Include this file in user_list.php and staff_list.php
 */

// Determine if we're managing users or staff based on the current page
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$isStaffManagement = (strpos($currentUrl, '/admin/staff') !== false || strpos($currentUrl, 'staff') !== false);
$entityType = $isStaffManagement ? 'Staff' : 'User';
$entityTypeLower = strtolower($entityType);

// Debug: Log the detection
error_log("User Management Modal - URL: {$currentUrl}, Is Staff: " . ($isStaffManagement ? 'yes' : 'no'));

// Get current logged-in staff ID for self-blocking prevention
$currentStaffId = Session::getAdmin('staff_id');
?>

<div class="modal-overlay" id="add-<?= $entityTypeLower ?>-modal">
    <div class="modal-content admin-card" style="max-width: 600px;">
        <button class="modal-close">&times;</button>
        <h2>Add <?= $entityType ?></h2>
        <form action="<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/create" method="POST"
            class="profile-form" id="add-<?= $entityTypeLower ?>-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label><?= $entityType ?> ID<span class="required">*</span></label>
                <?php html_text('add_' . $entityTypeLower . '_id_display', 'disabled placeholder="Auto-generated" id="add-' . $entityTypeLower . '-id-display"'); ?>
            </div>

            <div class="form-group">
                <label>Username<span class="required">*</span></label>
                <?php
                $GLOBALS['add_username'] = '';
                // Required is enforced via JS and server-side; remove native 'required' attribute
                html_text('add_username');
                ?>
            </div>

            <div class="form-group">
                <label>Full Name<span class="required">*</span></label>
                <?php
                $GLOBALS['add_fullname'] = '';
                html_text('add_fullname');
                ?>
            </div>

            <div class="form-group">
                <label>Email<span class="required">*</span></label>
                <?php
                $GLOBALS['add_email'] = '';
                // Email requirement is enforced by JS/server-side; render email input without 'required'
                html_email('add_email');
                ?>
            </div>

            <?php if ($isStaffManagement): ?>
                <div class="form-group">
                    <label>Position<span class="required">*</span></label>
                    <?php
                    $GLOBALS['add_position'] = '';
                    // Position required by server/JS; remove native 'required' attribute
                    html_select('add_position', [
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        'manager' => 'Manager',
                        'other' => 'Other (specify below)'
                    ], '- Select Position -');
                    ?>
                </div>

                <div class="form-group" id="custom-position-group" style="display: none;">
                    <label>Custom Position</label>
                    <?php
                    $GLOBALS['add_custom_position'] = '';
                    html_text('add_custom_position');
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Contact Number</label>
                <?php
                $GLOBALS['add_contact_number'] = '';
                html_phone('add_contact_number');
                ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Birth Date</label>
                    <?php
                    $GLOBALS['add_birth_date'] = '';
                    html_date('add_birth_date');
                    ?>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <?php
                    $GLOBALS['add_gender'] = '';
                    html_select('add_gender', [
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other'
                    ], '- Prefer not to say -');
                    ?>
                </div>
            </div>

            <?php if (!$isStaffManagement): ?>
                <div
                    style="padding: 0.75rem; background: #f0fdf4; border: 1px solid #86efac; margin: 1rem 0; color: #166534; font-size: 0.875rem;">
                    <strong>Note:</strong> The user will be required to reset their password on first login.
                </div>
            <?php else: ?>
                <div
                    style="padding: 0.75rem; background: #f0fdf4; border: 1px solid #86efac; margin: 1rem 0; color: #166534; font-size: 0.875rem;">
                    <strong>Note:</strong> The staff will be required to reset their password on first login.
                </div>
            <?php endif; ?>

            <?php if (!$isStaffManagement): ?>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0;">
                        <?php
                        // Default to checked so verification is sent automatically (can be unchecked by admin)
                        $GLOBALS['add_send_verification'] = '1';
                        html_checkbox('add_send_verification');
                        ?>
                        <span>Send email verification immediately after creation</span>
                    </label>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" id="add-<?= $entityTypeLower ?>-submit-btn" class="user-btn">Create
                    <?= $entityType ?></button>
                <button type="button" class="user-btn user-btn-secondary"
                    onclick="close<?= $entityType ?>AddModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="verify-<?= $entityTypeLower ?>-confirmation-modal">
    <div class="modal-content admin-card" style="max-width: 500px;">
        <button class="modal-close">&times;</button>
        <h2>Send Email Verification</h2>
        <div style="padding: 1rem 0;">
            <p style="margin-bottom: 1rem;">Are you sure you want to send an email verification to this
                <?= $entityTypeLower ?>?
            </p>
            <p id="verify-<?= $entityTypeLower ?>-email-display" style="font-weight: bold; color: var(--text-color);"></p>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
            <button type="button" id="verify-<?= $entityTypeLower ?>-confirm-btn" class="user-btn">Send Verification
                Email</button>
            <button type="button" class="user-btn user-btn-secondary"
                onclick="close<?= $entityType ?>VerificationModal()">Cancel</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="view-<?= $entityTypeLower ?>-modal">
    <div class="modal-content admin-card" style="max-width: 600px;">
        <button class="modal-close">&times;</button>
        <h2>View <?= $entityType ?> Details</h2>
        <div class="profile-form" style="pointer-events: none; opacity: 0.9;">
            <div class="form-group">
                <label><?= $entityType ?> ID</label>
                <?php html_text('view_' . $entityTypeLower . '_id', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Username</label>
                <?php html_text('view_username', 'disabled'); ?>
            </div>

            <?php if ($isStaffManagement): ?>
                <div class="form-group">
                    <label>Position</label>
                    <?php html_text('view_position', 'disabled'); ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Full Name</label>
                <?php html_text('view_fullname', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <?php html_email('view_email', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <?php html_text('view_contact', 'disabled'); ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Birth Date</label>
                    <?php html_date('view_birthdate', 'disabled'); ?>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <?php html_text('view_gender', 'disabled'); ?>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <?php html_text('view_status', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Last Login</label>
                <?php html_text('view_last_login', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Created Date</label>
                <?php html_text('view_created_date', 'disabled'); ?>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
            <button type="button" class="user-btn user-btn-secondary"
                onclick="close<?= $entityType ?>ViewModal()">Close</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="edit-<?= $entityTypeLower ?>-modal">
    <div class="modal-content admin-card" style="max-width: 600px;">
        <button class="modal-close">&times;</button>
        <h2>Edit <?= $entityType ?></h2>
        <form action="<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/update" method="POST"
            class="profile-form" id="edit-<?= $entityTypeLower ?>-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <?php html_hidden('edit_' . $entityTypeLower . '_id', 'edit_' . $entityTypeLower . '_id', 'id="edit_' . $entityTypeLower . '_id"'); ?>

            <div class="form-group">
                <label><?= $entityType ?> ID</label>
                <?php html_text('edit_' . $entityTypeLower . '_id_display', 'disabled'); ?>
            </div>

            <div class="form-group">
                <label>Username<span class="required">*</span></label>
                <?php if ($isStaffManagement): ?>
                    <?php html_text('edit_username'); ?>
                <?php else: ?>
                    <?php html_text('edit_username', 'disabled'); ?>
                <?php endif; ?>
            </div>
            <?php if ($isStaffManagement): ?>
                <div class="form-group">
                    <label>Position<span class="required">*</span></label>
                    <?php
                    $GLOBALS['edit_position'] = '';
                    // Position selection left without native 'required' attribute
                    html_select('edit_position', [
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                        'manager' => 'Manager',
                        'other' => 'Other (specify below)'
                    ], '- Select Position -');
                    ?>
                </div>
                <div class="form-group" id="edit-custom-position-group" style="display: none;">
                    <label>Custom Position</label>
                    <?php
                    $GLOBALS['edit_custom_position'] = '';
                    html_text('edit_custom_position');
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Full Name<span class="required">*</span></label>
                <?php
                $GLOBALS['edit_fullname'] = '';
                html_text('edit_fullname');
                ?>
            </div>
            <div class="form-group">
                <label>Email<span class="required">*</span></label>
                <?php if ($isStaffManagement): ?>
                    <?php
                    $GLOBALS['edit_email'] = '';
                    // Email requirement enforced via JS/server-side; remove native 'required' attribute
                    html_email('edit_email');
                    ?>
                <?php else: ?>
                    <?php html_email('edit_email', 'disabled style="background-color: #f3f4f6; cursor: not-allowed;"'); ?>
                    <small style="color: #6b7280; font-size: 0.875rem;">Email cannot be changed after verification</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <?php
                $GLOBALS['edit_contact_number'] = '';
                html_phone('edit_contact_number');
                ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Birth Date</label>
                    <?php
                    $GLOBALS['edit_birth_date'] = '';
                    html_date('edit_birth_date');
                    ?>
                </div>

                <div class="form-group">
                    <label>Gender</label>
                    <?php
                    $GLOBALS['edit_gender'] = '';
                    html_select('edit_gender', [
                        'male' => 'Male',
                        'female' => 'Female',
                        'other' => 'Other'
                    ], '- Prefer not to say -');
                    ?>
                </div>
            </div>

            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="user-btn">Update <?= $entityType ?></button>
                <button type="button" class="user-btn user-btn-secondary"
                    onclick="close<?= $entityType ?>EditModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="block-<?= $entityTypeLower ?>-modal">
    <div class="modal-content admin-card" style="max-width: 500px;">
        <button class="modal-close">&times;</button>
        <h2 id="block-<?= $entityTypeLower ?>-modal-title" style="color: #ef4444;">Block <?= $entityType ?></h2>
        <div id="block-<?= $entityTypeLower ?>-warning" class="warning-message"
            style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; margin: 1rem 0;">
            <p style="color: #991b1b; margin-bottom: 0.5rem;"><strong
                    id="block-<?= $entityTypeLower ?>-warning-title">Warning: This action will block the
                    <?= $entityTypeLower ?>!</strong></p>
            <p style="color: #7f1d1d; margin: 0;" id="block-<?= $entityTypeLower ?>-warning-text">The
                <?= $entityTypeLower ?> will be unable to access the system until unblocked.
            </p>
        </div>
        <div style="padding: 1rem 0;">
            <p><strong><?= $entityType ?> ID:</strong> <span id="block-<?= $entityTypeLower ?>-id-display"></span></p>
            <p><strong>Username:</strong> <span id="block-<?= $entityTypeLower ?>-username"></span></p>
            <p><strong>Full Name:</strong> <span id="block-<?= $entityTypeLower ?>-fullname"></span></p>
        </div>
        <form action="<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/toggle-block" method="POST"
            id="block-<?= $entityTypeLower ?>-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <?php html_hidden($entityTypeLower . '_id', '', 'id="block-' . $entityTypeLower . '-id"'); ?>
            <?php html_hidden('action', '', 'id="block-' . $entityTypeLower . '-action" value="block"'); ?>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" id="block-<?= $entityTypeLower ?>-submit-btn" class="user-btn"
                    style="background: #ef4444; border-color: #ef4444;">Yes, Block <?= $entityType ?></button>
                <button type="button" class="user-btn user-btn-secondary"
                    onclick="close<?= $entityType ?>BlockModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="permanent-delete-<?= $entityTypeLower ?>-modal">
    <div class="modal-content admin-card" style="max-width: 550px;">
        <button class="modal-close">&times;</button>
        <h2 style="color: #dc2626;">Permanently Delete <?= $entityType ?></h2>
        <div class="warning-message"
            style="background: #fef2f2; border: 1px solid #fecaca; padding: 1rem; margin: 1rem 0;">
            <p style="color: #991b1b; margin-bottom: 0.5rem;"><strong>DANGER: This action is IRREVERSIBLE!</strong>
            </p>
            <p style="color: #7f1d1d; margin: 0;">Once deleted, this <?= $entityTypeLower ?> account and all associated
                data will be permanently removed from the system. There is NO way to recover this data.</p>
        </div>
        <div style="padding: 1rem 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 1rem;">
            <p><strong><?= $entityType ?> ID:</strong> <span id="permanent-delete-<?= $entityTypeLower ?>-id-display"
                    style="padding: 2px 6px;"></span>
            </p>
            <p><strong>Username:</strong> <span id="permanent-delete-<?= $entityTypeLower ?>-username"></span></p>
            <p><strong>Full Name:</strong> <span id="permanent-delete-<?= $entityTypeLower ?>-fullname"></span></p>
        </div>
        <form action="<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/delete" method="POST"
            id="permanent-delete-<?= $entityTypeLower ?>-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <?php html_hidden($entityTypeLower . '_id', '', 'id="permanent-delete-' . $entityTypeLower . '-id"'); ?>
            <div class="form-group" style="margin-bottom: 1rem;">
                <label style="color: #991b1b; font-weight: 600;">To confirm deletion, type the exact <?= $entityType ?>
                    ID below:</label>
                <input type="text" name="confirm_id" id="permanent-delete-<?= $entityTypeLower ?>-confirm-id"
                    class="form-control" placeholder="Enter <?= $entityType ?> ID to confirm"
                    style="margin-top: 0.5rem;" autocomplete="off" required>
                <small style="color: #6b7280; display: block; margin-top: 0.25rem;">Case-sensitive. Must match
                    exactly.</small>
            </div>
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" id="permanent-delete-<?= $entityTypeLower ?>-submit-btn" class="user-btn"
                    style="background: #dc2626; border-color: #dc2626;">Permanently Delete</button>
                <button type="button" class="user-btn user-btn-secondary"
                    onclick="closePermanentDelete<?= $entityType ?>Modal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    // <?= $entityType ?> Management Modal Functions
    console.log('[U_M_Modal] Script loaded - Entity Type: <?= $entityType ?>, Entity Type Lower: <?= $entityTypeLower ?>');

    // Helper: update admin sidebar when the currently-logged-in staff is changed
    const CURRENT_ADMIN_STAFF_ID = '<?= $currentStaffId ?? '' ?>';
    // Backwards-compatible global for scripts that expect `currentStaffId` to exist
    const currentStaffId = '<?= $currentStaffId ?? '' ?>';
    if (typeof window !== 'undefined') window.currentStaffId = currentStaffId;

    function updateAdminSidebarFromUser(user) {
        if (!user) return;
        const id = user.staff_id || user.user_id || user['<?= $entityTypeLower ?>_id'] || '';
        if (!CURRENT_ADMIN_STAFF_ID || String(id) !== String(CURRENT_ADMIN_STAFF_ID)) return;

        try {
            const nameEl = document.querySelector('.sidebar-user .user-name');
            const roleEl = document.querySelector('.sidebar-user .user-role');
            if (nameEl && user.fullname) nameEl.textContent = user.fullname;
            if (roleEl) {
                const pos = user.position || roleEl.textContent || '';
                roleEl.textContent = pos ? (pos.charAt(0).toUpperCase() + pos.slice(1)) : '';
            }

            // Update avatar if provided
            if (user.profile_photo) {
                const avatarEl = document.getElementById('admin-sidebar-avatar');
                const newSrc = '<?= BASE_URL ?>assets/images/uploads/profile/' + encodeURIComponent(user.profile_photo) + '?t=' + Date.now();
                if (avatarEl) {
                    if (avatarEl.tagName && avatarEl.tagName.toUpperCase() === 'IMG') {
                        avatarEl.src = newSrc;
                    } else {
                        const img = document.createElement('img');
                        img.id = 'admin-sidebar-avatar';
                        img.className = 'admin-sidebar-avatar';
                        img.src = newSrc;
                        img.alt = 'Profile';
                        avatarEl.parentNode.replaceChild(img, avatarEl);
                    }
                }
            }

            console.log('[U_M_Modal] Admin sidebar updated for', id);
        } catch (err) {
            console.error('[U_M_Modal] updateAdminSidebarFromUser error:', err);
        }
    }
    window.updateAdminSidebarFromUser = updateAdminSidebarFromUser;

    function restoreAddSubmitBtn() {
        // Prefer explicit submit button id for reliability
        const btn = document.getElementById('add-<?= $entityTypeLower ?>-submit-btn') || (document.getElementById('add-<?= $entityTypeLower ?>-form') ? document.getElementById('add-<?= $entityTypeLower ?>-form').querySelector('button[type="submit"]') : null);
        if (btn) {
            btn.disabled = false;
            btn.textContent = btn.dataset.originalText || btn.textContent;
            delete btn.dataset.originalText;
        }
        // Clear pending-open flag
        if (window._openAddModalAfterVerificationCancel) {
            window._openAddModalAfterVerificationCancel = false;
        }
    }

    function add<?= $entityType ?>() {
        console.log('[U_M_Modal] Opening add <?= $entityTypeLower ?> modal');
        const addForm = document.getElementById('add-<?= $entityTypeLower ?>-form');
        if (addForm) {
            addForm.reset();
        }
        const idDisplay = document.getElementById('add-<?= $entityTypeLower ?>-id-display');
        if (idDisplay) {
            idDisplay.value = '';
        }
        document.getElementById('add-<?= $entityTypeLower ?>-modal').classList.add('active');
    }

    // Make functions globally accessible
    window.add<?= $entityType ?> = add<?= $entityType ?>;
    window.addUser = add<?= $entityType ?>; // Alias for backwards compatibility
    <?php if ($isStaffManagement): ?>
        window.addStaff = add<?= $entityType ?>;
    <?php endif; ?>

    function close<?= $entityType ?>AddModal() {
        document.getElementById('add-<?= $entityTypeLower ?>-modal').classList.remove('active');
    }

    function close<?= $entityType ?>VerificationModal() {
        document.getElementById('verify-<?= $entityTypeLower ?>-confirmation-modal').classList.remove('active');
        // If we opened the verification modal from the add flow and the admin cancelled,
        // reopen the add modal so they can edit/submit again and re-enable the submit button.
        if (window._openAddModalAfterVerificationCancel) {
            window._openAddModalAfterVerificationCancel = false;
            const addModal = document.getElementById('add-<?= $entityTypeLower ?>-modal');
            if (addModal) addModal.classList.add('active');
            // Re-enable add submit if needed
            restoreAddSubmitBtn();
        }
    }

    function view<?= $entityType ?>(id) {
        console.log('[U_M_Modal] Viewing <?= $entityTypeLower ?>:', id);

        // Fetch <?= $entityTypeLower ?> details via AJAX
        fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + id)
            .then(response => {
                console.log('[U_M_Modal] Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('[U_M_Modal] Fetched data:', data);
                if (data.success) {
                    const user = data.data;
                    console.log('[U_M_Modal] User data:', user);

                    // Populate view modal - use querySelector for more reliability
                    const idField = document.getElementById('view_<?= $entityTypeLower ?>_id');
                    const usernameField = document.getElementById('view_username');
                    const fullnameField = document.getElementById('view_fullname');
                    const emailField = document.getElementById('view_email');
                    const contactField = document.getElementById('view_contact');
                    const birthdateField = document.getElementById('view_birthdate');
                    const genderField = document.getElementById('view_gender');
                    const statusField = document.getElementById('view_status');
                    const lastLoginField = document.getElementById('view_last_login');
                    const createdDateField = document.getElementById('view_created_date');

                    console.log('[U_M_Modal] Setting values - ID field exists:', !!idField);
                    console.log('[U_M_Modal] ID field element:', idField);

                    if (idField) idField.value = user.<?= $entityTypeLower ?>_id || '';
                    if (usernameField) usernameField.value = user.username || '';
                    <?php if ($isStaffManagement): ?>
                        const positionField = document.getElementById('view_position');
                        if (positionField) positionField.value = user.position ? user.position.charAt(0).toUpperCase() + user.position.slice(1) : 'N/A';
                    <?php endif; ?>
                    if (fullnameField) fullnameField.value = user.fullname || '';
                    if (emailField) emailField.value = user.email || '';
                    if (contactField) contactField.value = user.contact_number || 'N/A';
                    if (birthdateField) birthdateField.value = user.birth_date || '';
                    if (genderField) genderField.value = user.gender ? user.gender.charAt(0).toUpperCase() + user.gender.slice(1) : 'N/A';
                    if (statusField) statusField.value = user.is_blocked ? 'Blocked' : 'Active';
                    if (lastLoginField) lastLoginField.value = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
                    if (createdDateField) createdDateField.value = user.created_date ? new Date(user.created_date).toLocaleString() : 'N/A';

                    // Open modal
                    document.getElementById('view-<?= $entityTypeLower ?>-modal').classList.add('active');
                } else {
                    console.error('API error:', data.message);
                    showToast(data.message || 'Failed to load <?= $entityTypeLower ?> details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while loading <?= $entityTypeLower ?> details', 'error');
            });
    }

    // Make viewUser globally accessible
    window.view<?= $entityType ?> = view<?= $entityType ?>;
    window.viewUser = view<?= $entityType ?>;
    <?php if ($isStaffManagement): ?>
        window.viewStaff = view<?= $entityType ?>;
    <?php endif; ?>

    function edit<?= $entityType ?>(id) {
        console.log('[U_M_Modal] Editing <?= $entityTypeLower ?>:', id);

        // Fetch <?= $entityTypeLower ?> details via AJAX
        fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.data;
                    // Populate edit modal
                    const idField = document.getElementById('edit_<?= $entityTypeLower ?>_id');
                    const idDisplayField = document.getElementById('edit_<?= $entityTypeLower ?>_id_display');
                    const usernameField = document.getElementById('edit_username');
                    const fullnameField = document.getElementById('edit_fullname');
                    const emailField = document.getElementById('edit_email');
                    const contactField = document.getElementById('edit_contact_number');
                    const birthDateField = document.getElementById('edit_birth_date');
                    const genderField = document.getElementById('edit_gender');

                    // Populate fields
                    if (idField) idField.value = user.<?= $entityTypeLower ?>_id || '';
                    if (idDisplayField) idDisplayField.value = user.<?= $entityTypeLower ?>_id || '';
                    if (usernameField) usernameField.value = user.username || '';

                    <?php if ($isStaffManagement): ?>
                        const positionSelect = document.getElementById('edit_position');
                        const customPositionGroup = document.getElementById('edit-custom-position-group');
                        const customPositionInput = document.querySelector('input[name="edit_custom_position"]');

                        // Check if position is one of the standard options
                        if (positionSelect && ['admin', 'staff', 'manager'].includes(user.position)) {
                            positionSelect.value = user.position;
                            if (customPositionGroup) customPositionGroup.style.display = 'none';
                        } else if (positionSelect) {
                            // Custom position
                            positionSelect.value = 'other';
                            if (customPositionGroup) {
                                customPositionGroup.style.display = 'block';
                                if (customPositionInput) customPositionInput.value = user.position || '';
                            }
                        }
                    <?php endif; ?>

                    if (fullnameField) fullnameField.value = user.fullname || '';
                    if (emailField) emailField.value = user.email || '';
                    if (contactField) {
                        // Separate stored contact into prefix and number for the phone control.
                        // This prefers a match from the existing prefix <select> options (so +60 matches +60
                        // even when the stored value is like +60123456789). Falls back to a regex and
                        // then extracts only digits for the number input.
                        const contactStr = (user.contact_number || '').trim();
                        const prefixSelect = document.getElementById('edit_contact_number_prefix');
                        let numberOnly = '';
                        let prefixVal = '+60';

                        if (contactStr && contactStr !== 'N/A') {
                            // If prefix select exists, try to detect the prefix from its options
                            if (prefixSelect) {
                                const opts = Array.from(prefixSelect.options).map(o => o.value).sort((a, b) => b.length - a.length);
                                let matched = false;
                                for (const opt of opts) {
                                    if (contactStr === opt) {
                                        // Only the prefix was stored
                                        prefixVal = opt;
                                        numberOnly = '';
                                        matched = true;
                                        break;
                                    }
                                    if (contactStr.startsWith(opt)) {
                                        // Rest may start with a separator or digits
                                        const rest = contactStr.slice(opt.length);
                                        if (/^[-\s]?\d+/.test(rest) || /^[-\s]?$/.test(rest)) {
                                            prefixVal = opt;
                                            numberOnly = rest.replace(/^[-\s]+/, '').replace(/\D/g, '');
                                            matched = true;
                                            break;
                                        }
                                    }
                                }

                                if (!matched) {
                                    // Try a fallback regex (capture +country and rest)
                                    const m = contactStr.match(/^(\+\d{1,3})(?:[-\s]?)(.*)$/);
                                    if (m) {
                                        prefixVal = m[1];
                                        numberOnly = (m[2] || '').replace(/\D/g, '');
                                    } else {
                                        numberOnly = contactStr.replace(/\D/g, '');
                                    }
                                }
                            } else {
                                // No prefix select - simple parsing
                                const m = contactStr.match(/^(\+\d{1,3})(?:[-\s]?)(.*)$/);
                                if (m) {
                                    prefixVal = m[1];
                                    numberOnly = (m[2] || '').replace(/\D/g, '');
                                } else {
                                    numberOnly = contactStr.replace(/\D/g, '');
                                }
                            }
                        }

                        if (prefixSelect) {
                            const hasOption = Array.from(prefixSelect.options).some(o => o.value === prefixVal);
                            prefixSelect.value = hasOption ? prefixVal : (prefixSelect.value || '+60');
                        }
                        contactField.value = numberOnly;
                    }
                    if (birthDateField) birthDateField.value = user.birth_date || '';
                    if (genderField) genderField.value = user.gender || '';

                    // Open modal
                    document.getElementById('edit-<?= $entityTypeLower ?>-modal').classList.add('active');
                } else {
                    showToast(data.message || 'Failed to load <?= $entityTypeLower ?> details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while loading <?= $entityTypeLower ?> details', 'error');
            });
    }

    // Make editUser globally accessible
    window.edit<?= $entityType ?> = edit<?= $entityType ?>;
    window.editUser = edit<?= $entityType ?>;
    <?php if ($isStaffManagement): ?>
        window.editStaff = edit<?= $entityType ?>;
    <?php endif; ?>

    function block<?= $entityType ?>(id, username, fullname) {
        console.log('[U_M_Modal] Toggling block for <?= $entityTypeLower ?>:', id);

        <?php if ($isStaffManagement): ?>
            // Prevent staff from blocking themselves
            const currentStaffId = '<?= $currentStaffId ?>';
            if (id === currentStaffId) {
                showToast('You cannot block yourself!', 'error');
                return;
            }
        <?php endif; ?>

        // Fetch user details to determine current block status
        fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.data;
                    const isBlocked = user.is_blocked;

                    // Populate block modal
                    document.getElementById('block-<?= $entityTypeLower ?>-id').value = id;
                    document.getElementById('block-<?= $entityTypeLower ?>-id-display').textContent = id;
                    document.getElementById('block-<?= $entityTypeLower ?>-username').textContent = username;
                    document.getElementById('block-<?= $entityTypeLower ?>-fullname').textContent = fullname;

                    // Update modal content based on current status
                    const modalTitle = document.getElementById('block-<?= $entityTypeLower ?>-modal-title');
                    const warningBox = document.getElementById('block-<?= $entityTypeLower ?>-warning');
                    const warningTitle = document.getElementById('block-<?= $entityTypeLower ?>-warning-title');
                    const warningText = document.getElementById('block-<?= $entityTypeLower ?>-warning-text');
                    const actionInput = document.getElementById('block-<?= $entityTypeLower ?>-action');
                    const submitBtn = document.getElementById('block-<?= $entityTypeLower ?>-submit-btn');

                    if (isBlocked) {
                        // User is blocked - show unblock UI
                        modalTitle.textContent = 'Unblock <?= $entityType ?>';
                        modalTitle.style.color = '#10b981'; // Green
                        warningBox.style.background = '#f0fdf4';
                        warningBox.style.borderColor = '#bbf7d0';
                        warningTitle.style.color = '#166534';
                        warningTitle.innerHTML = '<strong>Unblock this <?= $entityTypeLower ?>?</strong>';
                        warningText.style.color = '#15803d';
                        warningText.textContent = 'The <?= $entityTypeLower ?> will regain access to the system.';
                        actionInput.value = 'unblock';
                        submitBtn.textContent = 'Yes, Unblock <?= $entityType ?>';
                        submitBtn.style.background = '#10b981';
                        submitBtn.style.borderColor = '#10b981';
                    } else {
                        // User is active - show block UI
                        modalTitle.textContent = 'Block <?= $entityType ?>';
                        modalTitle.style.color = '#ef4444'; // Red
                        warningBox.style.background = '#fef2f2';
                        warningBox.style.borderColor = '#fecaca';
                        warningTitle.style.color = '#991b1b';
                        warningTitle.innerHTML = '<strong>Warning: This action will block the <?= $entityTypeLower ?>!</strong>';
                        warningText.style.color = '#7f1d1d';
                        warningText.textContent = 'The <?= $entityTypeLower ?> will be unable to access the system until unblocked.';
                        actionInput.value = 'block';
                        submitBtn.textContent = 'Yes, Block <?= $entityType ?>';
                        submitBtn.style.background = '#ef4444';
                        submitBtn.style.borderColor = '#ef4444';
                    }

                    // Open modal
                    document.getElementById('block-<?= $entityTypeLower ?>-modal').classList.add('active');
                } else {
                    showToast(data.message || 'Failed to load <?= $entityTypeLower ?> details', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while loading <?= $entityTypeLower ?> details', 'error');
            });
    }

    // Make blockUser globally accessible
    window.block<?= $entityType ?> = block<?= $entityType ?>;
    window.blockUser = block<?= $entityType ?>;
    <?php if ($isStaffManagement): ?>
        window.blockStaff = block<?= $entityType ?>;
    <?php endif; ?>

    // Permanent Delete Functions
    function permanentDelete<?= $entityType ?>(id, username, fullname) {
        console.log('[U_M_Modal] Opening permanent delete modal for <?= $entityTypeLower ?>:', id);

        <?php if ($isStaffManagement): ?>
            // Prevent staff from deleting themselves
            const currentStaffId = '<?= $currentStaffId ?>';
            if (id === currentStaffId) {
                showToast('You cannot delete yourself!', 'error');
                return;
            }
        <?php endif; ?>

        // Populate the permanent delete modal
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-id').value = id;
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-id-display').textContent = id;
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-username').textContent = username;
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-fullname').textContent = fullname;

        // Clear the confirmation input
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-confirm-id').value = '';

        // Open the modal
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-modal').classList.add('active');
    }

    // Make permanentDeleteUser globally accessible
    window.permanentDelete<?= $entityType ?> = permanentDelete<?= $entityType ?>;
    window.permanentDeleteUser = permanentDelete<?= $entityType ?>;
    <?php if ($isStaffManagement): ?>
        window.permanentDeleteStaff = permanentDelete<?= $entityType ?>;
    <?php endif; ?>

    function closePermanentDelete<?= $entityType ?>Modal() {
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-modal').classList.remove('active');
        // Clear the confirmation input when closing
        document.getElementById('permanent-delete-<?= $entityTypeLower ?>-confirm-id').value = '';
    }

    function close<?= $entityType ?>ViewModal() {
        document.getElementById('view-<?= $entityTypeLower ?>-modal').classList.remove('active');
    }

    function close<?= $entityType ?>EditModal() {
        document.getElementById('edit-<?= $entityTypeLower ?>-modal').classList.remove('active');
    }

    function close<?= $entityType ?>BlockModal() {
        document.getElementById('block-<?= $entityTypeLower ?>-modal').classList.remove('active');
    }

    // Remove row from table after permanent delete
    function removeTableRow<?= $entityType ?>(id) {
        const row = document.querySelector(`tr[data-<?= $entityTypeLower ?>-id="${id}"]`);
        if (row) {
            row.style.transition = 'opacity 0.3s ease, background-color 0.3s ease';
            row.style.backgroundColor = '#fef2f2';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
                // Check if table is empty and show empty message
                const tbody = document.querySelector('table tbody');
                if (tbody && tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.innerHTML = '<td colspan="<?= $isStaffManagement ? '9' : '8' ?>" style="text-align: center; padding: 2rem; color: var(--accent-gray);">No <?= $entityTypeLower ?>s found</td>';
                    tbody.appendChild(emptyRow);
                }
            }, 300);
        }
    }

    // Update table row after edit or block/unblock
    function updateTableRow<?= $entityType ?>(id) {
        // Fetch updated data
        fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.data;

                    // Find the table row
                    const row = document.querySelector(`tr[data-<?= $entityTypeLower ?>-id="${id}"]`);
                    if (!row) return;

                    const cells = row.querySelectorAll('td');
                    if (cells.length >= 5) {

                        // NOTE: Javascript Nodelists are 0-indexed. 
                        // 0=ID, 1=Username, 2=Fullname, 3=Email...
                        let cellIndex = 1;

                        // --- 1. Update Username (Needs .truncate-text) ---
                        if (cells[cellIndex]) {
                            cells[cellIndex].innerHTML = `<div class="truncate-text" title="${htmlEscape(user.username)}">${htmlEscape(user.username)}</div>`;
                            cellIndex++;
                        }

                        // --- 2. Update Full Name (Needs .truncate-text) ---
                        if (cells[cellIndex]) {
                            cells[cellIndex].innerHTML = `<div class="truncate-text" title="${htmlEscape(user.fullname)}">${htmlEscape(user.fullname)}</div>`;
                            cellIndex++;
                        }

                        // --- 3. Update Email (Needs .truncate-email) ---
                        if (cells[cellIndex]) {
                            cells[cellIndex].innerHTML = `<div class="truncate-email" title="${htmlEscape(user.email)}">${htmlEscape(user.email)}</div>`;
                            cellIndex++;
                        }

                        <?php if ($isStaffManagement): ?>
                            // Update position (staff only)
                            if (cells[cellIndex]) {
                                // Position usually doesn't have a special div wrapper, just text
                                cells[cellIndex].textContent = user.position ? user.position.charAt(0).toUpperCase() + user.position.slice(1) : 'N/A';
                                cellIndex++;
                            }
                        <?php endif; ?>

                        // --- 4. Update Contact ---
                        if (cells[cellIndex]) {
                            cells[cellIndex].textContent = user.contact_number || 'N/A';
                            cellIndex++;
                        }

                        // --- 5. Update Status (Needs span classes) ---
                        if (cells[cellIndex]) {
                            const isBlocked = user.is_blocked == 1; // Ensure strictly boolean/checked
                            cells[cellIndex].innerHTML = isBlocked ?
                                '<span class="order-status status-cancelled">Blocked</span>' :
                                '<span class="order-status status-completed">Active</span>';
                            cellIndex++;
                        }

                        // --- 6. Update Last Login ---
                        if (cells[cellIndex]) {
                            // Replicate PHP date format: m/d/Y, g:i:s A
                            cells[cellIndex].textContent = user.last_login ?
                                new Date(user.last_login).toLocaleString('en-US', {
                                    year: 'numeric',
                                    month: '2-digit',
                                    day: '2-digit',
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    second: '2-digit',
                                    hour12: true
                                }) :
                                'Never';
                            cellIndex++;
                        }

                        // --- 6. Update Action Buttons (Needs .action-buttons wrapper) ---
                        if (cells[cellIndex]) {
                            const actionsCell = cells[cellIndex];
                            const isBlocked = user.is_blocked == 1;
                            const entityType = '<?= $entityType ?>';
                            const entityTypeLower = '<?= $entityTypeLower ?>';

                            <?php if ($isStaffManagement): ?>
                                const currentStaffId = '<?= $currentStaffId ?>';
                                // Ensure loose comparison or string conversion for IDs
                                const isCurrentUser = String(user.staff_id) === String(currentStaffId);
                            <?php endif; ?>

                            let buttonsHtml = '';

                            <?php if ($isStaffManagement): ?>
                                if (isCurrentUser) {
                                    buttonsHtml = `
                                    <a href="#" onclick="view${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn">View</a>
                                    <a href="#" onclick="edit${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn admin-btn-secondary">Edit</a>
                                    <a href="#" class="admin-btn" style="background: #9ca3af; border-color: #9ca3af; opacity: 0.5; cursor: not-allowed;" title="You cannot block yourself">Block</a>
                                    <a href="#" class="admin-btn" style="background: #9ca3af; border-color: #9ca3af; opacity: 0.5; cursor: not-allowed;" title="You cannot delete yourself">Delete</a>`;
                                } else {
                                    buttonsHtml = `
                                    <a href="#" onclick="view${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn admin-btn-small">View</a>
                                    <a href="#" onclick="edit${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn admin-btn-small admin-btn-secondary">Edit</a>
                                    <a href="#" onclick="block${entityType}('${user[entityTypeLower + '_id']}', '${htmlEscape(user.username)}', '${htmlEscape(user.fullname)}'); return false;" class="admin-btn admin-btn-danger">${isBlocked ? 'Unblock' : 'Block'}</a>
                                    <a href="#" onclick="permanentDelete${entityType}('${user[entityTypeLower + '_id']}', '${htmlEscape(user.username)}', '${htmlEscape(user.fullname)}'); return false;" class="admin-btn admin-btn-danger" title="Permanently delete staff">Delete</a>`;
                                }
                            <?php else: ?>
                                // User Management
                                buttonsHtml = `
                                <a href="#" onclick="view${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn">View</a>
                                <a href="#" onclick="edit${entityType}('${user[entityTypeLower + '_id']}'); return false;" class="admin-btn admin-btn-secondary">Edit</a>
                                <a href="#" onclick="block${entityType}('${user[entityTypeLower + '_id']}', '${htmlEscape(user.username)}', '${htmlEscape(user.fullname)}'); return false;" class="admin-btn admin-btn-danger">${isBlocked ? 'Unblock' : 'Block'}</a>
                                <a href="#" onclick="permanentDelete${entityType}('${user[entityTypeLower + '_id']}', '${htmlEscape(user.username)}', '${htmlEscape(user.fullname)}'); return false;" class="admin-btn admin-btn-danger" title="Permanently delete user">Delete</a>`;
                            <?php endif; ?>

                            // IMPORTANT: Wrap in div.action-buttons to maintain CSS layout
                            actionsCell.innerHTML = `<div class="action-buttons">${buttonsHtml}</div>`;
                        }
                    }

                    // Highlight effect
                    row.style.backgroundColor = '#dbeafe';
                    setTimeout(() => {
                        row.style.transition = 'background-color 1s ease';
                        row.style.backgroundColor = '';
                    }, 100);
                    setTimeout(() => {
                        row.style.transition = '';
                    }, 1100);

                    // Update admin sidebar if the current staff is edited
                    updateAdminSidebarFromUser(user);
                }
            })
            .catch(error => {
                console.error('Error updating table row:', error);
            });
    }

    // Handle position change for staff to show/hide custom position field
    <?php if ($isStaffManagement): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Add modal position select
            const addPositionSelect = document.getElementById('add_position');
            const addCustomPositionGroup = document.getElementById('custom-position-group');

            if (addPositionSelect && addCustomPositionGroup) {
                addPositionSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        addCustomPositionGroup.style.display = 'block';
                    } else {
                        addCustomPositionGroup.style.display = 'none';
                    }
                });
            }

            // Edit modal position select (already exists inline in the edit modal, but adding here for completeness)
            const editPositionSelect = document.getElementById('edit_position');
            const editCustomPositionGroup = document.getElementById('edit-custom-position-group');

            if (editPositionSelect && editCustomPositionGroup) {
                editPositionSelect.addEventListener('change', function() {
                    if (this.value === 'other') {
                        editCustomPositionGroup.style.display = 'block';
                    } else {
                        editCustomPositionGroup.style.display = 'none';
                    }
                });
            }
        });
    <?php endif; ?>

    // Handle add form submission
    document.addEventListener('DOMContentLoaded', function() {
        const addForm = document.getElementById('add-<?= $entityTypeLower ?>-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    // Persist original text so we can restore it later
                    submitBtn.dataset.originalText = originalText;
                }

                // Prepare form data
                const formData = new FormData(this);

                // Determine checkbox and email states (prefer DOM values)
                let sendVerification = false;
                const cb = document.getElementById('add_send_verification');
                if (cb) sendVerification = !!cb.checked;
                else sendVerification = !!formData.get('add_send_verification');

                let email = formData.get('add_email');
                const emailEl = document.getElementById('add_email');
                if ((!email || email === '') && emailEl) email = emailEl.value;

                // Front-end validation before server-side simulate
                if (window.InputValidators) {
                    const isStaff = <?= $isStaffManagement ? 'true' : 'false' ?>;
                    const valid = InputValidators.validateUserForm(this, {
                        isStaff: isStaff,
                        isAdd: true
                    });
                    if (!valid) {
                        // Validation failed - re-enable submit and stop
                        restoreAddSubmitBtn();
                        return;
                    }
                }

                // Disable submit while validating
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Validating...';
                }

                // Create a simulate FormData copy
                const simulateData = new FormData(this);
                simulateData.append('simulate', '1');

                const action = this.getAttribute('action');

                fetch(action, {
                        method: 'POST',
                        body: simulateData
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (!result.success) {
                            // Validation failed - show error and re-enable submit
                            showToast(result.message || 'Validation failed', 'error');
                            restoreAddSubmitBtn();
                            return;
                        }

                        // Validation passed on server
                        if (sendVerification && email) {
                            // Close add modal and show confirmation modal
                            close<?= $entityType ?>AddModal();
                            window._openAddModalAfterVerificationCancel = true;

                            document.getElementById('verify-<?= $entityTypeLower ?>-email-display').textContent = email;

                            const confirmBtn = document.getElementById('verify-<?= $entityTypeLower ?>-confirm-btn');

                            // Ensure previous handlers are removed
                            confirmBtn.onclick = null;

                            const onConfirm = function() {
                                confirmBtn.disabled = true;

                                // Re-validate before actual create (safety check)
                                if (window.InputValidators) {
                                    const addFormEl = addForm; // captured from outer scope
                                    const isStaff = <?= $isStaffManagement ? 'true' : 'false' ?>;
                                    const valid = InputValidators.validateUserForm(addFormEl, {
                                        isStaff: isStaff,
                                        isAdd: true
                                    });
                                    if (!valid) {
                                        showToast('Validation failed. Please review the form.', 'error');
                                        confirmBtn.disabled = false;
                                        // Reopen add modal so admin can edit
                                        close<?= $entityType ?>VerificationModal();
                                        const addModal = document.getElementById('add-<?= $entityTypeLower ?>-modal');
                                        if (addModal) addModal.classList.add('active');
                                        restoreAddSubmitBtn();
                                        return;
                                    }
                                }

                                submitAddForm(formData).then(data => {
                                    // Always restore the add submit button when the create attempt completes
                                    restoreAddSubmitBtn();

                                    if (!data.success) {
                                        // Creation failed - reopen add modal for editing
                                        showToast(data.message || 'Failed to create user', 'error');
                                        close<?= $entityType ?>VerificationModal();

                                        const addModal = document.getElementById('add-<?= $entityTypeLower ?>-modal');
                                        if (addModal) addModal.classList.add('active');

                                        // Re-enable submit
                                        // restoreAddSubmitBtn already handled
                                    }
                                    // On success, submitAddForm already handled success UI (closing modals and adding row)
                                    confirmBtn.disabled = false;
                                }).catch(err => {
                                    console.error(err);
                                    showToast('An error occurred while creating user', 'error');
                                    close<?= $entityType ?>VerificationModal();
                                    // Ensure submit button is restored
                                    restoreAddSubmitBtn();
                                    confirmBtn.disabled = false;
                                });
                            };

                            confirmBtn.addEventListener('click', onConfirm, {
                                once: true
                            });

                            // Ensure cancel/close reopen add modal and re-enable submit
                            const verifyModal = document.getElementById('verify-<?= $entityTypeLower ?>-confirmation-modal');
                            const verifyClose = verifyModal.querySelector('.modal-close');
                            if (verifyClose) verifyClose.onclick = function() {
                                close<?= $entityType ?>VerificationModal();
                            };
                            const cancelBtn = verifyModal.querySelector('button.user-btn.user-btn-secondary');
                            if (cancelBtn) cancelBtn.onclick = function() {
                                close<?= $entityType ?>VerificationModal();
                            };

                            document.getElementById('verify-<?= $entityTypeLower ?>-confirmation-modal').classList.add('active');
                        } else {
                            // No verification requested - perform actual create
                            // Final client-side validation (safety)
                            if (window.InputValidators) {
                                const isStaff = <?= $isStaffManagement ? 'true' : 'false' ?>;
                                if (!InputValidators.validateUserForm(this, {
                                        isStaff: isStaff,
                                        isAdd: true
                                    })) {
                                    restoreAddSubmitBtn();
                                    return;
                                }
                            }
                            submitAddForm(formData).then(data => {
                                // Ensure the add submit button is restored when the create attempt completes
                                restoreAddSubmitBtn();
                                if (!data.success) {
                                    // Re-enable submit so admin can fix
                                    // restoreAddSubmitBtn already handled
                                }
                            }).catch(err => {
                                console.error(err);
                                showToast('An error occurred while creating user', 'error');
                                // ensure button restored
                                restoreAddSubmitBtn();
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Validation request error:', error);
                        showToast('Validation request failed', 'error');
                        restoreAddSubmitBtn();
                    });
            });
        }
    });

    function submitAddForm(formData) {
        const action = document.getElementById('add-<?= $entityTypeLower ?>-form').getAttribute('action');

        return fetch(action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.send_verification) {
                    showToast('Verification email sent successfully', 'success');
                }
                // Restore add form submit button and original text
                restoreAddSubmitBtn();
                if (data.success) {
                    // Close verification modal and ensure add modal is closed
                    document.getElementById('verify-<?= $entityTypeLower ?>-confirmation-modal').classList.remove('active');
                    document.getElementById('add-<?= $entityTypeLower ?>-modal').classList.remove('active');

                    // Reset the add form so subsequent adds start fresh
                    const addFormEl = document.getElementById('add-<?= $entityTypeLower ?>-form');
                    if (addFormEl) addFormEl.reset();

                    addNewRowToTable<?= $entityType ?>(data.data);

                    // Update admin sidebar if the current staff is added
                    updateAdminSidebarFromUser(data.data);
                }

                return data;
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
                restoreAddSubmitBtn();
                throw error;
            });
    }

    function addNewRowToTable<?= $entityType ?>(userData) {
        const tbody = document.querySelector('table tbody');
        if (!tbody) {
            setTimeout(() => {
                location.reload();
            }, 500);
            return;
        }

        const emptyRow = tbody.querySelector('tr td[colspan]');
        if (emptyRow) {
            emptyRow.parentElement.remove();
        }

        const newRow = document.createElement('tr');
        newRow.setAttribute('data-<?= $entityTypeLower ?>-id', userData.<?= $entityTypeLower ?>_id);

        <?php if ($isStaffManagement): ?>
            newRow.innerHTML = `
                <td>${htmlEscape(userData.staff_id)}</td>

                <td><div class="truncate-text" title="${htmlEscape(userData.username)}">${htmlEscape(userData.username)}</div></td>

                <td><div class="truncate-text" title="${htmlEscape(userData.fullname)}">${htmlEscape(userData.fullname)}</div></td>

                <td><div class="truncate-email" title="${htmlEscape(userData.email)}">${htmlEscape(userData.email)}</div></td>

                <td>${htmlEscape((userData.position || 'staff').charAt(0).toUpperCase() + (userData.position || 'staff').slice(1))}</td>

                <td>${htmlEscape(userData.contact_number || 'N/A')}</td>

                <td>
                    ${userData.is_blocked == 1 
                        ? '<span class="order-status status-cancelled">Blocked</span>' 
                        : '<span class="order-status status-completed">Active</span>'}
                </td>

                <td class="col-last-login">
                    ${userData.last_login 
                        ? new Date(userData.last_login).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }) 
                        : 'Never'}
                </td>

                <td>
                    <div class="action-buttons">
                        <a href="#" onclick="viewStaff('${htmlEscape(userData.staff_id)}'); return false;" class="admin-btn">View</a>
                        
                        <a href="#" onclick="editStaff('${htmlEscape(userData.staff_id)}'); return false;" class="admin-btn admin-btn-secondary">Edit</a>

                        ${ (String(userData.staff_id) === String(currentStaffId)) 
                            ? `
                                <a href="#" class="admin-btn" style="background: #9ca3af; border-color: #9ca3af; opacity: 0.5; cursor: not-allowed;" title="You cannot block yourself">Block</a>
                                <a href="#" class="admin-btn" style="background: #9ca3af; border-color: #9ca3af; opacity: 0.5; cursor: not-allowed;" title="You cannot delete yourself">Delete</a>
                            `
                            : `
                                <a href="#" onclick="blockStaff('${htmlEscape(userData.staff_id)}', '${htmlEscape(userData.username)}', '${htmlEscape(userData.fullname)}'); return false;" class="admin-btn admin-btn-danger">
                                    ${userData.is_blocked == 1 ? 'Unblock' : 'Block'}
                                </a>
                                <a href="#" onclick="permanentDeleteStaff('${htmlEscape(userData.staff_id)}', '${htmlEscape(userData.username)}', '${htmlEscape(userData.fullname)}'); return false;" class="admin-btn admin-btn-danger" title="Permanently delete staff">Delete</a>
                            `
                        }
                    </div>
                </td>
            `;
        <?php else: ?>
            newRow.innerHTML = `
                <td>${htmlEscape(userData.user_id)}</td>

                <td><div class="truncate-text" title="${htmlEscape(userData.username)}">${htmlEscape(userData.username)}</div></td>

                <td><div class="truncate-text" title="${htmlEscape(userData.fullname)}">${htmlEscape(userData.fullname)}</div></td>

                <td><div class="truncate-email" title="${htmlEscape(userData.email)}">${htmlEscape(userData.email)}</div></td>

                <td>${htmlEscape(userData.contact_number || 'N/A')}</td>

                <td>
                    ${userData.is_blocked == 1 
                        ? '<span class="order-status status-cancelled">Blocked</span>' 
                        : '<span class="order-status status-completed">Active</span>'}
                </td>

                <td class="col-last-login">
                    ${userData.last_login 
                        ? new Date(userData.last_login).toLocaleString('en-US', { year: 'numeric', month: '2-digit', day: '2-digit', hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true }) 
                        : 'Never'}
                </td>

                <td>
                    <div class="action-buttons">
                        <a href="#" onclick="viewUser('${htmlEscape(userData.user_id)}'); return false;" class="admin-btn">View</a>
                        
                        <a href="#" onclick="editUser('${htmlEscape(userData.user_id)}'); return false;" class="admin-btn admin-btn-secondary">Edit</a>
                        
                        <a href="#" onclick="blockUser('${htmlEscape(userData.user_id)}', '${htmlEscape(userData.username)}', '${htmlEscape(userData.fullname)}'); return false;" class="admin-btn admin-btn-danger">
                            ${userData.is_blocked == 1 ? 'Unblock' : 'Block'}
                        </a>
                        
                        <a href="#" onclick="permanentDeleteUser('${htmlEscape(userData.user_id)}', '${htmlEscape(userData.username)}', '${htmlEscape(userData.fullname)}'); return false;" class="admin-btn admin-btn-danger" title="Permanently delete user">Delete</a>
                    </div>
                </td>
            `;
        <?php endif; ?>

        tbody.insertBefore(newRow, tbody.firstChild);

        newRow.style.backgroundColor = '#dbeafe';
        setTimeout(() => {
            newRow.style.transition = 'background-color 1s ease';
            newRow.style.backgroundColor = '';
        }, 100);
        setTimeout(() => {
            newRow.style.transition = '';
        }, 1100);
    }

    function htmlEscape(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    // Helper: If server did not return updated data on edit/block, fetch the staff/user details
    // and update the admin sidebar if the affected id matches the current admin's id.
    function fetchAndUpdateAdminSidebarById(id) {
        if (!id) return;
        try {
            const currentId = '<?= $currentStaffId ?>';
            // Only attempt fetch when this action concerns the current admin
            if (!currentId || String(currentId) !== String(id)) return;

            fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + encodeURIComponent(id))
                .then(res => res.json())
                .then(data => {
                    if (data && data.success && data.data && typeof updateAdminSidebarFromUser === 'function') {
                        try {
                            updateAdminSidebarFromUser(data.data);
                        } catch (err) {
                            console.error('[U_M_Modal] updateAdminSidebarFromUser error:', err);
                        }
                    }
                })
                .catch(err => console.error('[U_M_Modal] fetchAndUpdateAdminSidebarById error:', err));
        } catch (err) {
            console.error('[U_M_Modal] fetchAndUpdateAdminSidebarById unexpected error:', err);
        }
    }

    // Handle edit form submission
    document.addEventListener('DOMContentLoaded', function() {
        const editForm = document.getElementById('edit-<?= $entityTypeLower ?>-form');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Client-side validation mirroring server rules
                if (window.InputValidators) {
                    const isStaff = <?= $isStaffManagement ? 'true' : 'false' ?>;
                    const ok = InputValidators.validateUserForm(this, {
                        isStaff: isStaff,
                        isAdd: false
                    });
                    if (!ok) return; // validation function already shows toast and focuses
                }

                // Disable submit button to prevent double submit
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.dataset.originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Updating...';
                }

                const formData = new FormData(this);
                const action = this.getAttribute('action');

                // Fix to avoid syntax error warning in IDE
                const entityId = formData.get('edit_<?= $entityTypeLower ?>_id') || formData.get('<?= $entityTypeLower ?>_id') || formData.get('user_id');

                fetch(action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            close<?= $entityType ?>EditModal();

                            // If the server returned updated user data, update admin sidebar immediately
                            if (data.data) {
                                try {
                                    updateAdminSidebarFromUser(data.data);
                                } catch (err) {
                                    console.error('[U_M_Modal] Failed to update admin sidebar:', err);
                                }
                            } else {
                                // Fallback: update immediately from form values if current admin affected, then fetch for authoritative data.
                                const currentIdForFallback = '<?= $currentStaffId ?>';
                                // Fix
                                if (currentIdForFallback && String(currentIdForFallback) === String(entityId)) {
                                    // Build temporary user object from submitted form data to update the sidebar immediately
                                    const immediateUser = {
                                        staff_id: entityId,
                                        fullname: formData.get('edit_fullname') || formData.get('fullname') || ''
                                    };

                                    // Try to read position from DOM (works on staff pages where position select exists)
                                    const posEl = document.getElementById('edit_position');
                                    if (posEl) {
                                        let posVal = posEl.value;
                                        if (posVal === 'other') {
                                            const custom = document.querySelector('input[name="edit_custom_position"]');
                                            if (custom) posVal = custom.value;
                                        }
                                        immediateUser.position = posVal;
                                    }

                                    if (typeof updateAdminSidebarFromUser === 'function') {
                                        try {
                                            updateAdminSidebarFromUser(immediateUser);
                                        } catch (e) {
                                            console.error(e);
                                        }
                                    }

                                    // Also fetch the authoritative updated record and apply it when available
                                    fetch('<?= BASE_URL ?>admin/<?= $isStaffManagement ? 'staff' : 'users' ?>/get?id=' + encodeURIComponent(entityId))
                                        .then(res => res.json())
                                        .then(resp => {
                                            if (resp && resp.success && resp.data && typeof updateAdminSidebarFromUser === 'function') {
                                                try {
                                                    updateAdminSidebarFromUser(resp.data);
                                                } catch (e) {
                                                    console.error('[U_M_Modal] updateAdminSidebarFromUser fallback error:', e);
                                                }
                                            }
                                        })
                                        .catch(err => console.error('[U_M_Modal] fallback fetch error:', err));
                                }
                            }

                            // Update the table row dynamically
                            // Fix
                            updateTableRow<?= $entityType ?>(entityId);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred', 'error');
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = submitBtn.dataset.originalText || submitBtn.textContent;
                            delete submitBtn.dataset.originalText;
                        }
                    });
            });
        }


        // Handle block form submission
        const blockForm = document.getElementById('block-<?= $entityTypeLower ?>-form');
        if (blockForm) {
            blockForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const action = this.getAttribute('action');

                // Fix
                const entityId = formData.get('<?= $entityTypeLower ?>_id') || formData.get('user_id');

                fetch(action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json()).then(data => {
                        showToast(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            close<?= $entityType ?>BlockModal();

                            // If returned user data present and current admin, update sidebar
                            if (data.data) {
                                try {
                                    updateAdminSidebarFromUser(data.data);
                                } catch (err) {
                                    console.error(err);
                                }
                            } else {
                                // Fallback: fetch and update admin sidebar if current admin affected
                                fetchAndUpdateAdminSidebarById(entityId);
                            }

                            // Update the table row status dynamically
                            updateTableRow<?= $entityType ?>(entityId);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred', 'error');
                    });
            });
        }

        // Handle permanent delete form submission
        const permanentDeleteForm = document.getElementById('permanent-delete-<?= $entityTypeLower ?>-form');
        if (permanentDeleteForm) {
            permanentDeleteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const action = this.getAttribute('action');

                // Fix
                const entityId = formData.get('<?= $entityTypeLower ?>_id');
                const confirmId = formData.get('confirm_id');

                // Frontend validation: check if confirm_id matches (case-sensitive)
                if (confirmId !== entityId) {
                    showToast('Confirmation ID does not match. Please type the exact <?= $entityType ?> ID.', 'error');
                    document.getElementById('permanent-delete-<?= $entityTypeLower ?>-confirm-id').focus();
                    return;
                }

                // Disable submit button
                const submitBtn = document.getElementById('permanent-delete-<?= $entityTypeLower ?>-submit-btn');
                if (submitBtn) {
                    submitBtn.dataset.originalText = submitBtn.textContent;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Deleting...';
                }

                fetch(action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        showToast(data.message, data.success ? 'success' : 'error');
                        if (data.success) {
                            closePermanentDelete<?= $entityType ?>Modal();
                            // Remove the row from the table
                            removeTableRow<?= $entityType ?>(entityId);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred', 'error');
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = submitBtn.dataset.originalText || submitBtn.textContent;
                            delete submitBtn.dataset.originalText;
                        }
                    });
            });
        }

        // Close modal with close button
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', function() {
                this.closest('.modal-overlay').classList.remove('active');
            });
        });
    });
</script>