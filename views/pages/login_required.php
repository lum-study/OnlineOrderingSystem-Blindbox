<?php
// login_required.php
// Reusable page for features that require login
require_once __DIR__ . '/../../config/init.php';

// Get error message if any
$errorMessage = $_GET['error'] ?? '';
$defaultMessage = 'Please log in to access this feature. Create an account if you don\'t have one yet and start exploring our amazing blind box collection!';
$displayMessage = !empty($errorMessage) ? htmlspecialchars($errorMessage) : $defaultMessage;
$pageTitle = 'Login Required';
include __DIR__ . '/../../includes/header.php';
?>

<head>
    <style>
        .login-required-container {
            max-width: 500px;
            margin: 100px auto 3rem;
            padding: 3rem;
            text-align: center;
            background: var(--bg-color);
            border: 2px solid var(--border-color);
        }

        .login-required-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            fill: var(--text-color);
        }

        .login-required-container h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .login-required-container p {
            color: var(--accent-gray);
            margin-bottom: 2rem;
            line-height: 1.6;
            font-size: 0.875rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            border: 2px solid var(--text-color);
            background: var(--bg-color);
            color: var(--text-color);
            text-decoration: none;
            font-weight: 700;
            transition: 0.2s;
            font-size: 1rem;
            letter-spacing: 0.05em;
        }

        .btn:hover {
            background: var(--text-color);
            color: var(--bg-color);
        }
    </style>
</head>
<div class="container" style="padding: 100px 24px 40px;">
    <div class="login-required-container">
        <svg class="login-required-icon" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
        </svg>
        <h1>LOGIN REQUIRED</h1>
        <p><?= $displayMessage ?></p>
        <div class="btn-group">
            <a href="<?= BASE_URL . 'login' ?>" class="btn">LOGIN</a>
            <a href="<?= BASE_URL . 'register' ?>" class="btn">REGISTER</a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>