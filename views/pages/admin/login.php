<?php
require_once __DIR__ . '/../../../config/init.php';

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['admin'])) {
    header('Location: ' . BASE_URL . 'admin/dashboard');
    exit;
}

$csrfToken = Security::generateCSRF();
?>
<!DOCTYPE html>
<html lang="en" class="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | BlindeDoos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <script src="<?= BASE_URL ?>assets/js/toast.js" defer></script>
    <script src="<?= BASE_URL ?>assets/js/admin_auth.js"></script>
    <script src="<?= BASE_URL ?>assets/js/input.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>

<body>
    <div class="auth-page">
        <div class="bg-grid"></div>
        <div class="auth-container">
            <div class="auth-header">
                <h1>ADMIN LOGIN</h1>
                <p>Staff & Admin Access Only</p>
            </div>
            <form action="<?= BASE_URL ?>admin/login" method="POST" class="auth-form" autocomplete="off">
                <?php html_hidden('csrf_token', $csrfToken); ?>
                <div class="form-group">
                    <label>Username or Email</label>
                    <?php html_text('username', 'placeholder="Enter username or email"'); ?>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <?php html_password('password', 'placeholder="Enter password"') ?>
                </div>
                <div class="g-recaptcha" data-sitekey="<?= RECAPTCHA_SITE_KEY ?>"></div>
                <button type="submit" class="auth-btn">Login</button>
            </form>
            <div class="auth-links">
                <p><a href="<?= BASE_URL ?>admin/forgot-password">Forgot Password?</a></p>
                <p><a href="<?= BASE_URL ?>login">Member Login</a></p>
            </div>
        </div>
    </div>
</body>

</html>