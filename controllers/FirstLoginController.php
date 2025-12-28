<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../lib/Auth.php';
require_once __DIR__ . '/../models/UserLogin.php';

class FirstLoginController
{
    public function show()
    {
        // Security: Only accessible via proper first-login flow from login page
        if (!isset($_SESSION['first_login_pending'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $pendingData = $_SESSION['first_login_pending'];
        
        // Validate session hasn't expired (10 minutes timeout)
        if ((time() - $pendingData['timestamp']) > 600) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // Validate IP hasn't changed
        if ($pendingData['ip'] !== Security::getClientIP()) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // Validate security token from URL
        $securityToken = $_GET['token'] ?? '';
        if ($securityToken !== $pendingData['token']) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        $userId = $pendingData['user_id'];
        $userLoginModel = new UserLogin();
        $user = $userLoginModel->findByUserId($userId);

        // Double-check user still qualifies for first login
        if (!$user || !is_null($user->getLastLogin()) || !$user->isEmailVerified()) {
            unset($_SESSION['first_login_pending']);
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        // Prevent caching so back button doesn't show cached page
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Ensure member session is NOT set (double security)
        unset($_SESSION['member']);
        unset($_SESSION['user_id']);

        require __DIR__ . '/../views/pages/first_login.php';
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

        $userId = $pendingData['user_id'];
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
            $userLoginModel = new UserLogin();
            
            // Final validation: user still qualifies for first login
            $user = $userLoginModel->findByUserId($userId);
            if (!$user || !is_null($user->getLastLogin()) || !$user->isEmailVerified()) {
                unset($_SESSION['first_login_pending']);
                echo json_encode(['success' => false, 'message' => 'Invalid state. Please login again.']);
                exit;
            }

            // Update password
            $hashedPassword = Security::hashPassword($password);
            $userLoginModel->updatePassword($userId, $hashedPassword);

            // Update last_login to mark first login as complete
            $userLoginModel->updateLastLogin($userId);

            // Create proper authenticated session
            $userData = $userLoginModel->findByUsername($user->getUsername());
            Auth::createSession($userId, $userData);

            // Clear temporary first-login session
            unset($_SESSION['first_login_pending']);

            echo json_encode([
                'success' => true,
                'message' => 'Password set successfully. Welcome to BlindeDoos!',
                'redirect' => BASE_URL
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error setting password. Please try again.']);
        }
        exit;
    }
}
