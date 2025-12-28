<?php
// config.php
// Database connection (PDO), constants, and basic settings.

// Database constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'online_shopping_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default
define('DB_CHARSET', 'utf8mb4');

define('BASE_URL', '/online_shopping_system/');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');

define('ASSETS_DIR', __DIR__ . '/../assets/');

// reCAPTCHA keys
define('RECAPTCHA_SITE_KEY', 'YOUR_RECAPTCHA_SITE_KEY');
define('RECAPTCHA_SECRET_KEY', 'YOUR_RECAPTCHA_SECRET_KEY');

// --- Lockout policy ---
// Maximum number of failed login attempts before temporary lock
define('MAX_FAILED_ATTEMPTS', 3);
// Duration of temporary lock (in minutes)
define('LOCKOUT_DURATION_MINUTES', 5);

// Email SMTP settings
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'YOUR_LOGIN_EMAIL'); // Login email
define('SMTP_PASSWORD', 'YOUR_GMAIL_APP_PASSWORD'); // Gmail app password
define('SMTP_FROM_EMAIL', 'YOUR_SENDER_EMAIL'); // Sender email
define('SMTP_FROM_NAME', 'BlindeDoos');
define('SMTP_DEBUG', 0); // 0=off, 1=client, 2=server

// PDO connection function
function getPDO()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            // In production, don't expose error details
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}
