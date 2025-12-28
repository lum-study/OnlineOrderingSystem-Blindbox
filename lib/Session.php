<?php
class Session {
    private static $memberTimeout = 1800; // 30 minutes
    private static $adminTimeout = 7200;  // 2 hours

    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            self::checkTimeouts();
        }
    }

    public static function regenerate() {
        session_regenerate_id(true);
    }

    // ========================================================================
    // MEMBER SESSION METHODS
    // ========================================================================
    
    public static function isMemberLoggedIn() {
        return isset($_SESSION['member']);
    }

    public static function getMember($key = null) {
        if ($key === null) {
            return $_SESSION['member'] ?? null;
        }
        return $_SESSION['member'][$key] ?? null;
    }

    public static function setMember($key, $value) {
        if (!isset($_SESSION['member'])) {
            $_SESSION['member'] = [];
        }
        $_SESSION['member'][$key] = $value;
    }

    public static function updateMemberActivity() {
        if (isset($_SESSION['member'])) {
            $_SESSION['member']['last_activity'] = time();
        }
    }

    public static function destroyMember() {
        unset($_SESSION['member']);
    }

    // ========================================================================
    // ADMIN SESSION METHODS
    // ========================================================================
    
    public static function isAdminLoggedIn() {
        return isset($_SESSION['admin']);
    }

    public static function getAdmin($key = null) {
        if ($key === null) {
            return $_SESSION['admin'] ?? null;
        }
        return $_SESSION['admin'][$key] ?? null;
    }

    public static function setAdmin($key, $value) {
        if (!isset($_SESSION['admin'])) {
            $_SESSION['admin'] = [];
        }
        $_SESSION['admin'][$key] = $value;
    }

    public static function updateAdminActivity() {
        if (isset($_SESSION['admin'])) {
            $_SESSION['admin']['last_activity'] = time();
        }
    }

    public static function destroyAdmin() {
        unset($_SESSION['admin']);
    }

    // ========================================================================
    // LEGACY METHODS (for backward compatibility during migration)
    // ========================================================================
    
    public static function isLoggedIn() {
        // Check old session format OR new format
        return isset($_SESSION['user_id']) || isset($_SESSION['staff_id']) || 
               self::isMemberLoggedIn() || self::isAdminLoggedIn();
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function updateActivity() {
        $_SESSION['last_activity'] = time();
        self::updateMemberActivity();
        self::updateAdminActivity();
    }

    // ========================================================================
    // TIMEOUT CHECKING
    // ========================================================================
    
    private static function checkTimeouts() {
        // Check member timeout
        if (isset($_SESSION['member']['last_activity'])) {
            $elapsed = time() - $_SESSION['member']['last_activity'];
            if ($elapsed > self::$memberTimeout) {
                self::destroyMember();
            } else {
                self::updateMemberActivity();
            }
        }

        // Check admin timeout
        if (isset($_SESSION['admin']['last_activity'])) {
            $elapsed = time() - $_SESSION['admin']['last_activity'];
            if ($elapsed > self::$adminTimeout) {
                self::destroyAdmin();
            } else {
                self::updateAdminActivity();
            }
        }

        // Legacy timeout check
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > self::$memberTimeout)) {
            self::destroy();
        } else {
            self::updateActivity();
        }
    }

    // ========================================================================
    // DESTROY METHODS
    // ========================================================================
    
    public static function destroy() {
        $_SESSION = [];
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        session_destroy();
    }

    public static function destroyAll() {
        self::destroy();
    }
}
