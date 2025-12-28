<?php

class ActivityLogEntity
{
    private $activity_id;
    private $user_id;
    private $staff_id;
    private $action;
    private $description;
    private $ip_address;
    private $user_agent;
    private $created_at;

    public function __construct($data)
    {
        $this->activity_id = $data['activity_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->staff_id = $data['staff_id'] ?? null;
        $this->action = $data['action'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->ip_address = $data['ip_address'] ?? null;
        $this->user_agent = $data['user_agent'] ?? null;
        $this->created_at = $data['created_at'] ?? null;
    }

    public function getActivityId() { return $this->activity_id; }
    public function getUserId() { return $this->user_id; }
    public function getStaffId() { return $this->staff_id; }
    public function getAction() { return $this->action; }
    public function getDescription() { return $this->description; }
    public function getIpAddress() { return $this->ip_address; }
    public function getUserAgent() { return $this->user_agent; }
    public function getCreatedAt() { return $this->created_at; }
}
