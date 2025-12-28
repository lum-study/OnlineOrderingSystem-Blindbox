<?php

// AccountGuard: ensures blocked accounts are logged out and prevented from accessing the site
// Runs on every request (after session start and remember-me auto-login)

class AccountGuard
{
    public static function enforce()
    {
        // Skip CLI
        if (php_sapi_name() === 'cli') {
            return true;
        }

        // Helper to detect AJAX/JSON requests
        $isAjax = self::isAjaxRequest();

        // MEMBER: check if a member session exists and whether the account is blocked
        $memberId = Session::getMember('user_id') ?? Session::get('user_id');
        if ($memberId) {
            // Fetch lock info from user_logins so we can auto-clear expired temporary locks
            $row = Database::fetch("SELECT ud.is_blocked, ul.locked_until FROM user_data ud JOIN user_logins ul ON ud.user_id = ul.user_id WHERE ud.user_id = ? LIMIT 1", [$memberId]);
            if ($row && ($row['is_blocked'] ?? 0)) {
                // If lock is temporary and has expired, clear it and allow the session to continue
                if (!empty($row['locked_until'])) {
                    $lockedTs = strtotime($row['locked_until']);
                    if ($lockedTs !== false && $lockedTs <= time()) {
                        try {
                            require_once __DIR__ . '/../models/UserLogin.php';
                            require_once __DIR__ . '/../models/UserData.php';
                            $ul = new UserLogin();
                            $ul->resetFailedAttemptsAndLock($memberId);
                            $ud = new UserData();
                            $ud->setBlocked($memberId, 0);
                        } catch (Exception $e) {
                            // ignore failures to avoid blocking requests
                        }
                        return true;
                    }
                }
                // Log and Force logout and clear remember-me
                try {
                    // Log forced logout for auditing
                    try {
                        $al = new ActivityLog();
                        $al->create($memberId, null, 'forced_logout', 'User blocked and logged out', Security::getClientIP(), Security::getUserAgent());
                    } catch (Exception $e) {
                        // ignore logging errors
                    }

                    Auth::clearRememberMe($memberId);
                } catch (Exception $e) {
                    // ignore
                }

                Session::destroyMember();
                // Clear legacy session entries as well
                Session::set('user_id', null);
                Session::set('fullname', null);
                Session::set('role', null);                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Your account has been blocked. Please contact support.']);
                    exit;
                }

                require_once __DIR__ . '/../includes/error_handler.php';
                redirectToError(403, 'Account Blocked', 'Your account has been blocked. Please contact support.');
                exit;
            }
        }

        // STAFF/ADMIN: check if an admin session exists and whether the account is blocked
        $staffId = Session::getAdmin('staff_id') ?? Session::get('staff_id');
        if ($staffId) {
            // Fetch lock info from staff_logins so we can auto-clear expired temporary locks
            $row = Database::fetch("SELECT sd.is_blocked, sl.locked_until FROM staff_data sd JOIN staff_logins sl ON sd.staff_id = sl.staff_id WHERE sd.staff_id = ? LIMIT 1", [$staffId]);
            if ($row && ($row['is_blocked'] ?? 0)) {
                // If lock is temporary and has expired, clear it and allow the session to continue
                if (!empty($row['locked_until'])) {
                    $lockedTs = strtotime($row['locked_until']);
                    if ($lockedTs !== false && $lockedTs <= time()) {
                        try {
                            require_once __DIR__ . '/../models/StaffLogin.php';
                            require_once __DIR__ . '/../models/StaffData.php';
                            $sl = new StaffLogin();
                            $sl->resetFailedAttemptsAndLock($staffId);
                            $sd = new StaffData();
                            $sd->setBlocked($staffId, 0);
                        } catch (Exception $e) {
                            // ignore failures to avoid blocking requests
                        }
                        return true;
                    }
                }
                // Log and Force logout
                try {
                    try {
                        $al = new ActivityLog();
                        $al->create(null, $staffId, 'forced_logout', 'Admin/staff blocked and logged out', Security::getClientIP(), Security::getUserAgent());
                    } catch (Exception $e) {
                        // ignore logging errors
                    }
                } catch (Exception $e) {
                    // ignore
                }

                AdminAuth::logout();
                Session::set('staff_id', null);                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Admin account has been blocked. Please contact another administrator.']);
                    exit;
                }

                require_once __DIR__ . '/../includes/error_handler.php';
                redirectToAdminError(403, 'Account Blocked', 'Admin account has been blocked. Please contact another administrator.');
                exit;
            }
        }

        return true;
    }

    private static function isAjaxRequest()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        if (!empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            return true;
        }
        return false;
    }
}
