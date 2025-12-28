<?php

class UserLoginEntity
{
    private $login_id;
    private $user_id;
    private $username;
    private $password;
    private $username_changed_at;
    private $failed_attempts;
    private $locked_until;
    private $last_login;
    private $remember_token;
    private $email_verified;
    private $verification_token;
    private $is_deleted;

    public function __construct($data)
    {
        $this->login_id = $data['login_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->password = $data['password'] ?? null;
        $this->username_changed_at = $data['username_changed_at'] ?? null;
        $this->failed_attempts = $data['failed_attempts'] ?? 0;
        $this->locked_until = $data['locked_until'] ?? null;
        $this->last_login = $data['last_login'] ?? null;
        $this->remember_token = $data['remember_token'] ?? null;
        $this->email_verified = $data['email_verified'] ?? 0;
        $this->verification_token = $data['verification_token'] ?? null;
        $this->is_deleted = $data['is_deleted'] ?? 0;
    }

    public function getLoginId() { return $this->login_id; }
    public function getUserId() { return $this->user_id; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getUsernameChangedAt() { return $this->username_changed_at; }
    public function getFailedAttempts() { return $this->failed_attempts; }
    public function getLockedUntil() { return $this->locked_until; }
    public function getLastLogin() { return $this->last_login; }
    public function getRememberToken() { return $this->remember_token; }
    public function getEmailVerified() { return $this->email_verified; }
    public function isEmailVerified() { return $this->email_verified; }
    public function getVerificationToken() { return $this->verification_token; }
    public function isDeleted() { return $this->is_deleted; }
}
