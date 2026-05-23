<?php
/**
 * User Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class User extends Model {
    public function __construct() {
        parent::__construct('users', 'user_id');
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = hashPassword($data['password']);
            unset($data['password']);
        }
        
        // Set email verified to false for new registrations
        if (!isset($data['email_verified'])) {
            $data['email_verified'] = 0;
        }
        
        // Set role to student by default
        if (!isset($data['role'])) {
            $data['role'] = 'student';
        }
        
        return $this->insert($data);
    }
    
    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->queryOne($sql, [$email]);
    }
    
    /**
     * Get user by student ID
     */
    public function getByStudentId($student_id) {
        $sql = "SELECT * FROM {$this->table} WHERE student_id = ?";
        return $this->queryOne($sql, [$student_id]);
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $exclude_id = null) {
        $sql = "SELECT user_id FROM {$this->table} WHERE email = ?";
        
        if ($exclude_id) {
            $sql .= " AND user_id != ?";
            return $this->queryOne($sql, [$email, $exclude_id]) !== null;
        }
        
        return $this->queryOne($sql, [$email]) !== null;
    }
    
    /**
     * Check if phone exists
     */
    public function phoneExists($phone, $exclude_id = null) {
        $sql = "SELECT user_id FROM {$this->table} WHERE phone_number = ?";
        
        if ($exclude_id) {
            $sql .= " AND user_id != ?";
            return $this->queryOne($sql, [$phone, $exclude_id]) !== null;
        }
        
        return $this->queryOne($sql, [$phone]) !== null;
    }
    
    /**
     * Get all students
     */
    public function getAllStudents() {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'student' AND is_active = 1 ORDER BY created_at DESC";
        return $this->query($sql);
    }
    
    /**
     * Get all admins
     */
    public function getAllAdmins() {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'admin' AND is_active = 1 ORDER BY created_at DESC";
        return $this->query($sql);
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($email, $password) {
        $user = $this->getByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        if (!verifyPassword($password, $user['password_hash'])) {
            return false;
        }
        
        if (!$user['is_active']) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * Update password
     */
    public function updatePassword($user_id, $new_password) {
        $password_hash = hashPassword($new_password);
        
        $sql = "UPDATE {$this->table} SET password_hash = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('si', $password_hash, $user_id);
        return $stmt->execute();
    }
    
    /**
     * Mark email as verified
     */
    public function verifyEmail($user_id) {
        $sql = "UPDATE {$this->table} SET email_verified = 1, email_verified_at = NOW() WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }
    
    /**
     * Deactivate user
     */
    public function deactivate($user_id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }
    
    /**
     * Activate user
     */
    public function activate($user_id) {
        $sql = "UPDATE {$this->table} SET is_active = 1 WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }
    
    /**
     * Get user by ID with role checking
     */
    public function getByIdSafe($user_id) {
        $sql = "SELECT user_id, email, first_name, last_name, phone_number, 
                profile_photo, role, is_active, created_at FROM {$this->table} 
                WHERE user_id = ? AND is_active = 1";
        return $this->queryOne($sql, [$user_id]);
    }
    
    /**
     * Search users
     */
    public function search($term, $role = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 
                (email LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone_number LIKE ?)
                AND is_active = 1";
        
        $search_term = "%$term%";
        $params = [$search_term, $search_term, $search_term, $search_term];
        
        if ($role) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->query($sql, $params);
    }
}

?>
