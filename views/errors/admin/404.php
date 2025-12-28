<?php
/**
 * 404 Not Found Error Page (Admin Side)
 */
require_once __DIR__ . '/../../../config/init.php';

$pageTitle = '404 - Page Not Found';
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
            <h1 class="error-code">404</h1>
            <h2 class="error-title">PAGE NOT FOUND</h2>
            <p class="error-message">
                /// ADMIN TRANSMISSION LOST ///<br>
                The admin page you're looking for doesn't exist.
                It might have been moved, deleted, or the URL is incorrect.
            </p>            <a href="/online_shopping_system/views/pages/admin/dashboard.php" class="error-btn">BACK TO DASHBOARD</a>
        </div>
    </div>
    <script src="/online_shopping_system/assets/js/theme.js"></script>
    <script src="/online_shopping_system/assets/js/toast.js"></script>
</body>
</html>
