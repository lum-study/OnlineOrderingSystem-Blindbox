<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../includes/guest_only.php';

$pageTitle = 'Forgot Password';
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
                <h1>FORGOT PASSWORD</h1>
                <p>Reset your account password</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>controllers/AuthController.php?action=request_reset" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <?php html_email('email', 'id="email" placeholder="Enter your email"'); ?>
                </div>

                <button type="submit" class="auth-btn">Send Reset Link</button>
            </form>
              <div class="auth-links">
                <p><a href="<?= BASE_URL . 'login' ?>">Back to Login</a></p>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>