<?php
/**
 * RoomType Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class RoomType extends Model {
    public function __construct() {
        parent::__construct('room_types', 'type_id');
    }
}
