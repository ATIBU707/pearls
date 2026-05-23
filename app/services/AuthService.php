<?php
/**
 * AuthService - Handles all authentication logic
 * Online Hostel Management System
 */

require_once APP_PATH . '/models/User.php';
require_once APP_PATH . '/helpers/functions.php';

class AuthService {

    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // ===========================
    // REGISTRATION
    // ===========================

    /**
     * Register a new student
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public function register($data) {
        $errors = $this->validateRegistration($data);
        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        // Check duplicates
        if ($this->userModel->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'An account with that email already exists.'];
        }
        if (!empty($data['phone_number']) && $this->userModel->phoneExists($data['phone_number'])) {
            return ['success' => false, 'message' => 'That phone number is already registered.'];
        }
        if (!empty($data['student_id'])) {
            $existing = $this->userModel->getByStudentId($data['student_id']);
            if ($existing) {
                return ['success' => false, 'message' => 'That student ID is already registered.'];
            }
        }

        // Build user data
        $userData = [
            'email'                 => sanitizeEmail($data['email']),
            'first_name'            => sanitize($data['first_name']),
            'last_name'             => sanitize($data['last_name']),
            'phone_number'          => sanitize($data['phone_number'] ?? ''),
            'student_id'            => sanitize($data['student_id'] ?? ''),
            'identification_type'   => $data['identification_type'] ?? 'student_id',
            'identification_number' => sanitize($data['identification_number'] ?? ''),
            'password'              => $data['password'],
            'role'                  => 'student',
            'is_active'             => 1,
            'email_verified'        => 0,
        ];

        $user_id = $this->userModel->create($userData);

        if (!$user_id) {
            logMessage("Registration failed for email: " . $data['email'], 'error');
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }

        logMessage("New student registered: {$data['email']} (ID: $user_id)", 'activity');
        return ['success' => true, 'message' => 'Registration successful! You can now log in.', 'user_id' => $user_id];
    }

    // ===========================
    // LOGIN
    // ===========================

    /**
     * Authenticate user and create session
     * @return array ['success' => bool, 'message' => string, 'redirect' => string|null]
     */
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required.'];
        }

        $user = $this->userModel->verifyPassword(sanitizeEmail($email), $password);

        if (!$user) {
            logMessage("Failed login attempt for: $email", 'activity');
            return ['success' => false, 'message' => 'Invalid email or password.'];
        }

        // Create session
        $this->createSession($user);

        $redirect = ($user['role'] === 'admin')
            ? APP_URL . 'admin/index.php'
            : APP_URL . 'dashboard.php';

        logMessage("User logged in: {$user['email']} (ID: {$user['user_id']})", 'activity');
        return ['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect];
    }

    // ===========================
    // LOGOUT
    // ===========================

    /**
     * Destroy session and log out
     */
    public function logout() {
        if (isLoggedIn()) {
            logMessage("User logged out: ID " . getCurrentUserId(), 'activity');
        }
        session_unset();
        session_destroy();
    }

    // ===========================
    // PASSWORD RESET
    // ===========================

    /**
     * Initiate forgot-password flow
     * Stores a reset token in session (for demo; production would email a link)
     */
    public function forgotPassword($email) {
        if (empty($email) || !isValidEmail($email)) {
            return ['success' => false, 'message' => 'Please enter a valid email address.'];
        }

        $user = $this->userModel->getByEmail(sanitizeEmail($email));

        // Always return success to prevent email enumeration
        if (!$user || !$user['is_active']) {
            return ['success' => true, 'message' => 'If that email is registered, you will receive reset instructions.'];
        }

        // Generate token (in production this would be emailed; here we store in session for demo)
        $token = bin2hex(random_bytes(32));
        $_SESSION['pwd_reset_token']   = $token;
        $_SESSION['pwd_reset_user_id'] = $user['user_id'];
        $_SESSION['pwd_reset_expiry']  = time() + 3600; // 1 hour

        logMessage("Password reset requested for: $email", 'activity');

        // In production: send email with link. Here we return token for demo.
        return [
            'success' => true,
            'message' => 'Password reset link has been sent to your email.',
            'token'   => $token  // Remove in production
        ];
    }

    /**
     * Reset password using token
     */
    public function resetPassword($token, $new_password, $confirm_password) {
        $errors = [];

        if (empty($token) || empty($_SESSION['pwd_reset_token']) || !hash_equals($_SESSION['pwd_reset_token'], $token)) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }

        if ($_SESSION['pwd_reset_expiry'] < time()) {
            unset($_SESSION['pwd_reset_token'], $_SESSION['pwd_reset_user_id'], $_SESSION['pwd_reset_expiry']);
            return ['success' => false, 'message' => 'Reset token has expired. Please request a new one.'];
        }

        if (empty($new_password) || !isValidPassword($new_password)) {
            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and a number.';
        }

        if ($new_password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'message' => implode('<br>', $errors)];
        }

        $user_id = $_SESSION['pwd_reset_user_id'];
        $result  = $this->userModel->updatePassword($user_id, $new_password);

        if (!$result) {
            return ['success' => false, 'message' => 'Failed to update password. Please try again.'];
        }

        unset($_SESSION['pwd_reset_token'], $_SESSION['pwd_reset_user_id'], $_SESSION['pwd_reset_expiry']);
        logMessage("Password reset for user ID: $user_id", 'activity');

        return ['success' => true, 'message' => 'Password updated successfully. You can now log in.'];
    }

    // ===========================
    // PRIVATE HELPERS
    // ===========================

    /**
     * Create user session after successful login
     */
    private function createSession($user) {
        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']        = $user['user_id'];
        $_SESSION['user_role']      = $user['role'];
        $_SESSION['first_name']     = $user['first_name'];
        $_SESSION['last_name']      = $user['last_name'];
        $_SESSION['email']          = $user['email'];
        $_SESSION['last_activity']  = time();
    }

    /**
     * Validate registration input
     */
    private function validateRegistration($data) {
        $errors = [];

        if (empty($data['first_name'])) $errors[] = 'First name is required.';
        if (empty($data['last_name']))  $errors[] = 'Last name is required.';

        if (empty($data['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!isValidEmail($data['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (!empty($data['phone_number']) && !isValidPhone($data['phone_number'])) {
            $errors[] = 'Please enter a valid Uganda phone number (e.g. 0712345678).';
        }

        if (empty($data['password'])) {
            $errors[] = 'Password is required.';
        } elseif (!isValidPassword($data['password'])) {
            $errors[] = 'Password must be at least 8 characters and include uppercase, lowercase, and a number.';
        }

        if (empty($data['confirm_password'])) {
            $errors[] = 'Please confirm your password.';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }
}
?>
