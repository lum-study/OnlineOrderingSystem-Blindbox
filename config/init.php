<?php
// init.php
// Session start, error reporting, includes for config and autoloading.

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/config.php';

// Set default timezone
date_default_timezone_set('UTC');

// Basic security headers (can be extended)
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Autoloader for lib and controllers
spl_autoload_register(function ($class) {
    $paths = [__DIR__ . '/../lib/', __DIR__ . '/../controllers/'];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include common functions
if (file_exists(__DIR__ . '/../includes/functions.php')) {
    require_once __DIR__ . '/../includes/functions.php';
}

// Include HTML helpers
require_once __DIR__ . '/../lib/HtmlHelpers.php';

// Auto-login via remember me cookie
if (!Auth::check() && isset($_COOKIE['remember_me'])) {
    Auth::attemptRememberMe();
}

// Enforce account blocking guard early so blocked accounts are logged out on every request
if (class_exists('AccountGuard') || file_exists(__DIR__ . '/../lib/AccountGuard.php')) {
    AccountGuard::enforce();
}

// Access Control - Enforce path restrictions based on user role
// Only enforce for view pages (not for API/controller requests)
// Skip if a specific guard has already been included
if (!defined('ACCESS_CONTROL_HANDLED')) {
    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($scriptPath, '/views/pages/') !== false) {
        require_once __DIR__ . '/../includes/access_control.php';
    }
}

// Initialize form data globals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $GLOBALS[$key] = $value;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    foreach ($_GET as $key => $value) {
        $GLOBALS[$key] = $value;
    }
}
