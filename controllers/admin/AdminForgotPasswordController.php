<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ . '/../../models/StaffData.php';
require_once __DIR__ . '/../../models/StaffLogin.php';
require_once __DIR__ . '/../../lib/VerificationToken.php';
require_once __DIR__ . '/../../lib/Email.php';

class AdminForgotPasswordController
{
    public function showForgotPassword()
    {
        require __DIR__ . '/../../views/pages/admin/forgot_password.php';
    }

    public function showResetPassword()
    {
        $token = $_GET['token'] ?? '';

        if (empty($token)) {
            header('Location: ' . BASE_URL . 'admin/login');
            exit;
        }

        // Verify token exists and is valid
        $verificationTokenModel = new VerificationToken();
        $tokenData = $verificationTokenModel->verifyStaffToken($token, 'password_reset');

        if (!$tokenData) {
            $_SESSION['error_message'] = 'Invalid or expired reset link. Please request a new one.';
            header('Location: ' . BASE_URL . 'admin/forgot-password');
            exit;
        }

        require __DIR__ . '/../../views/pages/admin/reset_password.php';
    }

    public function requestReset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

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

        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }

        if (!Validator::email($email)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }

        try {
            $staffDataModel = new StaffData();
            $staff = $staffDataModel->findByEmail($email);

            // Always return success to prevent email enumeration
            if (!$staff) {
                echo json_encode([
                    'success' => true,
                    'message' => 'If an account with that email exists, a password reset link has been sent.'
                ]);
                exit;
            }

            // Check if account is blocked
            if ($staff->isBlocked()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'If an account with that email exists, a password reset link has been sent.'
                ]);
                exit;
            }

            // Generate verification token
            $verificationTokenModel = new VerificationToken();
            $token = $verificationTokenModel->generateStaffToken($staff->getStaffId(), 'password_reset');

            if (!$token) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Please wait before requesting another reset link.'
                ]);
                exit;
            }

            // Send reset email
            $resetLink = 'http://localhost' . BASE_URL . 'admin/reset-password?token=' . $token;
            $sent = Email::sendAdminPasswordReset($email, $staff->getFullname(), $resetLink);

            if ($sent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password reset link has been sent to your email.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to send email. Please try again later.'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred. Please try again.'
            ]);
        }
        exit;
    }

    public function resetPassword()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        header('Content-Type: application/json');

        if (!Security::verifyCSRF($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh and try again.']);
            exit;
        }

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($password) || empty($confirmPassword)) {
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
            $verificationTokenModel = new VerificationToken();
            $tokenData = $verificationTokenModel->verifyStaffToken($token, 'password_reset');

            if (!$tokenData) {
                echo json_encode(['success' => false, 'message' => 'Invalid or expired reset link']);
                exit;
            }

            $staffId = $tokenData['staff_id'];

            // Update password
            $staffLoginModel = new StaffLogin();
            $hashedPassword = Security::hashPassword($password);
            $staffLoginModel->updatePassword($staffId, $hashedPassword);

            // Delete used token
            $verificationTokenModel->blockStaffToken($staffId, 'password_reset');

            echo json_encode([
                'success' => true,
                'message' => 'Password has been reset successfully',
                'redirect' => BASE_URL . 'admin/login'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error resetting password. Please try again.'
            ]);
        }
        exit;
    }
}
