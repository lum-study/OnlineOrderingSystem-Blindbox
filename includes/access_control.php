<?php

/**
 * Access Control - Centralized path restriction security
 * 
 * Access Levels:
 * - Guest: Can access pages/ and pages/products/ only
 * - Member: Can access pages/, pages/products/, and pages/member/
 * - Admin/Staff: Can access all pages including pages/admin/
 */

// Get the current script path relative to views/pages/
$currentPath = $_SERVER['SCRIPT_NAME'];

// Define access rules
class AccessControl
{

    // Paths accessible to everyone (including guests)
    private static $publicPaths = [
        '/views/pages/about.php',
        '/views/pages/contact.php',
        '/views/pages/faq.php',
        '/views/pages/first_login.php',
        '/views/pages/forgot_password.php',
        '/views/pages/home.php',
        '/views/pages/login_required.php',
        '/views/pages/login.php',
        '/login',
        '/views/pages/register.php',
        '/register',
        '/views/pages/reset_password.php',
        '/views/pages/verify_email.php',
    ];

    // Path prefixes accessible to guests
    private static $publicPrefixes = [
        '/views/pages/products/',
    ];

    // Path prefixes that require member login
    private static $memberPrefixes = [
        '/views/pages/member/',
        '/member/',
    ];

    // Path prefixes that require admin/staff login
    private static $adminPrefixes = [
        '/views/pages/admin/',
        '/admin/',
    ];

    /**
     * Check if current user has access to the requested path
     * @param string $path The current script path
     * @return bool|string Returns true if access granted, or redirect URL if denied
     */
    public static function checkAccess($path)
    {
        // Normalize path - remove base URL prefix if present
        $path = self::normalizePath($path);

        // Check if this is admin login page - always allow access
        if (strpos($path, '/views/pages/admin/login.php') !== false) {
            return true;
        }

        // Get user role - check both member and admin sessions
        $isMemberLoggedIn = Auth::check();
        $isAdminLoggedIn = AdminAuth::check();
        $memberRole = Session::getMember('role') ?? Session::get('role');
        $adminPosition = Session::getAdmin('position');

        // Treat any authenticated admin/session as admin for page access (allow any position stored in DB)
        $isAdmin = $isAdminLoggedIn;
        $isMember = $isMemberLoggedIn && ($memberRole === 'member');

        // Check if path is in admin area
        if (self::matchesPrefix($path, self::$adminPrefixes)) {
            if (!$isAdminLoggedIn) {
                return BASE_URL . 'admin/login?error=' . urlencode('Please login to continue');
            }
            if (!$isAdmin) {
                return BASE_URL . 'admin/login?error=' . urlencode('Access denied. Admin privileges required.');
            }
            return true;
        }
        // Check if path is in member area
        if (self::matchesPrefix($path, self::$memberPrefixes)) {
            if (!$isMemberLoggedIn) {
                return BASE_URL . 'views/errors/401.php';
            }
            // Only members can access member area (admins are redirected in profile.php)
            return true;
        }

        // Check if path is public
        if (self::isPublicPath($path)) {
            return true;
        }

        // Check if path matches public prefixes
        if (self::matchesPrefix($path, self::$publicPrefixes)) {
            return true;
        }

        // For any other paths in views/pages that aren't explicitly defined
        // Default: require member login
        if (strpos($path, '/views/pages/') !== false) {
            if (!$isMemberLoggedIn) {
                return BASE_URL . 'views/errors/401.php';
            }
        }

        return true;
    }

    /**
     * Normalize the path by removing query string and base URL
     */
    private static function normalizePath($path)
    {
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        // Remove base URL prefix (e.g., /online_shopping_system)
        $baseUrlPath = parse_url(BASE_URL, PHP_URL_PATH);
        if ($baseUrlPath && strpos($path, $baseUrlPath) === 0) {
            $path = substr($path, strlen($baseUrlPath) - 1); // Keep leading slash
        }

        return $path;
    }

    /**
     * Check if path is in the public paths list
     */
    private static function isPublicPath($path)
    {
        foreach (self::$publicPaths as $publicPath) {
            if (strpos($path, $publicPath) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if path matches any of the given prefixes
     */
    private static function matchesPrefix($path, $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if (strpos($path, $prefix) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Enforce access control - call this at the start of each page
     */
    public static function enforce()
    {
        $result = self::checkAccess($_SERVER['SCRIPT_NAME']);

        if ($result !== true) {
            header('Location: ' . $result);
            exit;
        }
    }
}

// Auto-enforce access control when this file is included
AccessControl::enforce();
