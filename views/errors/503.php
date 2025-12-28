<?php
/**
 * 503 Service Unavailable Error Page (User Side)
 */
require_once __DIR__ . '/../../config/init.php';

$pageTitle = '503 - Service Unavailable';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - BlindeDoos</title>
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
                    <polyline points="16 12 12 8 8 12"></polyline>
                    <line x1="12" y1="16" x2="12" y2="8"></line>
                </svg>
            </div>
            <h1 class="error-code">503</h1>
            <h2 class="error-title">SERVICE UNAVAILABLE</h2>
            <p class="error-message">
                /// MAINTENANCE MODE ///<br>
                The service is temporarily unavailable. We're performing maintenance
                or experiencing high traffic. Please check back soon.
            </p>            <a href="/online_shopping_system/" class="error-btn">BACK TO HOME</a>
        </div>
    </div>
    <script src="/online_shopping_system/assets/js/theme.js"></script>
    <script src="/online_shopping_system/assets/js/toast.js"></script>
</body>
</html>
