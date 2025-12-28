<?php
require_once __DIR__ . '/../../../config/init.php';
require_once __DIR__ . '/../../../includes/admin_guard.php';
require_once __DIR__ . '/../../../models/ActivityLog.php';

$pageTitle = 'Activity Logs';
include __DIR__ . '/../../../includes/admin/header.php';

$activityModel = new ActivityLog();
$logs = Database::fetchAll("SELECT al.*, sd.fullname AS staff_fullname, ud.fullname AS user_fullname
    FROM activity_logs al
    LEFT JOIN staff_data sd ON al.staff_id = sd.staff_id
    LEFT JOIN user_data ud ON al.user_id = ud.user_id
    WHERE al.activity_id IS NOT NULL
    ORDER BY al.created_date DESC LIMIT 100");
?>

<div class="admin-header-bar">
    <div>
        <p class="admin-breadcrumb">Admin / Activity Logs</p>
        <h1>ACTIVITY LOGS</h1>
    </div>
</div>

<div class="admin-card">
    <h2 class="admin-card-header">Recent Activity</h2>

    <div class="table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Activity ID</th>
                    <th>Staff (ID)</th>
                    <th>User (ID)</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8">No activity found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $staffName = $log['staff_fullname'] ?? null;
                        $staffIdVal = $log['staff_id'] ?? null;
                        if ($staffName && $staffIdVal) {
                            $staffDisplay = htmlspecialchars($staffName) . ' (' . htmlspecialchars($staffIdVal) . ')';
                        } elseif ($staffName) {
                            $staffDisplay = htmlspecialchars($staffName);
                        } elseif ($staffIdVal) {
                            $staffDisplay = htmlspecialchars($staffIdVal);
                        } else {
                            $staffDisplay = 'N/A';
                        }

                        $userName = $log['user_fullname'] ?? null;
                        $userIdVal = $log['user_id'] ?? null;
                        if ($userName && $userIdVal) {
                            $userDisplay = htmlspecialchars($userName) . ' (' . htmlspecialchars($userIdVal) . ')';
                        } elseif ($userName) {
                            $userDisplay = htmlspecialchars($userName);
                        } elseif ($userIdVal) {
                            $userDisplay = htmlspecialchars($userIdVal);
                        } else {
                            $userDisplay = 'N/A';
                        }

                        $ua = $log['user_agent'] ?? '';
                        $uaDisplay = htmlspecialchars((mb_strlen($ua) > 60) ? mb_substr($ua, 0, 60) . '...' : $ua);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($log['activity_id']) ?></td>
                            <td><?= $staffDisplay ?></td>
                            <td><?= $userDisplay ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                            <td><?= nl2br(htmlspecialchars($log['description'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                            <td><small><?= $uaDisplay ?></small></td>
                            <td><?= htmlspecialchars($log['created_date'] ?? $log['created_at'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../../includes/admin/footer.php'; ?>
