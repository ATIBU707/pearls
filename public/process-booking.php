<?php
/**
 * Process Booking Handler
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/BookingService.php';

requireStudentAuth();
verifyCsrfOrDie();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rooms.php');
    exit;
}

$room_id       = (int)($_POST['room_id']      ?? 0);
$semester      = trim($_POST['semester']      ?? '');
$check_in_date = trim($_POST['check_in_date'] ?? '');

// Basic validation
if (!$room_id || empty($semester) || empty($check_in_date)) {
    redirectWithMessage('rooms.php', 'Invalid booking request. Please fill all required fields.', 'error');
}

// Date must not be in the past
if (strtotime($check_in_date) < strtotime('today')) {
    redirectWithMessage('booking.php?room_id=' . $room_id, 'Check-in date cannot be in the past.', 'error');
}

$bookingService = new BookingService();
$result = $bookingService->processBooking([
    'user_id'       => getCurrentUserId(),
    'room_id'       => $room_id,
    'check_in_date' => $check_in_date,
    'semester'      => $semester,
]);

if ($result['success']) {
    // Redirect to payment page
    header('Location: payment/initiate.php?booking_id=' . $result['booking_id']);
    exit;
} else {
    redirectWithMessage('booking.php?room_id=' . $room_id, $result['message'], 'error');
}
?>
