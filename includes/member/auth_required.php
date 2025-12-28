<?php
// Member authentication guard - redirects to member login if not authenticated
require_once __DIR__ . '/../../lib/Auth.php';
require_once __DIR__ . '/../../includes/error_handler.php';

if (!Auth::check()) {
    redirectToError(401);
}

// Update member activity
Session::updateMemberActivity();
