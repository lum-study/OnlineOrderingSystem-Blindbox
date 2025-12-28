<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../lib/IDGenerator.php';
require_once __DIR__ . '/../../models/StaffData.php';
require_once __DIR__ . '/../../models/StaffLogin.php';

class StaffManagementController
{
    /**
     * Check if current staff has permission to access staff management
     * Only admin and manager positions are allowed
     */
    private function checkStaffManagementAccess(): bool
    {
        $position = Session::getAdmin('position') ?? '';
        return in_array(strtolower($position), ['admin', 'manager']);
    }

    /**
     * Show staff list page
     */
    public function show()
    {
        if (!AdminAuth::check()) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            header('Location: ' . BASE_URL . 'admin/dashboard?error=' . urlencode('Access denied. Only admin and manager can access staff management.'));
            exit;
        }

        require __DIR__ . '/../../views/pages/admin/users/staff_list.php';
    }

    /**
     * Get staff details (AJAX)
     */
    public function get()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        header('Content-Type: application/json');
        if (!AdminAuth::check()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Only admin and manager can access staff management.']);
            exit;
        }

        $staffId = trim($_GET['id'] ?? '');

        if ($staffId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }

        try {
            // Get full profile with login data
            $staffDataModel = new StaffData();
            $profile = $staffDataModel->getProfileWithLogin($staffId);

            if (!$profile) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Staff not found']);
                exit;
            }

            // Return the complete profile data
            $result = [
                'staff_id' => $profile['staff_id'],
                'fullname' => $profile['fullname'],
                'email' => $profile['email'],
                'contact_number' => $profile['contact_number'] ?? 'N/A',
                'birth_date' => $profile['birth_date'] ?? '',
                'gender' => $profile['gender'] ?? '',
                'is_blocked' => $profile['is_blocked'] ?? 0,
                'created_date' => $profile['created_date'] ?? '',
                'username' => $profile['username'] ?? '',
                'position' => $profile['position'] ?? '',
                'last_login' => $profile['last_login'] ?? '',
            ];

            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Update staff profile (admin editing staff)
     */    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/users/staff_list.php');
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }
        $adminId = Session::getAdmin('staff_id');
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            echo json_encode(['success' => false, 'message' => 'Access denied. Only admin and manager can access staff management.']);
            exit;
        }

        // Accept both staff_id and edit_staff_id for consistency with modal
        $staffId = trim($_POST['edit_staff_id'] ?? $_POST['staff_id'] ?? '');
        $username = trim($_POST['edit_username'] ?? $_POST['username'] ?? '');
        $position = trim($_POST['edit_position'] ?? $_POST['position'] ?? '');
        $customPosition = trim($_POST['edit_custom_position'] ?? $_POST['custom_position'] ?? '');
        $fullname = trim($_POST['edit_fullname'] ?? $_POST['fullname'] ?? '');
        $email = trim($_POST['edit_email'] ?? $_POST['email'] ?? '');
        $contactNumberPrefix = $_POST['edit_contact_number_prefix'] ?? $_POST['contact_number_prefix'] ?? '+60';
        $contactNumber = trim($_POST['edit_contact_number'] ?? $_POST['contact_number'] ?? '');
        $birthDate = $_POST['edit_birth_date'] ?? $_POST['birth_date'] ?? null;
        $gender = $_POST['edit_gender'] ?? $_POST['gender'] ?? null;

        // Validation
        if ($staffId === '') {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }

        if ($username === '') {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit;
        }

        if (mb_strlen($username) < 4) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 4 characters long']);
            exit;
        }

        if (mb_strlen($username) > 50) {
            echo json_encode(['success' => false, 'message' => 'Username must not exceed 50 characters']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, underscores, and hyphens']);
            exit;
        }

        if ($position === '') {
            echo json_encode(['success' => false, 'message' => 'Position is required']);
            exit;
        }

        if ($position === 'other') {
            if ($customPosition === '') {
                echo json_encode(['success' => false, 'message' => 'Custom position is required when "Other" is selected']);
                exit;
            }
            $position = $customPosition;
        }

        if (mb_strlen($position) > 100) {
            echo json_encode(['success' => false, 'message' => 'Position must not exceed 100 characters']);
            exit;
        }

        if ($fullname === '') {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            exit;
        }
        if (mb_strlen($fullname) < 3) {
            echo json_encode(['success' => false, 'message' => 'Full name must be at least 3 characters long']);
            exit;
        }
        if (mb_strlen($fullname) > 100) {
            echo json_encode(['success' => false, 'message' => 'Full name must not exceed 100 characters']);
            exit;
        }
        if (!preg_match("/^[a-zA-Z\\s\\-]+$/", $fullname)) {
            echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, and hyphens']);
            exit;
        }
        if ($email === '') {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if (mb_strlen($email) > 100) {
            echo json_encode(['success' => false, 'message' => 'Email must not exceed 100 characters']);
            exit;
        }

        if (
            !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) ||
            str_contains($email, '..') ||
            str_starts_with($email, '.') ||
            str_ends_with($email, '.')
        ) {
            echo json_encode(['success' => false, 'message' => 'Email address format is invalid']);
            exit;
        }

        if ($contactNumber !== '' && !preg_match('/^[0-9]{7,15}$/', $contactNumber)) {
            echo json_encode(['success' => false, 'message' => 'Contact number must be between 7 and 15 digits']);
            exit;
        }

        $birthDateNormalized = null;
        if (!empty($birthDate)) {
            $ts = strtotime($birthDate);
            if ($ts === false) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid birth date']);
                exit;
            }

            $birthDateYmd = date('Y-m-d', $ts);
            $todayYmd = date('Y-m-d');
            if ($birthDateYmd > $todayYmd) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be in the future']);
                exit;
            }

            $minDate = date('Y-m-d', strtotime('-120 years'));
            $maxDate = date('Y-m-d', strtotime('-18 years'));
            if ($birthDateYmd < $minDate) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                exit;
            }
            if ($birthDateYmd > $maxDate) {
                echo json_encode(['success' => false, 'message' => 'Staff must be at least 18 years old']);
                exit;
            }

            $birthDateNormalized = $birthDateYmd;
        }

        if ($gender === '' || $gender === null) {
            $gender = null;
        }

        $fullContactNumber = ($contactNumber !== '') ? ($contactNumberPrefix . '-' . $contactNumber) : null;

        // Duplicate checks: ensure username and email are unique among active staff, excluding current staff
        $staffLoginModel = new StaffLogin();
        if ($staffLoginModel->usernameExistsCaseInsensitive($username, $staffId)) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit;
        }

        $staffDataModel = new StaffData();
        if ($staffDataModel->emailExists($email, $staffId)) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }

        try {
            $staffDataModel = new StaffData();
            $staffDataModel->update(
                $staffId,
                $fullname,
                $email,
                $fullContactNumber,
                $birthDateNormalized,
                ($gender === '' ? null : $gender)
            );

            // Update position in staff_logins table
            $staffLoginModel = new StaffLogin();
            $staffLoginModel->update($staffId, $username, $position);

            echo json_encode(['success' => true, 'message' => 'Staff updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Block/unblock staff
     */
    public function toggleBlock()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/users/staff_list.php');
            exit;
        }

        header('Content-Type: application/json');
        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }
        $adminId = Session::getAdmin('staff_id');
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Only admin and manager can access staff management.']);
            exit;
        }

        $staffId = trim($_POST['staff_id'] ?? '');
        $action = trim($_POST['action'] ?? 'block');
        $isBlocked = ($action === 'block') ? 1 : 0;

        if ($staffId === '') {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }

        // Prevent self-blocking
        if ($staffId === $adminId) {
            echo json_encode(['success' => false, 'message' => 'You cannot block yourself']);
            exit;
        }

        try {
            $staffDataModel = new StaffData();
            $staffDataModel->setBlocked($staffId, $isBlocked);

            // If admin is unblocking, clear temporary lock and failed attempts
            try {
                $staffLoginModel = new StaffLogin();
                if ($isBlocked === 0) {
                    $staffLoginModel->resetFailedAttemptsAndLock($staffId);
                }
                $activityLog = new ActivityLog();
                $activityLog->create(null, $adminId, $isBlocked ? 'blocked_staff' : 'unblocked_staff', ($isBlocked ? 'Blocked staff account' : 'Unblocked staff account'), Security::getClientIP(), Security::getUserAgent());
            } catch (Exception $e) {
                // ignore
            }

            $actionText = $isBlocked ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "Staff {$actionText} successfully"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Create new staff (admin creating new staff)
     */
    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        $adminId = Session::getAdmin('staff_id');
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Only admin and manager can access staff management.']);
            exit;
        }

        $username = trim($_POST['add_username'] ?? $_POST['username'] ?? '');
        $fullname = trim($_POST['add_fullname'] ?? $_POST['fullname'] ?? '');
        $email = trim($_POST['add_email'] ?? $_POST['email'] ?? '');
        $position = trim($_POST['add_position'] ?? $_POST['position'] ?? '');
        $customPosition = trim($_POST['add_custom_position'] ?? $_POST['custom_position'] ?? '');
        $contactNumberPrefix = $_POST['add_contact_number_prefix'] ?? $_POST['contact_number_prefix'] ?? '+60';
        $contactNumber = trim($_POST['add_contact_number'] ?? $_POST['contact_number'] ?? '');
        $birthDate = $_POST['add_birth_date'] ?? $_POST['birth_date'] ?? null;
        $gender = $_POST['add_gender'] ?? $_POST['gender'] ?? null;
        // Accept any truthy value from the checkbox (e.g., '1' or 'on')
        $sendVerification = !empty($_POST['add_send_verification']) || !empty($_POST['send_verification']) ? 1 : 0;
        $simulate = isset($_POST['simulate']) && $_POST['simulate'] === '1';

        if ($username === '') {
            echo json_encode(['success' => false, 'message' => 'Username is required']);
            exit;
        }

        if (mb_strlen($username) < 4) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 4 characters long']);
            exit;
        }

        if (mb_strlen($username) > 50) {
            echo json_encode(['success' => false, 'message' => 'Username must not exceed 50 characters']);
            exit;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, underscores, and hyphens']);
            exit;
        }

        if ($fullname === '') {
            echo json_encode(['success' => false, 'message' => 'Full name is required']);
            exit;
        }

        if (mb_strlen($fullname) < 3) {
            echo json_encode(['success' => false, 'message' => 'Full name must be at least 3 characters long']);
            exit;
        }

        if (mb_strlen($fullname) > 100) {
            echo json_encode(['success' => false, 'message' => 'Full name must not exceed 100 characters']);
            exit;
        }
        if (!preg_match("/^[a-zA-Z\\s\\-]+$/", $fullname)) {
            echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, and hyphens']);
            exit;
        }

        if ($email === '') {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if (mb_strlen($email) > 100) {
            echo json_encode(['success' => false, 'message' => 'Email must not exceed 100 characters']);
            exit;
        }

        if (
            !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) ||
            str_contains($email, '..') ||
            str_starts_with($email, '.') ||
            str_ends_with($email, '.')
        ) {
            echo json_encode(['success' => false, 'message' => 'Email address format is invalid']);
            exit;
        }

        if ($position === '') {
            echo json_encode(['success' => false, 'message' => 'Position is required']);
            exit;
        }

        if ($position === 'other') {
            if ($customPosition === '') {
                echo json_encode(['success' => false, 'message' => 'Custom position is required when "Other" is selected']);
                exit;
            }
            $position = $customPosition;
        }

        if (mb_strlen($position) > 100) {
            echo json_encode(['success' => false, 'message' => 'Position must not exceed 100 characters']);
            exit;
        }

        if ($contactNumber !== '' && !preg_match('/^[0-9]{7,15}$/', $contactNumber)) {
            echo json_encode(['success' => false, 'message' => 'Contact number must be between 7 and 15 digits']);
            exit;
        }

        $birthDateNormalized = null;
        if (!empty($birthDate)) {
            $ts = strtotime($birthDate);
            if ($ts === false) {
                echo json_encode(['success' => false, 'message' => 'Please enter a valid birth date']);
                exit;
            }

            $birthDateYmd = date('Y-m-d', $ts);
            $todayYmd = date('Y-m-d');
            if ($birthDateYmd > $todayYmd) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be in the future']);
                exit;
            }

            $minDate = date('Y-m-d', strtotime('-120 years'));
            $maxDate = date('Y-m-d', strtotime('-18 years'));
            if ($birthDateYmd < $minDate) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                exit;
            }
            if ($birthDateYmd > $maxDate) {
                echo json_encode(['success' => false, 'message' => 'Staff must be at least 18 years old']);
                exit;
            }

            $birthDateNormalized = $birthDateYmd;
        }

        if ($gender === '' || $gender === null) {
            $gender = null;
        }

        $fullContactNumber = ($contactNumber !== '') ? ($contactNumberPrefix . '-' . $contactNumber) : null;

        try {
            $staffLoginModel = new StaffLogin();

            if ($staffLoginModel->usernameExistsCaseInsensitive($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            $staffDataModel = new StaffData();
            if ($staffDataModel->emailExists($email)) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            // If this is only a validation check (simulate), return success now
            if ($simulate) {
                echo json_encode(['success' => true, 'message' => 'Validation passed']);
                exit;
            }

            $staffId = IDGenerator::staffID();

            $staffDataModel->create($staffId, $fullname, $email, $fullContactNumber, $birthDateNormalized, ($gender === '' ? null : $gender));

            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

            $staffLoginModel->create($staffId, $username, $hashedPassword, $position, false);

            $response = [
                'success' => true,
                'message' => 'Staff created successfully',
                'staff_id' => $staffId,
                'data' => [
                    'staff_id' => $staffId,
                    'username' => $username,
                    'fullname' => $fullname,
                    'email' => $email ?? 'N/A',
                    'contact_number' => $fullContactNumber ?? 'N/A',
                    'position' => $position,
                    'is_blocked' => 0
                ]
            ];

            if ($sendVerification) {
                require_once __DIR__ . '/../../lib/VerificationToken.php';
                $token = VerificationTokenLib::createEmailVerificationToken($email);
                if ($token && !isset($token['error'])) {
                    require_once __DIR__ . '/../../lib/Email.php';
                    Email::sendEmailVerification($email, $token);
                    $response['send_verification'] = true;
                }
            }
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Permanently delete staff (soft delete)
     */
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }

        $adminId = Session::getAdmin('staff_id');
        if (!$adminId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        // Check if staff has permission to access staff management
        if (!$this->checkStaffManagementAccess()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Only admin and manager can access staff management.']);
            exit;
        }

        $staffId = trim($_POST['staff_id'] ?? '');
        $confirmId = trim($_POST['confirm_id'] ?? '');

        if ($staffId === '') {
            echo json_encode(['success' => false, 'message' => 'Staff ID is required']);
            exit;
        }

        // Prevent self-deletion
        if ($staffId === $adminId) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete yourself']);
            exit;
        }

        // Backend validation: confirm_id must match staff_id exactly (case-sensitive)
        if ($confirmId !== $staffId) {
            echo json_encode(['success' => false, 'message' => 'Confirmation ID does not match. Please type the exact Staff ID to confirm deletion.']);
            exit;
        }

        try {
            $staffDataModel = new StaffData();

            // Check if staff exists
            $staff = $staffDataModel->findById($staffId);
            if (!$staff) {
                echo json_encode(['success' => false, 'message' => 'Staff not found']);
                exit;
            }

            // Soft delete the staff
            $staffDataModel->setDeleted($staffId, 1);

            echo json_encode(['success' => true, 'message' => 'Staff deleted successfully. This action cannot be undone.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
