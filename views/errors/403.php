<?php
/**
 * 403 Forbidden Error Page (User Side)
 */
require_once __DIR__ . '/../../config/init.php';

$pageTitle = '403 - Access Denied';
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
                    <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                    <path d="M2 17l10 5 10-5"></path>
                    <path d="M2 12l10 5 10-5"></path>
                    <path d="M8 12v6"></path>
                    <path d="M16 12v6"></path>
                    <circle cx="12" cy="17" r="1"></circle>
                </svg>
            </div>
            <h1 class="error-code">403</h1>
            <h2 class="error-title">ACCESS DENIED</h2>
            <p class="error-message">
                /// RESTRICTED AREA ///<br>
                You don't have permission to access this resource.
                This section is off-limits. Return to familiar territory.
            </p>            <a href="/online_shopping_system/" class="error-btn">BACK TO HOME</a>
        </div>
    </div>
    <script src="/online_shopping_system/assets/js/theme.js"></script>
    <script src="/online_shopping_system/assets/js/toast.js"></script>
</body>
</html>
