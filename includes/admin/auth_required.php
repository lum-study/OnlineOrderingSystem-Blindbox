<?php
// Admin authentication guard - redirects to admin login if not authenticated
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../includes/error_handler.php';

if (!AdminAuth::check()) {
    redirectToAdminError(401);
}

// Update admin activity
Session::updateAdminActivity();
