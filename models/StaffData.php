<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/StaffData.php';

class StaffData extends Database
{
    public function findById($staff_id)
    {
        $sql = "SELECT * FROM staff_data WHERE staff_id = ? AND is_deleted = 0";
        $row = $this->query($sql, [$staff_id])->fetch();
        return $row ? new StaffDataEntity($row) : null;
    }

    public function findByEmail($email)
    {
        $sql = "SELECT * FROM staff_data WHERE email = ? AND is_deleted = 0";
        $row = $this->query($sql, [$email])->fetch();
        return $row ? new StaffDataEntity($row) : null;
    }

    public function update($staff_id, $fullname, $email, $contact_number, $birth_date, $gender)
    {
        $sql = "UPDATE staff_data SET fullname = ?, email = ?, contact_number = ?, birth_date = ?, gender = ? WHERE staff_id = ?";
        $this->query($sql, [$fullname, $email, $contact_number, $birth_date, $gender, $staff_id]);
    }

    public function updateProfilePhoto($staff_id, $filename)
    {
        $sql = "UPDATE staff_data SET profile_photo = ? WHERE staff_id = ?";
        $this->query($sql, [$filename, $staff_id]);
    }

    public function getProfileWithLogin($staff_id)
    {
        $sql = "SELECT sd.*, sl.username, sl.position, sl.last_login 
                FROM staff_data sd 
                JOIN staff_logins sl ON sd.staff_id = sl.staff_id 
                WHERE sd.staff_id = ?";
        return $this->query($sql, [$staff_id])->fetch();
    }

    public function setBlocked($staff_id, $is_blocked)
    {
        $sql = "UPDATE staff_data SET is_blocked = ? WHERE staff_id = ?";
        $this->query($sql, [$is_blocked, $staff_id]);
    }

    public function create($staff_id, $fullname, $email, $contact_number = null, $birth_date = null, $gender = null)
    {
        $sql = "INSERT INTO staff_data (staff_id, fullname, email, contact_number, birth_date, gender) VALUES (?, ?, ?, ?, ?, ?)";
        $this->query($sql, [$staff_id, $fullname, $email, $contact_number, $birth_date, $gender]);
    }

    public function emailExists($email, $excludeStaffId = null)
    {
        $sql = "SELECT email FROM staff_data WHERE email = ? AND is_deleted = 0";
        $params = [$email];
        if ($excludeStaffId !== null) {
            $sql .= " AND staff_id != ?";
            $params[] = $excludeStaffId;
        }
        return $this->query($sql, $params)->fetch() !== false;
    }

    public function setDeleted($staff_id, $is_deleted)
    {
        $sql = "UPDATE staff_data SET is_deleted = ? WHERE staff_id = ?";
        $this->query($sql, [$is_deleted, $staff_id]);
    }
}
