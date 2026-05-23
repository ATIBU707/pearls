<?php
/**
 * Room Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class Room extends Model {
    public function __construct() {
        parent::__construct('rooms', 'room_id');
    }

    /**
     * Get all rooms with type and facilities
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT r.*, rt.type_name, rt.icon_url as type_icon 
                FROM rooms r 
                LEFT JOIN room_types rt ON r.room_type_id = rt.type_id 
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filters['type_id'])) {
            $sql .= " AND r.room_type_id = ?";
            $params[] = $filters['type_id'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['min_price'])) {
            $sql .= " AND r.price_per_semester >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $sql .= " AND r.price_per_semester <= ?";
            $params[] = $filters['max_price'];
        }

        $sql .= " ORDER BY r.room_number ASC";
        
        return $this->query($sql, $params);
    }

    /**
     * Get room by ID with details
     */
    public function getDetailsById($room_id) {
        $sql = "SELECT r.*, rt.type_name, rt.description as type_description, rt.icon_url as type_icon 
                FROM rooms r 
                LEFT JOIN room_types rt ON r.room_type_id = rt.type_id 
                WHERE r.room_id = ?";
        return $this->queryOne($sql, [$room_id]);
    }

    /**
     * Get facilities for a room
     */
    public function getFacilities($room_id) {
        $sql = "SELECT f.* FROM facilities f 
                JOIN room_facilities rf ON f.facility_id = rf.facility_id 
                WHERE rf.room_id = ?";
        return $this->query($sql, [$room_id]);
    }

    /**
     * Update room status
     */
    public function updateStatus($room_id, $status) {
        $sql = "UPDATE rooms SET status = ? WHERE room_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('si', $status, $room_id);
        return $stmt->execute();
    }
}
