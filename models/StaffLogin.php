<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../lib/IDGenerator.php';
require_once __DIR__ . '/../entity/StaffLogin.php';

class StaffLogin extends Database
{
    public function findById($staff_id)
    {
        $sql = "SELECT * FROM staff_logins WHERE staff_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$staff_id])->fetch();
        return $row ? new StaffLoginEntity($row) : null;
    }

    public function findByUsername($username)
    {
        $sql = "SELECT sl.*, sd.fullname, sd.email 
                FROM staff_logins sl 
                JOIN staff_data sd ON sl.staff_id = sd.staff_id 
                WHERE sl.username = ? AND sl.is_deleted = 0 AND sd.is_deleted = 0";
        return $this->query($sql, [$username])->fetch();
    }    public function findByUsernameOrEmail($usernameOrEmail)
    {
        $sql = "SELECT sl.*, sd.fullname, sd.is_blocked, sd.email 
                FROM staff_logins sl
                JOIN staff_data sd ON sl.staff_id = sd.staff_id
                WHERE (sl.username = ? OR sd.email = ?) AND sl.is_deleted = 0 AND sd.is_deleted = 0";
        return $this->query($sql, [$usernameOrEmail, $usernameOrEmail])->fetch();
    }

    public function findByStaffId($staff_id)
    {
        $sql = "SELECT * FROM staff_logins WHERE staff_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$staff_id])->fetch();
        return $row ? new StaffLoginEntity($row) : null;
    }

    public function updateLastLogin($staff_id)
    {
        $sql = "UPDATE staff_logins SET last_login = NOW() WHERE staff_id = ?";
        $this->query($sql, [$staff_id]);
    }

    public function updateLastLoginByLoginId($login_id)
    {
        $sql = "UPDATE staff_logins SET last_login = NOW() WHERE login_id = ?";
        $this->query($sql, [$login_id]);
    }

    public function updatePassword($staff_id, $password)
    {
        $sql = "UPDATE staff_logins SET password = ? WHERE staff_id = ?";
        return $this->query($sql, [$password, $staff_id]);
    }

    public function getPassword($staff_id)
    {
        $sql = "SELECT password FROM staff_logins WHERE staff_id = ?";
        $row = $this->query($sql, [$staff_id])->fetch();
        return $row ? $row['password'] : null;
    }

    public function updatePosition($staff_id, $position)
    {
        $sql = "UPDATE staff_logins SET position = ? WHERE staff_id = ?";
        $this->query($sql, [$position, $staff_id]);
    }

    public function update($staff_id, $username, $position)
    {
        $sql = "UPDATE staff_logins SET username = ?, position = ? WHERE staff_id = ?";
        $this->query($sql, [$username, $position, $staff_id]);
    }

    public function create($staff_id, $username, $password, $position, $setLastLogin = false)
    {
        $login_id = IDGenerator::staffLoginID();
        if ($setLastLogin) {
            $sql = "INSERT INTO staff_logins (login_id, staff_id, username, password, position, last_login) VALUES (?, ?, ?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO staff_logins (login_id, staff_id, username, password, position) VALUES (?, ?, ?, ?, ?)";
        }
        $this->query($sql, [$login_id, $staff_id, $username, $password, $position]);
        return $login_id;
    }

    public function usernameExists($username)
    {
        $sql = "SELECT username FROM staff_logins WHERE username = ? AND is_deleted = 0";
        return $this->query($sql, [$username])->fetch() !== false;
    }

    public function usernameExistsCaseInsensitive($username, $excludeStaffId = null)
    {
        $sql = "SELECT username FROM staff_logins WHERE LOWER(username) = LOWER(?) AND is_deleted = 0";
        $params = [$username];

        if ($excludeStaffId !== null) {
            $sql .= " AND staff_id != ?";
            $params[] = $excludeStaffId;
        }

        return $this->query($sql, $params)->fetch() !== false;
    }

    public function incrementFailedAttempts($staff_id)
    {
        $sql = "UPDATE staff_logins SET failed_attempts = failed_attempts + 1 WHERE staff_id = ?";
        $this->query($sql, [$staff_id]);
        $row = $this->query("SELECT failed_attempts FROM staff_logins WHERE staff_id = ?", [$staff_id])->fetch();
        return $row ? (int)$row['failed_attempts'] : 0;
    }

    public function getFailedAttempts($staff_id)
    {
        $sql = "SELECT failed_attempts FROM staff_logins WHERE staff_id = ?";
        $row = $this->query($sql, [$staff_id])->fetch();
        return $row ? (int)$row['failed_attempts'] : 0;
    }

    public function lockForMinutes($staff_id, $minutes)
    {
        $sql = "UPDATE staff_logins SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE staff_id = ?";
        $this->query($sql, [$minutes, $staff_id]);
    }

    public function resetFailedAttemptsAndLock($staff_id)
    {
        $sql = "UPDATE staff_logins SET failed_attempts = 0, locked_until = NULL WHERE staff_id = ?";
        $this->query($sql, [$staff_id]);
    }

    public function clearExpiredLock($staff_id)
    {
        $row = $this->query("SELECT 1 FROM staff_logins WHERE staff_id = ? AND locked_until IS NOT NULL AND locked_until <= NOW()", [$staff_id])->fetch();
        if ($row) {
            $sql = "UPDATE staff_logins SET failed_attempts = 0, locked_until = NULL WHERE staff_id = ?";
            $this->query($sql, [$staff_id]);
            require_once __DIR__ . '/StaffData.php';
            $sd = new StaffData();
            $sd->setBlocked($staff_id, 0);
            return true;
        }
        return false;
    }

    public function forceClearLockIfExpired($staff_id)
    {
        $sql = "UPDATE staff_logins sl JOIN staff_data sd ON sl.staff_id = sd.staff_id
                SET sl.failed_attempts = 0, sl.locked_until = NULL, sd.is_blocked = 0
                WHERE sl.staff_id = ? AND sl.locked_until IS NOT NULL AND sl.locked_until <= NOW()";
        $this->query($sql, [$staff_id]);
    }
}
