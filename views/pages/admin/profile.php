<?php
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../includes/admin/auth_required.php';
require_once __DIR__ . '/../../../models/StaffData.php';

$staffId = Session::getAdmin('staff_id');
$staffDataModel = new StaffData();
$profile = $staffDataModel->getProfileWithLogin($staffId);

$csrfToken = Security::generateCSRF();
$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? '';

// Set GLOBALS for HTML helpers
$GLOBALS['fullname'] = $profile['fullname'];
$GLOBALS['email'] = $profile['email'];
$GLOBALS['contact_number'] = $profile['contact_number'] ?? '';
$GLOBALS['birth_date'] = $profile['birth_date'] ?? '';
$GLOBALS['gender'] = $profile['gender'] ?? '';
$GLOBALS['old_password'] = '';
$GLOBALS['new_password'] = '';
$GLOBALS['username'] = htmlspecialchars($profile['username']);
$GLOBALS['position'] = htmlspecialchars($profile['position'] ?? 'N/A');
$last_login_value = $profile['last_login'] ? date('Y-m-d H:i', strtotime($profile['last_login'])) : 'Never';
$GLOBALS['last_login'] = $last_login_value;

$pageTitle = 'Profile';
include __DIR__ . '/../../../includes/admin/header.php';
?>

<div class="admin-header-bar">
    <div>
        <p class="admin-breadcrumb">Admin / Profile</p>
        <h1>MY PROFILE</h1>
    </div>
</div>

<?php if ($message): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showToast('<?= addslashes(htmlspecialchars($message)) ?>', '<?= $messageType === 'success' ? 'success' : 'error' ?>');
        });
    </script>
<?php endif; ?>

<div class="admin-profile-container">
    <!-- Single Column Layout - No Sidebar Needed -->
    <div class="admin-profile-content"><!-- Profile Photo -->
        <div class="admin-card">
            <h2 class="admin-card-header">Profile Photo</h2>
            <form action="<?= BASE_URL ?>admin/profile/upload-photo" method="POST" enctype="multipart/form-data" id="photo-upload-form">
                <?php html_hidden('csrf_token', $csrfToken); ?>
                <input type="hidden" name="action" value="upload_photo">
                <input type="hidden" name="cropped_image" id="cropped-image-data">
                <div class="photo-upload-container">
                    <div class="photo-upload-frame" id="photo-upload-frame">
                        <?php if ($profile['profile_photo']): ?>
                            <img src="<?= BASE_URL ?>assets/images/uploads/profile/<?= htmlspecialchars($profile['profile_photo']) ?>?t=<?= time() ?>" alt="Profile" id="photo-preview">
                        <?php else: ?>
                            <div class="photo-upload-placeholder">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <div>Click to upload</div>
                            </div>
                        <?php endif; ?>
                        <div class="photo-upload-overlay">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>Upload Picture</span>
                        </div>
                    </div>
                    <?php html_file('profile_photo', 'photo-file-input', 'accept="image/jpeg,image/png,image/gif"'); ?>
                    <small>JPG, PNG, GIF - Max 2MB</small>
                    <button type="submit" class="user-btn" id="upload-photo-btn">Upload Photo</button>
                </div>
            </form>
        </div>

        <!-- Profile Information -->
        <div class="admin-card">
            <h2 class="admin-card-header">Profile Information</h2>
            <form action="<?= BASE_URL ?>admin/profile/update" method="POST" class="profile-form" id="update-profile-form">
                <?php html_hidden('csrf_token', $csrfToken); ?>

                <div class="form-group">
                    <label>Username</label>
                    <?php html_text('username', 'disabled'); ?>
                </div>

                <div class="form-group">
                    <label>Position</label>
                    <?php html_text('position', 'disabled'); ?>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <?php html_text('fullname'); ?>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <?php html_email('email'); ?>
                </div>

                <div class="form-group">
                    <label>Contact Number</label>
                    <?php html_phone('contact_number'); ?>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Birth Date</label>
                        <?php html_date('birth_date'); ?>
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <?php html_select('gender', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], '- Prefer not to say -'); ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Last Login</label>
                    <?php html_text('last_login', 'disabled'); ?>
                </div> <button type="submit" class="user-btn">Update Profile</button>
                <button type="button" id="open-password-modal" class="user-btn" style="background: #ef4444; border-color: #ef4444;">Change Password</button>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal-overlay" id="password-modal">
    <div class="modal-content admin-card">
        <button class="modal-close">&times;</button>
        <h2>Change Password</h2>
        <form action="<?= BASE_URL ?>admin/profile/change-password" method="POST" class="profile-form" id="change-password-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Old Password</label>
                <?php html_password('old_password', 'placeholder="Enter old password"'); ?>
            </div>

            <div class="form-group">
                <label>New Password</label>
                <?php html_password('new_password', 'placeholder="Enter new password"'); ?>
                <small>8+ chars with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <?php html_password('confirm_password', 'placeholder="Enter confirm password"'); ?>
            </div>

            <button type="submit" class="user-btn">Change Password</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/photo_upload_modal.php'; ?>
<?php include __DIR__ . '/../../../includes/admin/footer.php'; ?>