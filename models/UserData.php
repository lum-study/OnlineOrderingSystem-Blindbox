<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/UserData.php';

class UserData extends Database
{
    public function findById($user_id)
    {
        $sql = "SELECT * FROM user_data WHERE user_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$user_id])->fetch();
        return $row ? new UserDataEntity($row) : null;
    }    public function findByEmail($email)
    {
        $sql = "SELECT * FROM user_data WHERE email = ? AND is_deleted = 0";
        $row = $this->query($sql, [$email])->fetch();
        return $row ? new UserDataEntity($row) : null;
    }    public function emailExists($email)
    {
        // Check only ACTIVE emails to allow reusing deleted emails
        $sql = "SELECT email FROM user_data WHERE email = ? AND is_deleted = 0";
        return $this->query($sql, [$email])->fetch() !== false;
    }

    public function create($user_id, $fullname, $email, $contact_number, $birth_date, $gender)
    {
        $sql = "INSERT INTO user_data (user_id, fullname, email, contact_number, birth_date, gender) VALUES (?, ?, ?, ?, ?, ?)";
        $this->query($sql, [$user_id, $fullname, $email, $contact_number, $birth_date, $gender]);
    }

    public function update($user_id, $fullname, $email, $contact_number, $birth_date, $gender)
    {
        $sql = "UPDATE user_data SET fullname = ?, email = ?, contact_number = ?, birth_date = ?, gender = ? WHERE user_id = ?";
        $this->query($sql, [$fullname, $email, $contact_number, $birth_date, $gender, $user_id]);
    }

    public function updateProfilePhoto($user_id, $filename)
    {
        $sql = "UPDATE user_data SET profile_photo = ? WHERE user_id = ?";
        $this->query($sql, [$filename, $user_id]);
    }

    /**
     * Update a specific preference key inside the JSON `preferences` column.
     * Uses JSON_SET to preserve other keys safely even when preferences is NULL.
     */
    public function updatePreference($user_id, $key, $value)
    {
        $path = '$.' . $key;
        $sql = "UPDATE user_data SET preferences = JSON_SET(COALESCE(preferences, JSON_OBJECT()), ?, ?) WHERE user_id = ?";
        $this->query($sql, [$path, $value, $user_id]);
    }

    /**
     * Get preferences as associative array or null if empty.
     */
    public function getPreferencesDecoded($user_id)
    {
        $sql = "SELECT preferences FROM user_data WHERE user_id = ?";
        $row = $this->query($sql, [$user_id])->fetch();
        if (!$row || empty($row['preferences'])) return null;
        $decoded = json_decode($row['preferences'], true);
        return is_array($decoded) ? $decoded : null;
    }

    public function getProfileWithLogin($user_id)
    {
        $sql = "SELECT ud.*, ul.username, ul.email_verified, ul.last_login 
                FROM user_data ud 
                JOIN user_logins ul ON ud.user_id = ul.user_id 
                WHERE ud.user_id = ?";
        return $this->query($sql, [$user_id])->fetch();
    }

    public function setBlocked($user_id, $is_blocked)
    {
        $sql = "UPDATE user_data SET is_blocked = ? WHERE user_id = ?";
        $this->query($sql, [$is_blocked, $user_id]);
    }
    public function setDeleted($user_id, $is_deleted)
    {
        $sql = "UPDATE user_data SET is_deleted = ? WHERE user_id = ?";
        $this->query($sql, [$is_deleted, $user_id]);
    }
}
