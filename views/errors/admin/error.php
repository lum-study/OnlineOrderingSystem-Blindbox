<?php
/**
 * Generic Error Page (Admin Side)
 * Can be used for any error code by passing it as a parameter
 */
require_once __DIR__ . '/../../../config/init.php';

// Get error details from query parameters or defaults
$errorCode = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : 'ERROR';
$errorTitle = isset($_GET['title']) ? htmlspecialchars($_GET['title']) : 'SOMETHING WENT WRONG';
$errorMessage = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'An unexpected error occurred in the admin panel. Please try again or return to the dashboard.';

$pageTitle = $errorCode . ' - Error';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Panel</title>
    <link rel="stylesheet" href="/online_shopping_system/assets/css/fonts.css">
    <link rel="stylesheet" href="/online_shopping_system/assets/css/style.css">
    <link rel="stylesheet" href="/online_shopping_system/assets/css/error.css">
</head>
<body>
    <div class="error-container">
        <div class="error-content">
            <div class="error-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h1 class="error-code"><?= $errorCode ?></h1>
            <h2 class="error-title"><?= $errorTitle ?></h2>
            <p class="error-message">
                /// ADMIN ERROR DETECTED ///<br>
                <?= $errorMessage ?>
            </p>            <a href="/online_shopping_system/views/pages/admin/dashboard.php" class="error-btn">BACK TO DASHBOARD</a>
        </div>
    </div>
    <script src="/online_shopping_system/assets/js/theme.js"></script>
    <script src="/online_shopping_system/assets/js/toast.js"></script>
</body>
</html>
