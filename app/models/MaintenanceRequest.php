<?php
/**
 * MaintenanceRequest Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class MaintenanceRequest extends Model {
    public function __construct() {
        parent::__construct('maintenance_requests', 'request_id');
    }

    /**
     * Create a new maintenance request
     */
    public function createRequest($data) {
        // Schema uses 'title' instead of 'category'
        if (isset($data['category'])) {
            $data['title'] = $data['category'];
            unset($data['category']);
        }
        $data['status'] = 'open';  // ENUM: open, in_progress, resolved, closed

        // Auto-resolve room_id from student's active booking if not provided
        if (empty($data['room_id']) && !empty($data['user_id'])) {
            $row = $this->query(
                "SELECT room_id FROM bookings WHERE user_id = ? AND status IN ('confirmed','checked_in') ORDER BY created_at DESC LIMIT 1",
                [$data['user_id']]
            );
            if (!empty($row)) {
                $data['room_id'] = $row[0]['room_id'];
            } else {
                // No active booking — cannot file request without a room
                return false;
            }
        }

        return $this->insert($data);
    }

    /**
     * Get requests by user ID
     */
    public function getByUser($user_id) {
        $sql = "SELECT m.*, r.room_number 
                FROM maintenance_requests m 
                LEFT JOIN bookings b ON m.user_id = b.user_id AND b.status IN ('confirmed', 'checked_in')
                LEFT JOIN rooms r ON b.room_id = r.room_id 
                WHERE m.user_id = ? 
                ORDER BY m.created_at DESC";
        return $this->query($sql, [$user_id]);
    }

    /**
     * Get all requests for admin
     */
    public function getAllDetailed() {
        $sql = "SELECT m.*, u.first_name, u.last_name, r.room_number 
                FROM maintenance_requests m 
                JOIN users u ON m.user_id = u.user_id 
                LEFT JOIN bookings b ON u.user_id = b.user_id AND b.status IN ('confirmed', 'checked_in')
                LEFT JOIN rooms r ON b.room_id = r.room_id 
                ORDER BY m.created_at DESC";
        return $this->query($sql);
    }
}
