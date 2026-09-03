<?php
require_once __DIR__ . '/../../../config/init.php';

$csrfToken = Security::generateCSRF();
$pageTitle = 'Forgot Password - Admin';
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
            <h1>FORGOT PASSWORD</h1>
            <p>Reset your admin account password</p>
        </div>

        <form action="<?= BASE_URL ?>admin/forgot-password/request" method="POST" class="auth-form" id="admin-forgot-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Email Address</label>
                <?php html_email('email', 'placeholder="Enter your email"'); ?>
            </div>

            <button type="submit" class="auth-btn">Send Reset Link</button>
        </form>

        <div class="auth-links">
            <p>Remember your password? <a href="<?= BASE_URL ?>admin/login">Back to Login</a></p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/admin/footer.php'; ?>