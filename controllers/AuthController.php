<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/UserData.php';
require_once __DIR__ . '/../models/UserLogin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/VerificationToken.php';
require_once __DIR__ . '/../lib/IDGenerator.php';
require_once __DIR__ . '/../lib/Recaptcha.php';

class AuthController
{
    public function showLogin()
    {
        require __DIR__ . '/../views/pages/login.php';
    }

    public function showRegister()
    {
        require __DIR__ . '/../views/pages/register.php';
    }

    public function showForgotPassword()
    {
        require __DIR__ . '/../views/pages/forgot_password.php';
    }
      public static function register()
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

            $username = Security::sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $fullname = Security::sanitize($_POST['fullname'] ?? '');
            $email = Security::sanitize($_POST['email'] ?? '');
            $contactPrefix = Security::sanitize($_POST['contact_number_prefix'] ?? '+60');
            $contactNumber = Security::sanitize($_POST['contact_number'] ?? '');
            $contact = !empty($contactNumber) ? $contactPrefix . '-' . $contactNumber : null;
            $birthDate = $_POST['birth_date'] ?? null;
            $gender = $_POST['gender'] ?? '';

            // Username validation
            if (strlen($username) < 3 || strlen($username) > 50) {
                echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters']);
                exit;
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                echo json_encode(['success' => false, 'message' => 'Username can only contain letters, numbers, and underscores']);
                exit;
            }

            // Fullname validation (match profile validation)
            $fullnameTrimmed = trim($fullname);
            if ($fullnameTrimmed === '') {
                echo json_encode(['success' => false, 'message' => 'Full name is required']);
                exit;
            }
            if (mb_strlen($fullnameTrimmed) < 3) {
                echo json_encode(['success' => false, 'message' => 'Full name must be at least 3 characters long']);
                exit;
            }
            if (mb_strlen($fullnameTrimmed) > 100) {
                echo json_encode(['success' => false, 'message' => 'Full name must not exceed 100 characters']);
                exit;
            }
            if (!preg_match("/^[a-zA-Z\\s\\-]+$/", $fullnameTrimmed)) {
                echo json_encode(['success' => false, 'message' => 'Full name can only contain letters, spaces, and hyphens']);
                exit;
            }

            // Email validation
            $emailTrimmed = trim($email);
            if ($emailTrimmed === '') {
                echo json_encode(['success' => false, 'message' => 'Email is required']);
                exit;
            }
            if (mb_strlen($emailTrimmed) > 100) {
                echo json_encode(['success' => false, 'message' => 'Email must not exceed 100 characters']);
                exit;
            }

            if (
                !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $emailTrimmed) ||
                str_contains($emailTrimmed, '..') ||
                str_starts_with($emailTrimmed, '.') ||
                str_ends_with($emailTrimmed, '.')
            ) {
                echo json_encode(['success' => false, 'message' => 'Email address format is invalid']);
                exit;
            }

            // Contact number validation (optional)
            if ($contactNumber !== '' && !preg_match('/^[0-9]{7,15}$/', $contactNumber)) {
                echo json_encode(['success' => false, 'message' => 'Contact number must be between 7 and 15 digits']);
                exit;
            }

            // Birth date validation (optional)
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
                    echo json_encode(['success' => false, 'message' => 'You must be at least 13 years old']);
                    exit;
                }
            }

            // Password validation
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required']);
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
            $userLoginModel = new UserLogin();
            $userDataModel = new UserData();

            if ($userLoginModel->usernameExists($username)) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
                exit;
            }

            if ($userDataModel->emailExists($email)) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }

            try {
                $userId = IDGenerator::userID();
                $loginId = IDGenerator::userLoginID();
                $userDataModel->create($userId, $fullnameTrimmed, $emailTrimmed, $contact, $birthDate, $gender);
                $userLoginModel->create($loginId, $userId, $username, Security::hashPassword($password), true);

                require_once __DIR__ . '/../lib/VerificationToken.php';
                $token = VerificationTokenLib::createEmailVerificationToken($emailTrimmed);
                if ($token && !isset($token['error'])) {
                    require_once __DIR__ . '/../lib/Email.php';
                    Email::sendEmailVerification($emailTrimmed, $token);
                }

                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Please check your email to verify your account.',
                    'registered_email' => $emailTrimmed
                ]);
                exit;
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
                exit;
            }
        }    }
    public static function login()
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

                // Find user by email and get username
                $userDataModel = new UserData();
                $user = $userDataModel->findByEmail($usernameOrEmail);

                if (!$user) {
                    echo json_encode(['success' => false, 'message' => 'Invalid email']);
                    exit;
                }

                // Get username from user_login table
                $userLoginModel = new UserLogin();
                $userLogin = $userLoginModel->findByUserId($user->getUserId());

                if (!$userLogin) {
                    echo json_encode(['success' => false, 'message' => 'Invalid username']);
                    exit;
                }

                $username = $userLogin->getUsername();
            } else {
                // Validate username using Validator
                if (!Validator::username($usernameOrEmail)) {
                    echo json_encode(['success' => false, 'message' => 'Username must be 3-50 characters and contain only letters, numbers, and underscores']);
                    exit;
                }

                $username = $usernameOrEmail;
            }

            $result = Auth::attempt($username, $password);

            if ($result['success']) {
                if (isset($result['first_login'])) {
                    $securityToken = $result['security_token'];
                    echo json_encode([
                        'success' => true,
                        'message' => 'Welcome! Please set your password.',
                        'redirect' => BASE_URL . 'first-login?token=' . $securityToken
                    ]);
                    exit;
                }

                if (isset($_POST['remember_me'])) {
                    Auth::setRememberMe(Session::get('user_id'));
                }

                $userId = Session::get('user_id');
                $userDataModel = new UserData();
                $userLoginModel = new UserLogin();

                $user = $userDataModel->findById($userId);
                $userLogin = $userLoginModel->findByUserId($userId);
                $fullName = $user ? $user->getFullname() : 'User';

                echo json_encode([
                    'success' => true,
                    'message' => 'Welcome back! ' . $fullName,
                    'redirect' => BASE_URL
                ]);
                exit;
            }

            echo json_encode($result);
            exit;
        }
    }
    public static function logout()
    {
        Auth::logout();
        header('Location: ' . BASE_URL . 'login?toast_message=' . urlencode('Log out successfully') . '&toast_type=success');
        exit;    }
    public static function requestReset()
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

    public static function verifyEmail()
    {
        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');

            $token = $_GET['token'] ?? '';
            if (empty($token)) {
                echo json_encode(['success' => false, 'message' => 'Invalid verification link']);
                exit;
            }

            require_once __DIR__ . '/../lib/VerificationToken.php';
            if (VerificationTokenLib::verifyEmail($token)) {
                echo json_encode(['success' => true, 'message' => 'Email verified successfully! You can now login.', 'redirect' => BASE_URL . 'login']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired verification link']);
            }
            exit;
        }

        // Regular browser request - show verification page
        $token = $_GET['token'] ?? '';
        require __DIR__ . '/../views/pages/verify_email.php';    }
    public static function resendVerification()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');

            if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
                exit;
            }

            $email = Security::sanitize($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }

            // Check if email is already verified
            $userDataModel = new UserData();
            $user = $userDataModel->findByEmail($email);
            if ($user) {
                $userLoginModel = new UserLogin();
                $userLogin = $userLoginModel->findByUserId($user->getUserId());
                if ($userLogin && $userLogin->getEmailVerified()) {
                    echo json_encode(['success' => true, 'message' => 'Email verified successfully! You can now login.', 'redirect' => BASE_URL . 'login']);
                    exit;
                }
            }

            require_once __DIR__ . '/../lib/VerificationToken.php';
            $token = VerificationTokenLib::createEmailVerificationToken($email);

            if (isset($token['error'])) {
                echo json_encode(['success' => false, 'message' => $token['error']]);
                exit;
            }

            if ($token) {
                require_once __DIR__ . '/../lib/Email.php';
                if (Email::sendEmailVerification($email, $token)) {
                    echo json_encode(['success' => true, 'message' => 'Verification email sent! Please check your inbox.', 'registered_email' => $email]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to send email. Please try again.']);
                    exit;
                }
            }

            echo json_encode(['success' => false, 'message' => 'Email not found.']);
            exit;
        }
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'register') {
        AuthController::register();
    } elseif ($_GET['action'] === 'login') {
        AuthController::login();
    } elseif ($_GET['action'] === 'logout') {
        AuthController::logout();
    } elseif ($_GET['action'] === 'request_reset') {
        AuthController::requestReset();
    } elseif ($_GET['action'] === 'verify_email') {
        AuthController::verifyEmail();
    } elseif ($_GET['action'] === 'resend_verification') {
        AuthController::resendVerification();
    } elseif ($_GET['action'] === 'reset_password') {
        AuthController::resetPassword();
    }
}
