<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../models/StaffLogin.php';

class AdminFirstLoginController
{
    public function show()
    {
        // Security: Only accessible via proper first-login flow from admin login page
        if (!isset($_SESSION['first_login_pending'])) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        $pendingData = $_SESSION['first_login_pending'];
        
        // Validate session hasn't expired (10 minutes timeout)
        if ((time() - $pendingData['timestamp']) > 600) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Validate IP hasn't changed
        if ($pendingData['ip'] !== Security::getClientIP()) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Validate security token from URL
        $securityToken = $_GET['token'] ?? '';
        if ($securityToken !== $pendingData['token']) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Validate it's staff_id not user_id
        if (!isset($pendingData['staff_id'])) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        $staffId = $pendingData['staff_id'];
        $staffLoginModel = new StaffLogin();
        $staff = $staffLoginModel->findByStaffId($staffId);

        // Double-check staff still qualifies for first login
        if (!$staff || !is_null($staff->getLastLogin())) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Prevent caching so back button doesn't show cached page
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Ensure admin session is NOT set (double security)
        unset($_SESSION['admin']);

        require __DIR__ . '/../../views/pages/admin/admin_first_login.php';
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        header('Content-Type: application/json');

        // Validate first-login session exists
        if (!isset($_SESSION['first_login_pending'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }

        // Validate CSRF token
        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid security token. Please try again.']);
            exit;
        }

        $pendingData = $_SESSION['first_login_pending'];
        
        // Validate session timeout (10 minutes)
        if ((time() - $pendingData['timestamp']) > 600) {
            unset($_SESSION['first_login_pending']);
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
            exit;
        }

        // Validate IP consistency
        if ($pendingData['ip'] !== Security::getClientIP()) {
            unset($_SESSION['first_login_pending']);
            echo json_encode(['success' => false, 'message' => 'Security validation failed.']);
            exit;
        }

        // Validate it's staff_id not user_id
        if (!isset($pendingData['staff_id'])) {
            unset($_SESSION['first_login_pending']);
            echo json_encode(['success' => false, 'message' => 'Invalid session type.']);
            exit;
        }

        $staffId = $pendingData['staff_id'];
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate inputs
        if (empty($password) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        if (!Validator::passwordStrength($password)) {
            echo json_encode(['success' => false, 'message' => 'Password must be 8+ chars with uppercase, lowercase, number, and special character']);
            exit;
        }

        try {
            $staffLoginModel = new StaffLogin();
            
            // Final validation: staff still qualifies for first login
            $staff = $staffLoginModel->findByStaffId($staffId);
            if (!$staff || !is_null($staff->getLastLogin())) {
                unset($_SESSION['first_login_pending']);
                echo json_encode(['success' => false, 'message' => 'Invalid state. Please login again.']);
                exit;
            }

            // Update password
            $hashedPassword = Security::hashPassword($password);
            $staffLoginModel->updatePassword($staffId, $hashedPassword);

            // Update last_login to mark first login as complete
            $staffLoginModel->updateLastLogin($staffId);

            // Create proper authenticated session
            $staffData = $staffLoginModel->findByUsername($staff->getUsername());
            AdminAuth::createSession($staffId, $staffData['position'], $staffData);

            // Clear temporary first-login session
            unset($_SESSION['first_login_pending']);

            echo json_encode([
                'success' => true,
                'message' => 'Password set successfully. Welcome to Admin Portal!',
                'redirect' => BASE_URL . 'admin/dashboard'
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error setting password. Please try again.']);
        }
        exit;
    }
}
