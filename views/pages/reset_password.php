<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../includes/guest_only.php';
require_once __DIR__ . '/../../lib/VerificationToken.php';

$token = $_GET['token'] ?? '';

if (!$token || !VerificationTokenLib::verifyPasswordResetToken($token)) {
    header('Location: ' . BASE_URL . 'forgot?error=' . urlencode('Invalid or expired reset token'));
    exit;
}

$pageTitle = 'Reset Password';
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
            <h1>RESET PASSWORD</h1>
            <p>Enter your new password</p>
        </div>

        <form method="POST" action="<?= BASE_URL ?>controllers/AuthController.php?action=reset_password" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= Security::generateCSRF() ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="password">New Password</label>
                <?php html_password('password', 'id="password" placeholder="Enter new password"'); ?>
                <small>8+ chars with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <?php html_password('confirm_password', 'id="confirm_password" placeholder="Confirm new password"'); ?>
            </div>

            <button type="submit" class="auth-btn">Reset Password</button>
        </form>
        <div class="auth-links">
            <p><a href="<?= BASE_URL . 'login' ?>">Back to Login</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>