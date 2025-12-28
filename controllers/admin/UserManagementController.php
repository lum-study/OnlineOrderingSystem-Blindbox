<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../lib/IDGenerator.php';
require_once __DIR__ . '/../../models/UserData.php';
require_once __DIR__ . '/../../models/UserLogin.php';

class UserManagementController
{
    /**
     * Show user list page
     */
    public function show()
    {
        if (!AdminAuth::check()) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }
        require __DIR__ . '/../../views/pages/admin/users/user_list.php';
    }
    /**
     * Get user details (AJAX)
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

        $userId = trim($_GET['id'] ?? '');

        if ($userId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }

        try {
            // Get full profile with login data
            $userDataModel = new UserData();
            $profile = $userDataModel->getProfileWithLogin($userId);

            if (!$profile) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }            // Return the complete profile data
            $result = [
                'user_id' => $profile['user_id'],
                'fullname' => $profile['fullname'],
                'email' => $profile['email'],
                'contact_number' => $profile['contact_number'] ?? 'N/A',
                'birth_date' => $profile['birth_date'] ?? '',
                'gender' => $profile['gender'] ?? '',
                'is_blocked' => $profile['is_blocked'] ?? 0,
                'created_date' => $profile['created_date'] ?? '',
                'username' => $profile['username'] ?? '',
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
     * Update user profile (admin editing user)     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/users/user_list.php');
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }
        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
            exit;
        }

        // Accept both user_id and user_id for backwards compatibility
        $userId = trim($_POST['edit_user_id'] ?? '');
        $fullname = trim($_POST['edit_fullname'] ?? '');
        $contactNumberPrefix = $_POST['edit_contact_number_prefix'] ?? '+60';
        $contactNumber = trim($_POST['edit_contact_number'] ?? '');
        $birthDate = $_POST['edit_birth_date'] ?? null;
        $gender = $_POST['edit_gender'] ?? null;

        // Validation
        if ($userId === '') {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
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

        // Note: Email is disabled for users (cannot be changed after verification)
        // We fetch the current email from the database and keep it unchanged

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
            $maxDate = date('Y-m-d', strtotime('-13 years'));
            if ($birthDateYmd < $minDate) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                exit;
            }
            if ($birthDateYmd > $maxDate) {
                echo json_encode(['success' => false, 'message' => 'User must be at least 13 years old']);
                exit;
            }

            $birthDateNormalized = $birthDateYmd;
        }
        $allowedGenders = [null, '', 'male', 'female', 'other'];
        if (!in_array($gender, $allowedGenders, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid gender selection']);
            exit;
        }

        // Set to NULL if no contact number is provided (to display N/A in UI)
        $fullContactNumber = ($contactNumber !== '') ? ($contactNumberPrefix . '-' . $contactNumber) : null;

        try {
            $userDataModel = new UserData();

            // Get current email from database (email cannot be changed for users)
            $currentUser = $userDataModel->findById($userId);
            if (!$currentUser) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }
            $userDataModel->update(
                $userId,
                $fullname,
                $currentUser->getEmail(), // Use current email (unchanged)
                $fullContactNumber,
                $birthDateNormalized,
                ($gender === '' ? null : $gender)
            );

            echo json_encode(['success' => true, 'message' => 'User updated successfully']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }    /**
     * Block/unblock user
     */
    public function toggleBlock()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'views/pages/admin/users/user_list.php');
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }
        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
            exit;
        }

        // Accept both user_id and user_id for backwards compatibility
        $userId = trim($_POST['user_id'] ?? '');
        $action = trim($_POST['action'] ?? 'block');
        $isBlocked = ($action === 'block') ? 1 : 0;

        if ($userId === '') {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }

        try {
            $userDataModel = new UserData();
            $userDataModel->setBlocked($userId, $isBlocked);

            // If admin is unblocking, clear temporary lock and failed attempts
            try {
                $userLoginModel = new UserLogin();
                if ($isBlocked === 0) {
                    $userLoginModel->resetFailedAttemptsAndLock($userId);
                }

                $activityLog = new ActivityLog();
                $activityLog->create($userId, $staffId, $isBlocked ? 'blocked_by_staff' : 'unblocked_by_staff', ($isBlocked ? 'Blocked by staff' : 'Unblocked by staff'), Security::getClientIP(), Security::getUserAgent());
            } catch (Exception $e) {
                // Do not interrupt flow on logging/reset errors
            }

            $actionText = $isBlocked ? 'blocked' : 'unblocked';
            echo json_encode(['success' => true, 'message' => "User {$actionText} successfully"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Create new user (admin creating new user account)
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

        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $username = trim($_POST['add_username'] ?? '');
        $fullname = trim($_POST['add_fullname'] ?? '');
        $email = trim($_POST['add_email'] ?? '');
        $contactNumberPrefix = $_POST['add_contact_number_prefix'] ?? '+60';
        $contactNumber = trim($_POST['add_contact_number'] ?? '');
        $birthDate = $_POST['add_birth_date'] ?? null;
        $gender = $_POST['add_gender'] ?? null;
        // Accept any truthy value from the checkbox (e.g., '1' or 'on')
        $sendVerification = !empty($_POST['add_send_verification']) ? 1 : 0;
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
            $maxDate = date('Y-m-d', strtotime('-13 years'));
            if ($birthDateYmd < $minDate) {
                echo json_encode(['success' => false, 'message' => 'Birth date cannot be more than 120 years ago']);
                exit;
            }
            if ($birthDateYmd > $maxDate) {
                echo json_encode(['success' => false, 'message' => 'User must be at least 13 years old']);
                exit;
            }

            $birthDateNormalized = $birthDateYmd;
        }

        $allowedGenders = [null, '', 'male', 'female', 'other'];
        if (!in_array($gender, $allowedGenders, true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid gender selection']);
            exit;
        }

        $fullContactNumber = ($contactNumber !== '') ? ($contactNumberPrefix . '-' . $contactNumber) : null;

        try {
            $userLoginModel = new UserLogin();

            if ($userLoginModel->usernameExistsCaseInsensitive($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            $userDataModel = new UserData();

            if ($userDataModel->emailExists($email)) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            // If this is only a validation check (simulate), return success now
            if ($simulate) {
                echo json_encode(['success' => true, 'message' => 'Validation passed']);
                exit;
            }

            $userId = IDGenerator::userID();

            $userDataModel->create($userId, $fullname, $email, $fullContactNumber, $birthDateNormalized, ($gender === '' ? null : $gender));

            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);

            $userLoginModel->createByAdmin($userId, $username, $hashedPassword, false);

            $response = [
                'success' => true,
                'message' => 'User created successfully',
                'user_id' => $userId,
                'data' => [
                    'user_id' => $userId,
                    'username' => $username,
                    'fullname' => $fullname,
                    'email' => $email,
                    'contact_number' => $fullContactNumber ?? 'N/A',
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
     * Permanently delete user (soft delete)
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

        $staffId = Session::getAdmin('staff_id');
        if (!$staffId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }

        $userId = trim($_POST['user_id'] ?? '');
        $confirmId = trim($_POST['confirm_id'] ?? '');

        if ($userId === '') {
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            exit;
        }

        // Backend validation: confirm_id must match user_id exactly (case-sensitive)
        if ($confirmId !== $userId) {
            echo json_encode(['success' => false, 'message' => 'Confirmation ID does not match. Please type the exact User ID to confirm deletion.']);
            exit;
        }

        try {
            $userDataModel = new UserData();
            $userLoginModel = new UserLogin();

            // Check if user exists
            $userD = $userDataModel->findById($userId);
            if (!$userD) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // Check if user exists
            $userL = $userLoginModel->findByUserId($userId);
            if (!$userL) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit;
            }

            // Soft delete the user
            $userDataModel->setDeleted($userId, 1);
            $userLoginModel->setDeleted($userId, 1);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully. This action cannot be undone.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
