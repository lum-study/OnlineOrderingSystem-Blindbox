<?php
require_once __DIR__ . '/../models/UserData.php';
require_once __DIR__ . '/../models/StaffData.php';
require_once __DIR__ . '/../models/UserLogin.php';
require_once __DIR__ . '/../models/StaffLogin.php';
require_once __DIR__ . '/../models/VerificationToken.php';

class VerificationTokenLib {
    const PASSWORD_RESET_EXPIRY = 1; // 1 hour
    const EMAIL_VERIFICATION_EXPIRY = 24; // 24 hours
    const RESEND_COOLDOWN = 60; // 60 seconds

    public static function createPasswordResetToken($email, $isStaff = false) {
        return self::createToken($email, $isStaff, 'password_reset', self::PASSWORD_RESET_EXPIRY);
    }

    public static function createEmailVerificationToken($email, $isStaff = false, $expiryHours = null) {
        $hours = $expiryHours ?? self::EMAIL_VERIFICATION_EXPIRY;
        return self::createToken($email, $isStaff, 'email_verification', $hours);
    }

    private static function createToken($email, $isStaff, $type, $expiryHours) {
        $userId = null;
        $staffId = null;
        
        if ($isStaff) {
            $staff = Database::fetch("SELECT staff_id FROM staff_data WHERE email = ? AND is_deleted = 0", [$email]);
            if (!$staff) return false;
            $staffId = $staff['staff_id'];
        } else {
            $userDataModel = new UserData();
            $user = $userDataModel->findByEmail($email);
            if (!$user) return false;
            $userId = $user->getUserId();
        }

        // Rate limiting check
        $tokenModel = new VerificationToken();
        $lastCreated = $tokenModel->getLastCreatedTime($userId, $staffId, $type);
        if ($lastCreated) {
            $timeDiff = time() - strtotime($lastCreated);
            if ($timeDiff < self::RESEND_COOLDOWN) {
                return ['error' => 'Please wait ' . (self::RESEND_COOLDOWN - $timeDiff) . ' seconds before requesting again'];
            }
        }

        $token = bin2hex(random_bytes(32));
        
        if ($isStaff) {
            $tokenModel->deleteByStaffId($staffId, $type);
        } else {
            $tokenModel->deleteByUserId($userId, $type);
        }
        $tokenModel->create($userId, $staffId, $type, $token, $expiryHours);
        
        return $token;
    }

    public static function verifyPasswordResetToken($token) {
        $tokenModel = new VerificationToken();
        return $tokenModel->findByToken($token, 'password_reset') ?: false;
    }

    public static function verifyEmailVerificationToken($token) {
        $tokenModel = new VerificationToken();
        return $tokenModel->findByToken($token, 'email_verification') ?: false;
    }

    public static function resetPassword($token, $newPassword) {
        $tokenModel = new VerificationToken();
        $reset = $tokenModel->findByToken($token, 'password_reset');
        if (!$reset) return false;
        
        $hashedPassword = Security::hashPassword($newPassword);
        
        if ($reset['staff_id']) {
            $staffLoginModel = new StaffLogin();
            $staffLoginModel->updatePassword($reset['staff_id'], $hashedPassword);
        } else {
            $userLoginModel = new UserLogin();
            $userLoginModel->updatePassword($reset['user_id'], $hashedPassword);
        }
        
        $tokenModel->deleteByToken($token);
        return true;
    }

    public static function verifyEmail($token) {
        $tokenModel = new VerificationToken();
        $verification = $tokenModel->findByToken($token, 'email_verification');
        if (!$verification) return false;

        if ($verification['staff_id']) {
            Database::query("UPDATE staff_logins SET email_verified = 1 WHERE staff_id = ?", [$verification['staff_id']]);
        } else {
            Database::query("UPDATE user_logins SET email_verified = 1 WHERE user_id = ?", [$verification['user_id']]);
        }

        $tokenModel->deleteByToken($token);
        return true;
    }

    public static function cleanExpiredTokens() {
        Database::query("DELETE FROM verification_tokens WHERE expires_at < NOW()");
    }
}
