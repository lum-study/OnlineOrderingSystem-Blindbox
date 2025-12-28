<?php
// Admin-only guard - requires admin position
require_once __DIR__ . '/../../lib/AdminAuth.php';

if (!AdminAuth::check() || !AdminAuth::isAdmin()) {
    header('Location: ' . BASE_URL . 'views/pages/admin/dashboard.php?error=insufficient_permissions');
    exit;
}

// Update admin activity
Session::updateAdminActivity();
