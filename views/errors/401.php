<?php
/**
 * 401 Unauthorized — show user-friendly login required page
 */
require_once __DIR__ . '/../../config/init.php';

// Keep the HTTP status as 401
http_response_code(401);

// Provide a user-friendly message if not supplied
if (!isset($_GET['error'])) {
    $_GET['error'] = 'You need to be logged in to access this resource. Please sign in or create an account to continue.';
}

// Reuse the existing login-required page (includes header and footer)
include __DIR__ . '/../pages/login_required.php';
exit;
?>
