<?php
require_once __DIR__ . '/../models/StaffLogin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/StaffData.php';

class AdminAuth
{
    /**
     * Attempt admin/staff login
     */
    public static function attempt($usernameOrEmail, $password)
    {
        $staffLoginModel = new StaffLogin();
        $staff = $staffLoginModel->findByUsernameOrEmail($usernameOrEmail);

        if (!$staff) {
            return ['success' => false, 'message' => 'Invalid Username or Email. Please Try Again.'];
        }

        // Atomically clear expired DB-side lock if present, then reload
        try {
            $staffLoginModel->forceClearLockIfExpired($staff['staff_id']);
            $staff = $staffLoginModel->findByUsernameOrEmail($usernameOrEmail);
        } catch (Exception $e) {
            // ignore
        }

        // Check temporary lock after attempted clear
        if (!empty($staff['locked_until'])) {
            return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.', 'locked_until' => $staff['locked_until']];
        }

        if ($staff['is_blocked']) {
            return ['success' => false, 'message' => 'Account is blocked.'];
        }

        if (is_null($staff['last_login'])) {

            // Set temporary first-login session with security token
            $securityToken = bin2hex(random_bytes(32));
            $_SESSION['first_login_pending'] = [
                'staff_id' => $staff['staff_id'],
                'token' => $securityToken,
                'timestamp' => time(),
                'ip' => Security::getClientIP()
            ];
            return ['success' => true, 'first_login' => true, 'security_token' => $securityToken];
        }

        if (!Security::verifyPassword($password, $staff['password'])) {
            // Wrong password - increment failed attempts
            $failed = $staffLoginModel->incrementFailedAttempts($staff['staff_id']);
            $attemptsLeft = max(0, MAX_FAILED_ATTEMPTS - $failed);

            // Log failed attempt
            try {
                $activity = new ActivityLog();
                $activity->create(null, $staff['staff_id'], 'failed_login', "Failed login attempt (attempt {$failed})", Security::getClientIP(), Security::getUserAgent());
            } catch (Exception $e) {
            }

            if ($failed >= MAX_FAILED_ATTEMPTS) {
                // Lock account temporarily and set is_blocked so it appears blocked
                $staffLoginModel->lockForMinutes($staff['staff_id'], LOCKOUT_DURATION_MINUTES);
                $staffDataModel = new StaffData();
                $staffDataModel->setBlocked($staff['staff_id'], 1);

                try {
                    $activity = new ActivityLog();
                    $activity->create(null, $staff['staff_id'], 'account_locked', "Account locked due to repeated failed logins", Security::getClientIP(), Security::getUserAgent());
                } catch (Exception $e) {
                }

                $row = $staffLoginModel->findByStaffId($staff['staff_id']);
                $lockedUntil = $row ? $row->getLockedUntil() : null;

                return ['success' => false, 'message' => 'Account temporarily locked due to repeated failed login attempts.', 'locked_until' => $lockedUntil];
            }

            return ['success' => false, 'message' => 'Invalid Username or Password. Please Try Again.', 'attempts_left' => $attemptsLeft];
        }

        // Successful login - reset failed attempts and lock
        $staffLoginModel->resetFailedAttemptsAndLock($staff['staff_id']);

        $staffLoginModel->updateLastLogin($staff['staff_id']);
        self::createSession($staff['staff_id'], $staff['position'], $staff);
        self::logActivity($staff['staff_id'], 'login', 'Staff logged in');
        return ['success' => true, 'role' => $staff['position']];
    }

    /**
     * Create admin session
     */
    public static function createSession($staffId, $position, $data)
    {
        // Regenerate session ID for security
        Session::regenerate();

        // ALWAYS store position in lowercase for consistent comparison
        $_SESSION['admin'] = [
            'staff_id' => $staffId,
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'position' => strtolower(trim($position)), // Normalize to lowercase
            'last_activity' => time()
        ];

        // Debug log to verify session is created
        error_log("AdminAuth::createSession - Session created for staff_id: " . $staffId);
        error_log("AdminAuth::createSession - Position stored: " . strtolower(trim($position)));
        error_log("AdminAuth::createSession - Session admin data: " . json_encode($_SESSION['admin']));
    }

    /**
     * Logout admin
     */
    public static function logout()
    {
        $staffId = Session::getAdmin('staff_id');

        if ($staffId) {
            $exists = Database::fetch("SELECT staff_id FROM staff_data WHERE staff_id = ?", [$staffId]);
            if ($exists) {
                self::logActivity($staffId, 'logout', 'Staff logged out');
            }
        }

        Session::destroyAdmin();
        Session::destroy();
    }

    /**
     * Check if admin is logged in
     */
    public static function check()
    {
        return Session::isAdminLoggedIn();
    }

    /**
     * Check if user has admin position
     */
    public static function isAdmin()
    {
        return Session::getAdmin('position') === 'admin';
    }

    /**
     * Check if user has manager position
     */
    public static function isManager()
    {
        return Session::getAdmin('position') === 'manager';
    }

    /**
     * Get admin user data
     */
    public static function user()
    {
        return Session::getAdmin();
    }

    /**
     * Get specific admin user property
     */
    public static function get($key, $default = null)
    {
        return Session::getAdmin($key) ?? $default;
    }
    /**
     * Log admin activity
     */
    private static function logActivity($staffId, $action, $description)
    {
        try {
            $activityLogModel = new ActivityLog();
            $activityLogModel->create(null, $staffId, $action, $description, Security::getClientIP(), Security::getUserAgent());
        } catch (Exception $e) {
            error_log("Failed to log admin activity: " . $e->getMessage());
        }
    }
}
