<?php

class VerificationTokenEntity
{
    private $token_id;
    private $user_id;
    private $staff_id;
    private $type;
    private $token;
    private $expires_at;
    private $created_date;

    public function __construct($data)
    {
        $this->token_id = $data['token_id'] ?? null;
        $this->user_id = $data['user_id'] ?? null;
        $this->staff_id = $data['staff_id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->token = $data['token'] ?? null;
        $this->expires_at = $data['expires_at'] ?? null;
        $this->created_date = $data['created_date'] ?? null;
    }

    public function getTokenId() { return $this->token_id; }
    public function getUserId() { return $this->user_id; }
    public function getStaffId() { return $this->staff_id; }
    public function getType() { return $this->type; }
    public function getToken() { return $this->token; }
    public function getExpiresAt() { return $this->expires_at; }
    public function getCreatedDate() { return $this->created_date; }
}
