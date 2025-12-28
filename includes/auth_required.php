<?php
// Auth required guard - Redirect guests to login_required page
// Used for member-only features like cart, checkout, etc.
define('ACCESS_CONTROL_HANDLED', true);
require_once __DIR__ . '/error_handler.php';

if (!Auth::check()) {
    redirectToError(401);
}
