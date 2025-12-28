<?php
require_once __DIR__ . '/../../../config/init.php';

$csrfToken = Security::generateCSRF();
$token = $_GET['token'] ?? '';
$pageTitle = 'Reset Password - Admin';
?>

<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <script src="<?= BASE_URL ?>assets/js/toast.js" defer></script>
    <script src="<?= BASE_URL ?>assets/js/admin_auth.js"></script>
    <script src="<?= BASE_URL ?>assets/js/input.js"></script>
</head>

<div class="auth-page">
    <div class="bg-grid"></div>
    <div class="auth-container">
        <div class="auth-header">
            <h1>RESET PASSWORD</h1>
            <p>Enter your new password</p>
        </div>
        <form action="<?= BASE_URL ?>admin/reset-password/process" method="POST" class="auth-form" id="admin-reset-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label>New Password <span class="required">*</span></label>
                <?php // Validation handled via JS and server-side; remove native 'required'
                html_password('password', 'placeholder="Enter new password"'); ?>
                <small style="color: #6b7280; display: block; margin-top: 0.5rem;">Must be 8+ characters with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label>Confirm Password <span class="required">*</span></label>
                <?php html_password('confirm_password', 'placeholder="Confirm new password"'); ?>
            </div>

            <button type="submit" class="auth-btn">Reset Password</button>
        </form>

        <div class="auth-links">
            <p>Remember your password? <a href="<?= BASE_URL ?>admin/login">Back to Login</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/admin/footer.php'; ?>