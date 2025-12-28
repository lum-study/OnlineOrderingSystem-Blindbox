<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/IDGenerator.php';
require_once __DIR__ . '/../entity/UserLogin.php';

class UserLogin extends Database
{
    public function findByUsername($username)
    {
        $sql = "SELECT ul.*, ud.fullname, ud.is_blocked, ud.email 
                FROM user_logins ul 
                JOIN user_data ud ON ul.user_id = ud.user_id 
                WHERE ul.username = ? AND ul.is_deleted = 0 AND ud.is_deleted = 0";
        return $this->query($sql, [$username])->fetch();
    }

    public function findByUsernameOrEmail($usernameOrEmail)
    {
        $sql = "SELECT ul.*, ud.fullname, ud.is_blocked, ud.email 
                FROM user_logins ul 
                JOIN user_data ud ON ul.user_id = ud.user_id 
                WHERE (ul.username = ? OR ud.email = ?) AND ul.is_deleted = 0 AND ud.is_deleted = 0";
        return $this->query($sql, [$usernameOrEmail, $usernameOrEmail])->fetch();
    }

    public function findByUserId($user_id)
    {
        $sql = "SELECT * FROM user_logins WHERE user_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? new UserLoginEntity($row) : null;
    }
    public function usernameExists($username, $excludeUserId = null)
    {
        if ($excludeUserId) {
            $sql = "SELECT username FROM user_logins WHERE username = ? AND user_id != ? AND is_deleted = 0";
            return $this->query($sql, [$username, $excludeUserId])->fetch() !== false;
        }
        // Check only ACTIVE usernames to allow reusing deleted usernames
        $sql = "SELECT username FROM user_logins WHERE username = ? AND is_deleted = 0";
        return $this->query($sql, [$username])->fetch() !== false;
    }
    public function usernameExistsCaseInsensitive($username, $excludeUserId = null)
    {
        if ($excludeUserId) {
            // Check only ACTIVE usernames (case-insensitive) to allow reusing deleted usernames
            $sql = "SELECT username FROM user_logins WHERE LOWER(username) = LOWER(?) AND user_id != ? AND is_deleted = 0";
            return $this->query($sql, [$username, $excludeUserId])->fetch() !== false;
        }
        // Check only ACTIVE usernames (case-insensitive) to allow reusing deleted usernames
        $sql = "SELECT username FROM user_logins WHERE LOWER(username) = LOWER(?) AND is_deleted = 0";
        return $this->query($sql, [$username])->fetch() !== false;
    }

    public function create($login_id, $user_id, $username, $password, $setLastLogin = false)
    {
        if ($setLastLogin) {
            $sql = "INSERT INTO user_logins (login_id, user_id, username, password, last_login) VALUES (?, ?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO user_logins (login_id, user_id, username, password) VALUES (?, ?, ?, ?)";
        }
        $this->query($sql, [$login_id, $user_id, $username, $password]);
    }

    public function createByAdmin($user_id, $username, $password, $setLastLogin = false)
    {
        $login_id = IDGenerator::userLoginID();
        if ($setLastLogin) {
            $sql = "INSERT INTO user_logins (login_id, user_id, username, password, last_login) VALUES (?, ?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO user_logins (login_id, user_id, username, password) VALUES (?, ?, ?, ?)";
        }
        $this->query($sql, [$login_id, $user_id, $username, $password]);
        return $login_id;
    }

    public function updateLastLogin($user_id)
    {
        $sql = "UPDATE user_logins SET last_login = NOW() WHERE user_id = ?";
        $this->query($sql, [$user_id]);
    }

    public function updateUsername($user_id, $username)
    {
        $sql = "UPDATE user_logins SET username = ?, username_changed_at = NOW() WHERE user_id = ?";
        $this->query($sql, [$username, $user_id]);
    }

    public function updatePassword($user_id, $password)
    {
        $sql = "UPDATE user_logins SET password = ? WHERE user_id = ?";
        $this->query($sql, [$password, $user_id]);
    }

    public function getPassword($user_id)
    {
        $sql = "SELECT password FROM user_logins WHERE user_id = ?";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? $row['password'] : null;
    }

    public function getUsernameChangedAt($user_id)
    {
        $sql = "SELECT username_changed_at FROM user_logins WHERE user_id = ?";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? $row['username_changed_at'] : null;
    }

    public function getUsername($user_id)
    {
        $sql = "SELECT username FROM user_logins WHERE user_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? $row['username'] : null;
    }

    public function updateRememberToken($user_id, $token)
    {
        $sql = "UPDATE user_logins SET remember_token = ? WHERE user_id = ?";
        $this->query($sql, [$token, $user_id]);
    }

    public function findByRememberToken($token)
    {
        $sql = "SELECT ul.*, ud.fullname, ud.is_blocked, ud.email 
                FROM user_logins ul 
                JOIN user_data ud ON ul.user_id = ud.user_id 
                WHERE ul.remember_token = ? AND ul.is_deleted = 0 AND ud.is_deleted = 0";
        return $this->query($sql, [$token])->fetch();
    }
    public function setDeleted($user_id, $is_deleted)
    {
        $sql = "UPDATE user_logins SET is_deleted = ? WHERE user_id = ?";
        $this->query($sql, [$is_deleted, $user_id]);
    }

    public function incrementFailedAttempts($user_id)
    {
        $sql = "UPDATE user_logins SET failed_attempts = failed_attempts + 1 WHERE user_id = ?";
        $this->query($sql, [$user_id]);
        $row = $this->query("SELECT failed_attempts FROM user_logins WHERE user_id = ?", [$user_id])->fetch();
        return $row ? (int)$row['failed_attempts'] : 0;
    }

    public function getFailedAttempts($user_id)
    {
        $sql = "SELECT failed_attempts FROM user_logins WHERE user_id = ?";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? (int)$row['failed_attempts'] : 0;
    }

    public function lockForMinutes($user_id, $minutes)
    {
        $sql = "UPDATE user_logins SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE user_id = ?";
        $this->query($sql, [$minutes, $user_id]);
    }

    public function resetFailedAttemptsAndLock($user_id)
    {
        $sql = "UPDATE user_logins SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?";
        $this->query($sql, [$user_id]);
    }

    public function clearExpiredLock($user_id)
    {
        // Use UNIX_TIMESTAMP to get DB-interpreted epoch seconds and compare with PHP time()
        $row = $this->query("SELECT UNIX_TIMESTAMP(locked_until) AS locked_unix FROM user_logins WHERE user_id = ? AND locked_until IS NOT NULL LIMIT 1", [$user_id])->fetch();
        if ($row && isset($row['locked_unix']) && $row['locked_unix'] !== null && intval($row['locked_unix']) <= time()) {
            $sql = "UPDATE user_logins SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?";
            $this->query($sql, [$user_id]);
            // Also clear blocked flag in user_data
            require_once __DIR__ . '/UserData.php';
            $ud = new UserData();
            $ud->setBlocked($user_id, 0);
            return true;
        }
        return false;
    }

    public function forceClearLockIfExpired($user_id)
    {
        $sql = "UPDATE user_logins ul JOIN user_data ud ON ul.user_id = ud.user_id
                SET ul.failed_attempts = 0, ul.locked_until = NULL, ud.is_blocked = 0
                WHERE ul.user_id = ? AND ul.locked_until IS NOT NULL AND ul.locked_until <= NOW()";
        $this->query($sql, [$user_id]);
    }

    public function forceClearLockImmediate($user_id)
    {
        $sql = "UPDATE user_logins ul JOIN user_data ud ON ul.user_id = ud.user_id
                SET ul.failed_attempts = 0, ul.locked_until = NULL, ud.is_blocked = 0
                WHERE ul.user_id = ?";
        $this->query($sql, [$user_id]);
    }
}
