<?php
$pageTitle = 'Set Your Password';
$csrfToken = Security::generateCSRF();
?>

<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/fonts.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/admin.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/orders.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/profile.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/toast.css">
    <script src="<?= BASE_URL ?>assets/js/theme.js"></script>
    <script src="<?= BASE_URL ?>assets/js/toast.js" defer></script>
    <script src="<?= BASE_URL ?>assets/js/admin_auth.js"></script>
    <script src="<?= BASE_URL ?>assets/js/input.js"></script>
</head>
<div style="max-width: 600px; margin: 3rem auto; padding: 0 1rem;">
    <div class="admin-card" style="padding: 2rem;">
        <h1 style="font-size: 2rem; margin-bottom: 0.5rem;">Welcome to Admin Portal!</h1>
        <p style="color: #6b7280; margin-bottom: 2rem;">This is your first time logging in. Please set a secure password for your account.</p>

        <form id="admin-first-login-form" class="profile-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label for="password" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Password <span class="required">*</span></label>
                <?php $GLOBALS['password'] = ''; // Validation handled by JS/server; remove native 'required' attribute
                html_password('password', 'style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; font-size: 1rem;" placeholder="Enter a strong password"'); ?>
                <small style="color: #6b7280; display: block; margin-top: 0.5rem;">Must be 8+ characters with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label for="confirm_password" style="font-weight: 600; margin-bottom: 0.5rem; display: block;">Confirm Password <span class="required">*</span></label>
                <?php $GLOBALS['confirm_password'] = ''; html_password('confirm_password', 'style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; font-size: 1rem;" placeholder="Confirm your password"'); ?>
            </div>

            <button type="submit" class="user-btn" style="width: 100%; margin-top: 1.5rem;">Set Password</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('admin-first-login-form').addEventListener('submit', function(e) {
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

        const formData = new FormData(this);

        fetch('<?= BASE_URL ?>admin/first-login/reset-password', {
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