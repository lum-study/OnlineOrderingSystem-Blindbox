<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../lib/AdminAuth.php';
require_once __DIR__ . '/../../models/StaffData.php';
require_once __DIR__ . '/../../models/StaffLogin.php';

class AdminAuthController
{
    /**
     * Show admin login page
     */
    public function showAdminLogin()
    {
        require __DIR__ . '/../../views/pages/admin/login.php';
    }
    /**
     * Show admin forgot password page
     */
    public function showForgotPassword()
    {
        require __DIR__ . '/../views/pages/admin/forgot_password.php';
    }

    /**
     * Handle admin/staff login     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            // Verify reCAPTCHA
            $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
            if (!Recaptcha::verify($recaptchaResponse, Security::getClientIP())) {
                echo json_encode(['success' => false, 'message' => 'Please complete the reCAPTCHA verification']);
                exit;
            }

            $usernameOrEmail = Security::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            // Validate required fields
            if (empty($usernameOrEmail) || empty($password)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required']);
                exit;
            }

            // Determine if input is email or username
            $isEmail = filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL);

            if ($isEmail) {
                // Validate email using Validator
                if (!Validator::email($usernameOrEmail)) {
                    echo json_encode(['success' => false, 'message' => 'Email address format is invalid']);
                    exit;
                }

                $staffDataModel = new StaffData();
                $staff = $staffDataModel->findByEmail($usernameOrEmail);

                if (!$staff) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email']);
                    exit;
                }

                // Get username from staff_login table using staff_id
                $staffLoginModel = new StaffLogin();
                $staffLogin = $staffLoginModel->findById($staff->getStaffId());

                if (!$staffLogin) {
                    echo json_encode(['success' => false, 'message' => 'Invalid username']);
                    exit;
                }

                $username = $staffLogin->getUsername();
            } else {
                // Validate username using Validator
                if (!Validator::username($usernameOrEmail)) {
                    echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters and contain only letters, numbers, and underscores']);
                    exit;
                }

                $username = $usernameOrEmail;
            }

            $result = AdminAuth::attempt($username, $password);

            if ($result['success']) {
                if (isset($result['first_login'])) {
                    $securityToken = $result['security_token'];
                    echo json_encode([
                        'success' => true,
                        'message' => 'Welcome! Please set your password.',
                        'redirect' => BASE_URL . 'admin/first-login?token=' . $securityToken]);
                    exit;
                }

                $staffId = Session::getAdmin('staff_id');
                $staffDataModel = new StaffData();
                $staffLoginModel = new StaffLogin();

                $staff = $staffDataModel->findById($staffId);
                $staffLogin = $staffLoginModel->findByStaffId($staffId);
                $fullName = $staff ? $staff->getFullname() : 'Staff';

                echo json_encode([
                    'success' => true,
                    'message' => 'Welcome back! ' . $fullName,
                    'redirect' => BASE_URL . 'admin/dashboard'
                ]);
                exit;
            }

            echo json_encode($result);
            exit;
        }
    }

    /**
     * Handle admin/staff logout
     */    public function logout()
    {
        AdminAuth::logout();
        header('Location: ' . BASE_URL . 'admin/login?toast_message=' . urlencode('Log out successfully') . '&toast_type=success');
        exit;
    }    public static function requestReset()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $email = Security::sanitize($_POST['email'] ?? '');
            $isStaff = isset($_POST['is_staff']) && $_POST['is_staff'] === '1';

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }

            require_once __DIR__ . '/../lib/VerificationToken.php';
            $token = VerificationTokenLib::createPasswordResetToken($email, $isStaff);
            if (isset($token['error'])) {
                echo json_encode(['success' => false, 'message' => $token['error']]);
                exit;
            }

            if ($token) {
                require_once __DIR__ . '/../lib/Email.php';
                if (Email::sendPasswordReset($email, $token)) {
                    echo json_encode(['success' => true, 'message' => 'Reset link sent to your email']);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
                    exit;
                }
            }

            echo json_encode(['success' => false, 'message' => 'Email not found.']);
            exit;
        }
    }    public static function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($password) || empty($confirmPassword)) {
                echo json_encode(['success' => false, 'message' => 'All fields are required', 'token' => $token]);
                exit;
            }

            if ($password !== $confirmPassword) {
                echo json_encode(['success' => false, 'message' => 'Passwords do not match', 'token' => $token]);
                exit;
            }

            if (!Validator::passwordStrength($password)) {
                echo json_encode(['success' => false, 'message' => 'Password must be 8+ chars with uppercase, lowercase, number, and special character', 'token' => $token]);
                exit;
            }

            require_once __DIR__ . '/../lib/VerificationToken.php';
            if (VerificationTokenLib::resetPassword($token, $password)) {
                echo json_encode(['success' => true, 'message' => 'Password reset successfully', 'redirect' => BASE_URL . 'login']);
                exit;
            }

            echo json_encode(['success' => false, 'message' => 'Invalid or expired reset token']);
            exit;
        }
    }
}
