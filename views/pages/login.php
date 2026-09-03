<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../includes/guest_only.php';

$csrfToken = Security::generateCSRF();

$pageTitle = 'Login';
include __DIR__ . '/../../includes/header.php';
?>

<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
    <script src="<?= BASE_URL ?>assets/js/auth.js" defer></script>
</head>
<div class="auth-page">
    <div class="bg-grid"></div>
    <div class="auth-container">
        <div class="auth-header">
            <h1>LOGIN</h1>
            <p>Welcome back to BlindeDoos</p>
        </div>
        <form action="<?= BASE_URL ?>controllers/AuthController.php?action=login" method="POST" class="auth-form" autocomplete="off">
            <?php html_hidden('csrf_token', $csrfToken); ?>            <div class="form-group">
                <label>Username or Email</label>
                <?php html_text('username', 'placeholder="Enter username or email"'); ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                <?php html_password('password', 'placeholder="Enter password"'); ?>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal; text-transform: none;">
                    <?php html_checkbox('remember_me', '1'); ?> Remember me for 30 days
                </label>
            </div>

            <button type="submit" class="auth-btn">Login</button>
        </form>

        <div class="auth-links">
            <p>Don't have an account? <a href="<?= BASE_URL . 'register' ?>">Register here</a></p>
            <p><a href="<?= BASE_URL . 'forgot' ?>">Forgot Password?</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>