<?php
require_once __DIR__ . '/../lib/Database.php';
require_once __DIR__ . '/../entity/VerificationToken.php';
require_once __DIR__ . '/../lib/IDGenerator.php';

class VerificationToken extends Database
{
    public function findByToken($token, $type)
    {
        $sql = "SELECT * FROM verification_tokens WHERE token = ? AND type = ? AND expires_at > NOW()";
        $row = $this->query($sql, [$token, $type])->fetch();
        return $row ? $row : null;
    }

    public function create($user_id, $staff_id, $type, $token, $expiryHours)
    {
        $token_id = IDGenerator::verificationTokenID();
        $sql = "INSERT INTO verification_tokens (token_id, user_id, staff_id, type, token, expires_at) 
                VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))";
        $this->query($sql, [$token_id, $user_id, $staff_id, $type, $token, $expiryHours]);
    }

    public function deleteByUserId($user_id, $type)
    {
        $sql = "DELETE FROM verification_tokens WHERE user_id = ? AND type = ?";
        $this->query($sql, [$user_id, $type]);
    }

    public function deleteByStaffId($staff_id, $type)
    {
        $sql = "DELETE FROM verification_tokens WHERE staff_id = ? AND type = ?";
        $this->query($sql, [$staff_id, $type]);
    }

    public function deleteByToken($token)
    {
        $sql = "DELETE FROM verification_tokens WHERE token = ?";
        $this->query($sql, [$token]);
    }

    public function getLastCreatedTime($user_id, $staff_id, $type)
    {
        $sql = "SELECT created_date FROM verification_tokens 
                WHERE (user_id = ? OR staff_id = ?) AND type = ? 
                ORDER BY created_date DESC LIMIT 1";
        $row = $this->query($sql, [$user_id, $staff_id, $type])->fetch();
        return $row ? $row['created_date'] : null;
    }

    // Staff-specific token methods
    public function generateStaffToken($staff_id, $type)
    {
        // Check cooldown
        $lastCreated = $this->getStaffLastCreatedTime($staff_id, $type);
        if ($lastCreated) {
            $timeDiff = time() - strtotime($lastCreated);
            if ($timeDiff < 60) { // 60 second cooldown
                return false;
            }
        }

        // Delete existing tokens
        $this->blockStaffToken($staff_id, $type);

        // Generate new token
        $token = bin2hex(random_bytes(32));
        $token_id = IDGenerator::verificationTokenID();
        $expiryHours = $type === 'password_reset' ? 1 : 24;
        
        $sql = "INSERT INTO verification_tokens (token_id, staff_id, type, token, expires_at) 
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? HOUR))";
        $this->query($sql, [$token_id, $staff_id, $type, $token, $expiryHours]);
        
        return $token;
    }

    public function verifyStaffToken($token, $type)
    {
        $sql = "SELECT * FROM verification_tokens WHERE token = ? AND type = ? AND staff_id IS NOT NULL AND expires_at > NOW()";
        $row = $this->query($sql, [$token, $type])->fetch();
        return $row ? $row : null;
    }

    public function blockStaffToken($staff_id, $type)
    {
        $sql = "DELETE FROM verification_tokens WHERE staff_id = ? AND type = ?";
        $this->query($sql, [$staff_id, $type]);
    }

    public function getStaffLastCreatedTime($staff_id, $type)
    {
        $sql = "SELECT created_date FROM verification_tokens 
                WHERE staff_id = ? AND type = ? 
                ORDER BY created_date DESC LIMIT 1";
        $row = $this->query($sql, [$staff_id, $type])->fetch();
        return $row ? $row['created_date'] : null;
    }
}
