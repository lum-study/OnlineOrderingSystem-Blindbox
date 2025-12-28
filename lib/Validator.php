<?php
class Validator {
    private $errors = [];

    public function validate($data, $rules) {
        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? '';
            $ruleList = explode('|', $ruleSet);
            
            foreach ($ruleList as $rule) {
                if ($rule === 'required' && empty($value)) {
                    $this->errors[$field] = ucfirst($field) . ' is required';
                    break;
                }
                if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[$field] = 'Invalid email format';
                }
                if (strpos($rule, 'min:') === 0) {
                    $min = (int)substr($rule, 4);
                    if (strlen($value) < $min) {
                        $this->errors[$field] = ucfirst($field) . " must be at least $min characters";
                    }
                }
                if (strpos($rule, 'max:') === 0) {
                    $max = (int)substr($rule, 4);
                    if (strlen($value) > $max) {
                        $this->errors[$field] = ucfirst($field) . " must not exceed $max characters";
                    }
                }
            }
        }
        return empty($this->errors);
    }

    public function errors() {
        return $this->errors;
    }

    public static function passwordStrength($password) {
        if (strlen($password) < 8) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
        return true;
    }

    public static function email($email) {
        // Validate email format
        if (mb_strlen($email) > 100) return false;
        
        if (
            !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email) ||
            str_contains($email, '..') ||
            str_starts_with($email, '.') ||
            str_ends_with($email, '.')
        ) {
            return false;
        }
        
        return true;
    }

    public static function username($username) {
        // Validate username: 3-50 characters, letters, numbers, and underscores only
        if (strlen($username) < 3 || strlen($username) > 50) return false;
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) return false;
        return true;
    }
}
