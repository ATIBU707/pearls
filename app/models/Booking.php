<?php
/**
 * Booking Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class Booking extends Model {
    public function __construct() {
        parent::__construct('bookings', 'booking_id');
    }

    /**
     * Create a new booking
     */
    public function createBooking($data) {
        $data['booking_code'] = generateBookingCode();
        $data['status'] = 'pending';
        return $this->insert($data);
    }

    /**
     * Get bookings by user ID
     */
    public function getByUser($user_id) {
        $sql = "SELECT b.*, r.room_number, rt.type_name 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.room_id 
                JOIN room_types rt ON r.room_type_id = rt.type_id 
                WHERE b.user_id = ? 
                ORDER BY b.created_at DESC";
        return $this->query($sql, [$user_id]);
    }

    /**
     * Get single booking details
     */
    public function getDetails($booking_id) {
        $sql = "SELECT b.*, r.room_number, r.price_per_semester, rt.type_name, 
                       u.first_name, u.last_name, u.email, u.phone_number, u.student_id 
                FROM bookings b 
                JOIN rooms r ON b.room_id = r.room_id 
                JOIN room_types rt ON r.room_type_id = rt.type_id 
                JOIN users u ON b.user_id = u.user_id 
                WHERE b.booking_id = ?";
        return $this->queryOne($sql, [$booking_id]);
    }

    /**
     * Update booking status
     */
    public function updateStatus($booking_id, $status) {
        $sql = "UPDATE bookings SET status = ? WHERE booking_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param('si', $status, $booking_id);
        return $stmt->execute();
    }
}
