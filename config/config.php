<?php
/**
 * Application Configuration File
 * Online Hostel Management System
 * 
 * WARNING: This file contains sensitive information
 * ngrok http 80
 * Amenities filter on rooms page
SMS/Email reminders (infrastructure exists but not wired up)
Per-student double-booking check (room-level prevention works, but a student can book multiple rooms simultaneously)
Admin QR check-in scanner page
Dashboard payment total is hardcoded to 0

 */

// ===========================
// DATABASE CONFIGURATION
// ===========================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'hostel_management');
define('DB_PORT', 3306);

// ===========================
// APPLICATION CONFIGURATION
// ===========================
define('APP_NAME', 'Pearls of Wisdom Hostel Management System');
define('APP_URL', 'https://whisking-stoppable-appendage.ngrok-free.dev/online/public/');
define('APP_ENV', 'production'); // development or production
define('APP_DEBUG', false);
define('APP_TIMEZONE', 'Africa/Kampala');

// ===========================
// PATHS
// ===========================
define('BASE_PATH', dirname(dirname(__FILE__)));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('LOGS_PATH', BASE_PATH . '/logs');
define('VIEWS_PATH', BASE_PATH . '/views');
define('CONFIG_PATH', BASE_PATH . '/config');

// ===========================
// SECURITY CONFIGURATION
// ===========================
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);

// Session cookie configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (APP_ENV === 'production') {
    ini_set('session.cookie_secure', 1);
}

// ===========================
// PAYMENT CONFIGURATION — PESAPAL v3
// ===========================
define('PAYMENT_GATEWAY',  'pesapal');
define('PAYMENT_MODE', 'live'); // switch to 'sandbox' only with valid sandbox credentials

// PesaPal credentials — FortExpress LIVE account
define('PESAPAL_CONSUMER_KEY',    'AjXemdmnd39Ah5QZEP6dUq4Cbwvg+23F');
define('PESAPAL_CONSUMER_SECRET', '7h0pC1NQSdClHG1XUsx1dDix0Wk=');

// Previous credentials (keep for reference)
// define('PESAPAL_CONSUMER_KEY',    'WladF7f2VLuIhN/kjFw9vdpLJy7rWUeH');
// define('PESAPAL_CONSUMER_SECRET', 'EcZCXnJcGskMCTrqJyBojVhyUec=');

// API base URLs
define('PESAPAL_BASE_URL',
    PAYMENT_MODE === 'live'
        ? 'https://pay.pesapal.com/v3'
        : 'https://cybqa.pesapal.com/pesapalv3'
);

// Callback URLs (must be publicly accessible for PesaPal to reach them)
define('PESAPAL_IPN_URL',      APP_URL . 'payment/callback.php');
define('PESAPAL_REDIRECT_URL', APP_URL . 'payment/status.php');

// Legacy alias
define('PAYMENT_CALLBACK_URL', PESAPAL_REDIRECT_URL);

// ===========================
// EMAIL CONFIGURATION
// ===========================
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'wasswaatibu0@gmail.com');
define('MAIL_PASSWORD', 'yeoytyamljuerjas');
define('MAIL_FROM', 'wasswaatibu0@gmail.com');
define('MAIL_FROM_NAME', APP_NAME);
define('MAIL_ENCRYPTION', 'tls');

// ===========================
// SMS CONFIGURATION
// ===========================
define('SMS_ENABLED', false);
define('SMS_PROVIDER', 'africastalking'); // africastalking or similar
define('SMS_API_KEY', 'your_sms_api_key');
define('SMS_FROM', 'PearlsWisdom');

// ===========================
// FILE UPLOAD CONFIGURATION
// ===========================
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_UPLOAD_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_UPLOAD_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// ===========================
// PAGINATION
// ===========================
define('ITEMS_PER_PAGE', 10);
define('ADMIN_ITEMS_PER_PAGE', 20);

// ===========================
// CURRENCY & PRICING
// ===========================
define('CURRENCY_SYMBOL', 'UGX');
define('CURRENCY_CODE', 'UGX');
define('CURRENCY_FORMAT', '%.2f');

// ===========================
// ERROR HANDLING
// ===========================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// ===========================
// DATE & TIME CONFIGURATION
// ===========================
date_default_timezone_set(APP_TIMEZONE);

// ===========================
// DATABASE CONNECTION
// ===========================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Error: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// ===========================
// HELPER FUNCTION: Log Messages
// ===========================
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $log_file = LOGS_PATH . '/' . $type . '.log';
    $log_message = "[$timestamp] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// ===========================
// HELPER FUNCTION: Start Session
// ===========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    
    // Check session timeout
    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: ' . APP_URL . 'auth/login.php?timeout=1');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

?>
