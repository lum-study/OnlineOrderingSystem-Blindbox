<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/ActivityLog.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class ActivityLog extends Database
{
    public function create($user_id, $staff_id, $action, $description, $ip_address, $user_agent)
    {
        $activity_id = IDGenerator::activityLogID();
        $sql = "INSERT INTO activity_logs (activity_id, user_id, staff_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->query($sql, [$activity_id, $user_id, $staff_id, $action, $description, $ip_address, $user_agent]);
    }
}
