<?php

class StaffDataEntity
{
    private $staff_id;
    private $fullname;
    private $email;
    private $contact_number;
    private $birth_date;
    private $gender;
    private $profile_photo;
    private $is_blocked;
    private $is_deleted;

    public function __construct($data)
    {
        $this->staff_id = $data['staff_id'] ?? null;
        $this->fullname = $data['fullname'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->contact_number = $data['contact_number'] ?? null;
        $this->birth_date = $data['birth_date'] ?? null;
        $this->gender = $data['gender'] ?? null;
        $this->profile_photo = $data['profile_photo'] ?? null;
        $this->is_blocked = $data['is_blocked'] ?? 0;
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getStaffId() { return $this->staff_id; }
    public function getFullname() { return $this->fullname; }
    public function getEmail() { return $this->email; }
    public function getContactNumber() { return $this->contact_number; }
    public function getBirthDate() { return $this->birth_date; }
    public function getGender() { return $this->gender; }
    public function getProfilePhoto() { return $this->profile_photo; }
    public function isBlocked() { return $this->is_blocked; }
    public function isDeleted() { return $this->is_deleted; }
}
