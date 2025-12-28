<?php
$pageTitle = 'Set Your Password';
$csrfToken = Security::generateCSRF();
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
            <h1>Set Your Password</h1>
            <p>This is your first time logging in. Please set a secure password for your account.</p>
        </div>

        <form id="first-login-form" class="auth-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label for="password" class="form-label">Password <span class="required">*</span></label>
                <?php html_password('password', 'placeholder="Enter a strong password"') ?>
                <small class="form-hint">Must be 8+ characters with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password <span class="required">*</span></label>
                <?php html_password('confirm_password', 'placeholder="Confirm your password"') ?>
                <small class="form-hint">Please re-enter the same password as above</small>
            </div>

            <button type="submit" class="auth-btn">Set Password</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('first-login-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (!password || !confirmPassword) {
            showToast('All fields are required', 'error');
            return;
        }

        if (password !== confirmPassword) {
            showToast('Passwords do not match', 'error');
            return;
        }

        const passwordError = validatePasswordStrength(password);
        if (passwordError) {
            showToast(passwordError, 'error');
            return;
        }

        const formData = new FormData(this);

        fetch('<?= BASE_URL ?>first-login/reset-password', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred', 'error');
            });
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>