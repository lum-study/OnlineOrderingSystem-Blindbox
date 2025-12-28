<?php
require_once __DIR__ . '/../models/UserLogin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/UserData.php';

class Auth
{
    /**
     * Attempt member login (members only)
     */
    public static function attempt($usernameOrEmail, $password)
    {
        $userLoginModel = new UserLogin();
        $user = $userLoginModel->findByUsernameOrEmail($usernameOrEmail);

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid Username or Email. Please Try Again.'];
        }

        // Clear expired database-side lock if it exists (atomic DB-side operation)
        try {
            $userLoginModel->forceClearLockIfExpired($user['user_id']);
            // reload fresh data after clear
            $user = $userLoginModel->findByUsernameOrEmail($usernameOrEmail);
        } catch (Exception $e) {
            // ignore
        }

        // Check temporary lock (locked_until) after attempting to clear expired locks
        if (!empty($user['locked_until'])) {
            // If locked_until already passed according to PHP time, clear it as a fallback
            $lockedTs = @strtotime($user['locked_until']);
            if ($lockedTs !== false && $lockedTs <= time()) {
                try {
                    $userLoginModel->resetFailedAttemptsAndLock($user['user_id']);
                    $userDataModel = new UserData();
                    $userDataModel->setBlocked($user['user_id'], 0);
                    $user['locked_until'] = null;
                    $user['failed_attempts'] = 0;
                    $user['is_blocked'] = 0;
                } catch (Exception $e) {
                    // ignore
                }
            } else {
                return ['success' => false, 'message' => 'Account temporarily locked. Please try again later.', 'locked_until' => $user['locked_until']];
            }
        }

        if ($user['is_blocked']) {
            return ['success' => false, 'message' => 'Account is blocked.'];
        }

        if (!$user['email_verified']) {
            return ['success' => false, 'message' => 'Please verify your email before logging in.', 'unverified_email' => $user['email']];
        }

        if (is_null($user['last_login'])) {
            // Set temporary first-login session with security token
            $securityToken = bin2hex(random_bytes(32));
            $_SESSION['first_login_pending'] = [
                'user_id' => $user['user_id'],
                'token' => $securityToken,
                'timestamp' => time(),
                'ip' => Security::getClientIP()
            ];
            return ['success' => true, 'first_login' => true, 'security_token' => $securityToken];
        }

        if (!Security::verifyPassword($password, $user['password'])) {
            // Wrong password - increment failed attempts
            $failed = $userLoginModel->incrementFailedAttempts($user['user_id']);
            $attemptsLeft = max(0, MAX_FAILED_ATTEMPTS - $failed);

            // Log failed attempt
            try {
                $activity = new ActivityLog();
                $activity->create($user['user_id'], null, 'failed_login', "Failed login attempt (attempt {$failed})", Security::getClientIP(), Security::getUserAgent());
            } catch (Exception $e) {}

            if ($failed >= MAX_FAILED_ATTEMPTS) {
                // Lock account temporarily and set is_blocked so admin UI shows blocked state
                $userLoginModel->lockForMinutes($user['user_id'], LOCKOUT_DURATION_MINUTES);
                $userDataModel = new UserData();
                $userDataModel->setBlocked($user['user_id'], 1);

                try {
                    $activity = new ActivityLog();
                    $activity->create($user['user_id'], null, 'account_locked', "Account locked due to repeated failed logins", Security::getClientIP(), Security::getUserAgent());
                } catch (Exception $e) {}

                $row = $userLoginModel->findByUserId($user['user_id']);
                $lockedUntil = $row ? $row->getLockedUntil() : null;

                return ['success' => false, 'message' => 'Account temporarily locked due to repeated failed login attempts.', 'locked_until' => $lockedUntil];
            }

            return ['success' => false, 'message' => 'Invalid Username or Password. Please Try Again.', 'attempts_left' => $attemptsLeft];
        }

        // Successful login - reset failed attempts and lock
        $userLoginModel->resetFailedAttemptsAndLock($user['user_id']);

        // Successful login, update last_login
        $userLoginModel->updateLastLogin($user['user_id']);
        self::createSession($user['user_id'], $user);
        self::logActivity($user['user_id'], 'login', 'User logged in');
        return ['success' => true, 'role' => 'member'];
    }

    /**
     * Create member session
     */
    public static function createSession($userId, $data)
    {
        Session::regenerate();

        $_SESSION['member'] = [
            'user_id' => $userId,
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'email' => $data['email'],
            'role' => 'member',
            'last_activity' => time()
        ];

        // Keep legacy session for backward compatibility during migration
        Session::set('user_id', $userId);
        Session::set('fullname', $data['fullname']);
        Session::set('role', 'member');
        Session::updateActivity();

        // Load user's saved preferences into session (theme, etc.)
        try {
            require_once __DIR__ . '/../models/UserData.php';
            $ud = new UserData();
            $prefs = $ud->getPreferencesDecoded($userId);
            $theme = is_array($prefs) && isset($prefs['theme']) ? $prefs['theme'] : null;
            Session::setMember('theme', $theme);
        } catch (Exception $e) {
            Session::setMember('theme', null);
        }
    }

    /**
     * Logout member
     */
    public static function logout()
    {
        $userId = Session::getMember('user_id') ?? Session::get('user_id');

        if ($userId) {
            $exists = Database::fetch("SELECT user_id FROM user_data WHERE user_id = ?", [$userId]);
            if ($exists) {
                self::logActivity($userId, 'logout', 'User logged out');
                self::clearRememberMe($userId);
            }
        }

        Session::destroyMember();

        // Also clear legacy session
        Session::destroy();

        // Clear theme preference keys for safety on logout
        echo "<script>try{localStorage.removeItem('user-theme');localStorage.removeItem('admin-theme');localStorage.removeItem('savedTheme');}catch(e){};</script>";
    }

    /**
     * Check if member is logged in
     */
    public static function check()
    {
        return Session::isMemberLoggedIn() || isset($_SESSION['user_id']);
    }

    /**
     * Check if user is a member
     */
    public static function isMember()
    {
        return self::check();
    }

    /**
     * Get member user data
     */
    public static function user()
    {
        return Session::getMember() ?? [
            'user_id' => Session::get('user_id'),
            'fullname' => Session::get('fullname'),
            'role' => Session::get('role')
        ];
    }

    /**
     * Get specific member property
     */
    public static function get($key, $default = null)
    {
        return Session::getMember($key) ?? Session::get($key, $default);
    }

    /**
     * Set remember me cookie
     */
    public static function setRememberMe($userId)
    {
        $token = bin2hex(random_bytes(32));
        $userLoginModel = new UserLogin();
        $userLoginModel->updateRememberToken($userId, $token);
        setcookie('remember_me', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
    }

    /**
     * Attempt remember me login
     */
    public static function attemptRememberMe()
    {
        if (!isset($_COOKIE['remember_me'])) {
            return false;
        }

        $token = $_COOKIE['remember_me'];
        $userLoginModel = new UserLogin();
        $user = $userLoginModel->findByRememberToken($token);

        if ($user) {
            // If user has a temporary lock, clear it if expired
            if (!empty($user['locked_until'])) {
                $lockedTs = strtotime($user['locked_until']);
                if ($lockedTs > time()) {
                    // still locked, drop cookie
                    setcookie('remember_me', '', time() - 3600, '/');
                    return false;
                }

                // lock expired - reset failed attempts & unblock
                try {
                    $userLoginModel->resetFailedAttemptsAndLock($user['user_id']);
                    $userDataModel = new UserData();
                    $userDataModel->setBlocked($user['user_id'], 0);
                    $user['locked_until'] = null;
                    $user['failed_attempts'] = 0;
                    $user['is_blocked'] = 0;
                } catch (Exception $e) {
                    // ignore
                }
            }

            if (!$user['is_blocked']) {
                self::createSession($user['user_id'], $user);
                self::logActivity($user['user_id'], 'login', 'User logged in via remember me');
                return true;
            }
        }

        setcookie('remember_me', '', time() - 3600, '/');
        return false;
    }

    /**
     * Clear remember me token
     */
    public static function clearRememberMe($userId)
    {
        $userLoginModel = new UserLogin();
        $userLoginModel->updateRememberToken($userId, null);
        setcookie('remember_me', '', time() - 3600, '/');
    }

    /**
     * Log member activity
     */
    private static function logActivity($userId, $action, $description)
    {
        try {
            $activityLogModel = new ActivityLog();
            $activityLogModel->create($userId, null, $action, $description, Security::getClientIP(), Security::getUserAgent());
        } catch (Exception $e) {
            error_log("Failed to log member activity: " . $e->getMessage());
        }
    }
}
