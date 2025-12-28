<?php
// Admin guard - Only allow admin, manager, and staff roles
define('ACCESS_CONTROL_HANDLED', true);
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../lib/AdminAuth.php';
require_once __DIR__ . '/error_handler.php';

// Check if admin is logged in using new dual-session system
if (!AdminAuth::check()) {
    redirectToAdminError(401);
}

// Get admin position from session
$position = Session::getAdmin('position');

// If position is null or empty, try legacy session format as fallback
if (empty($position)) {
    $position = Session::get('position');
}

// Check if position is valid
if (empty($position)) {
    // Clear the invalid session and redirect
    Session::destroyAdmin();
    Session::destroy();
    redirectToAdminError(403);
}

// Update activity timestamp for admin session
Session::updateAdminActivity();
