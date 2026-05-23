<?php
/**
 * Helper Functions
 * Online Hostel Management System
 * 
 * This file contains utility functions used throughout the application
 */

// ===========================
// VALIDATION HELPERS
// ===========================

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    // Uganda phone number format: 0xxx xxx xxx or +256xxx xxx xxx
    $pattern = '/^(\+256|0)[0-9]{9}$/';
    return preg_match($pattern, $phone) === 1;
}

/**
 * Validate password strength
 * Minimum 8 characters, at least 1 uppercase, 1 lowercase, 1 number
 */
function isValidPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
}

/**
 * Validate file upload
 */
function isValidUpload($file, $max_size = null, $allowed_types = null) {
    global $conn;
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload error'];
    }
    
    $max_size = $max_size ?? MAX_UPLOAD_SIZE;
    $allowed_types = $allowed_types ?? ALLOWED_UPLOAD_TYPES;
    
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size exceeds maximum allowed'];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['valid' => false, 'error' => 'File type not allowed'];
    }
    
    return ['valid' => true];
}

/**
 * Sanitize string input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}


// ===========================
// CSRF TOKEN HELPERS
// ===========================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token for form
 */
function getCSRFTokenInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}


// ===========================
// PASSWORD HELPERS
// ===========================

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}


// ===========================
// SESSION HELPERS
// ===========================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . 'auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        die('Access Denied: Admin privileges required');
    }
}

/**
 * Get current logged in user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}


// ===========================
// BOOKING HELPERS
// ===========================

/**
 * Generate unique booking code
 */
function generateBookingCode() {
    return strtoupper(substr(uniqid(), -8)) . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Generate receipt code
 */
function generateReceiptCode() {
    return 'RCP-' . date('Ym') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}

/**
 * Generate QR code data
 */
function generateQRCodeData($booking_code, $booking_id) {
    return "Booking:$booking_code|ID:$booking_id|Date:" . date('Y-m-d') . "|URL:" . APP_URL . "receipt.php?booking_id=$booking_id";
}


// ===========================
// DATE & TIME HELPERS
// ===========================

/**
 * Format date
 */
function formatDate($date, $format = 'Y-m-d') {
    if ($date instanceof DateTime) {
        return $date->format($format);
    }
    return date($format, strtotime($date));
}

/**
 * Get human readable date
 */
function humanReadableDate($date) {
    $timestamp = strtotime($date);
    $days_ago = (time() - $timestamp) / 86400;
    
    if ($days_ago < 1) {
        return 'Today';
    } elseif ($days_ago < 2) {
        return 'Yesterday';
    } elseif ($days_ago < 7) {
        return round($days_ago) . ' days ago';
    } else {
        return formatDate($date, 'M d, Y');
    }
}

/**
 * Check if date is in the past
 */
function isDateInPast($date) {
    return strtotime($date) < time();
}

/**
 * Check if date is in the future
 */
function isDateInFuture($date) {
    return strtotime($date) > time();
}


// ===========================
// MONEY HELPERS
// ===========================

/**
 * Format currency
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

/**
 * Parse currency (remove symbols and spaces)
 */
function parseCurrency($amount) {
    return (float) str_replace([CURRENCY_SYMBOL, ',', ' '], '', $amount);
}


// ===========================
// DATABASE HELPERS
// ===========================

/**
 * Get single row from database
 */
function getRow($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logMessage("SQL Error: " . $conn->error, 'error');
        return null;
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get multiple rows from database
 */
function getRows($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logMessage("SQL Error: " . $conn->error, 'error');
        return [];
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Get single value from database
 */
function getValue($sql, $params = []) {
    $row = getRow($sql, $params);
    if ($row) {
        return array_shift($row);
    }
    return null;
}

/**
 * Execute insert/update/delete query
 */
function executeQuery($sql, $params = []) {
    global $conn;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        logMessage("SQL Error: " . $conn->error, 'error');
        return false;
    }
    
    if (!empty($params)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) $types .= 'i';
            elseif (is_float($param)) $types .= 'd';
            else $types .= 's';
        }
        $stmt->bind_param($types, ...$params);
    }
    
    return $stmt->execute();
}

/**
 * Get last inserted ID
 */
function getLastInsertId() {
    global $conn;
    return $conn->insert_id;
}


// ===========================
// RESPONSE HELPERS
// ===========================

/**
 * Redirect with message
 */
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $location);
    exit;
}

/**
 * Show alert message
 */
function showAlert($message, $type = 'success') {
    $classes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $classes[$type] ?? 'alert-info';
    echo '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">';
    echo htmlspecialchars($message);
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

/**
 * Get and clear alert message
 */
function getAlertMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'success';
        $message = $_SESSION['message'];
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}


// ===========================
// FILE HELPERS
// ===========================

/**
 * Upload file
 */
function uploadFile($file, $upload_dir, $allowed_types = null) {
    $allowed_types = $allowed_types ?? ALLOWED_UPLOAD_TYPES;
    
    // Validate
    $validation = isValidUpload($file, MAX_UPLOAD_SIZE, $allowed_types);
    if (!$validation['valid']) {
        return ['success' => false, 'error' => $validation['error']];
    }
    
    // Create directory if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('file_') . '.' . $ext;
    $filepath = $upload_dir . '/' . $filename;
    
    // Move file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    } else {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }
}

/**
 * Delete file
 */
function deleteFile($filepath) {
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    return false;
}


// ===========================
// STRING HELPERS
// ===========================

/**
 * Truncate string
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length) . $suffix;
}

/**
 * Generate slug from string
 */
function slugify($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}


// ===========================
// NUMBER HELPERS
// ===========================

/**
 * Generate random number
 */
function randomNumber($length = 6) {
    return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

/**
 * Format number with thousand separator
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, '.', ',');
}

?>
