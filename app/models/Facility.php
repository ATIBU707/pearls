<?php
/**
 * Facility Model
 * Online Hostel Management System
 */

require_once 'Model.php';

class Facility extends Model {
    public function __construct() {
        parent::__construct('facilities', 'facility_id');
    }
}
