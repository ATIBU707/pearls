<?php
/**
 * RoomService - Handles room business logic
 * Online Hostel Management System
 */

require_once APP_PATH . '/models/Room.php';
require_once APP_PATH . '/models/RoomType.php';

class RoomService {
    private $roomModel;
    private $typeModel;

    public function __construct() {
        $this->roomModel = new Room();
        $this->typeModel = new RoomType();
    }

    /**
     * Get room listing data
     */
    public function getRooms($filters = []) {
        return $this->roomModel->getAllWithDetails($filters);
    }

    /**
     * Get all room types
     */
    public function getRoomTypes() {
        return $this->typeModel->getAll();
    }

    /**
     * Get single room details with facilities
     */
    public function getRoomDetails($room_id) {
        $room = $this->roomModel->getDetailsById($room_id);
        if (!$room) return null;
        
        $room['facilities'] = $this->roomModel->getFacilities($room_id);
        return $room;
    }
}
