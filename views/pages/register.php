<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../includes/guest_only.php';

$csrfToken = Security::generateCSRF();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
$registered_email = $_GET['registered_email'] ?? '';

// Preserve form data
$formData = [
    'username' => $_GET['username'] ?? '',
    'fullname' => $_GET['fullname'] ?? '',
    'email' => $_GET['email'] ?? '',
    'contact_number' => $_GET['contact_number'] ?? '',
    'birth_date' => $_GET['birth_date'] ?? '',
    'gender' => $_GET['gender'] ?? ''
];

// Set GLOBALS for HTML helpers
$GLOBALS['username'] = $formData['username'];
$GLOBALS['fullname'] = $formData['fullname'];
$GLOBALS['email'] = $formData['email'];
$GLOBALS['contact_number'] = $formData['contact_number'];
$GLOBALS['birth_date'] = $formData['birth_date'];
$GLOBALS['gender'] = $formData['gender'];

$pageTitle = 'Register';
?>
<?php include __DIR__ . '/../../includes/header.php'; ?>

<head>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/auth.css">
    <script src="<?= BASE_URL ?>assets/js/auth.js" defer></script>
</head>

<div class="auth-page">
    <div class="bg-grid"></div>
    <div class="auth-container">
        <div class="auth-header">
            <h1>REGISTER</h1>
            <p>Create your BlindeDoos account</p>
        </div>

        <form action="<?= BASE_URL ?>controllers/AuthController.php?action=register" method="POST" class="auth-form" autocomplete="off" id="register-form">
            <?php html_hidden('csrf_token', $csrfToken); ?>

            <div class="form-group">
                <label>Username</label>
                <?php html_text('username', 'placeholder="Enter username"'); ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                    <?php html_password('password', 'placeholder="Enter password"'); ?>
                <small>8+ chars with uppercase, lowercase, number, and special character</small>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <?php html_password('confirm_password', 'placeholder="Confirm password"'); ?>
            </div>

            <div class="form-group">
                <label>Full Name</label>
                <?php html_text('fullname', 'placeholder="Enter full name"'); ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <?php html_email('email', 'placeholder="Enter email"'); ?>
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

            <button type="submit" class="auth-btn">Register</button>
        </form>

        <div class="auth-links">
            <p>Already have an account? <a href="<?= BASE_URL . 'login' ?>">Login here</a></p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>