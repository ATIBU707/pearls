<?php
/**
 * BookingService - Handles booking business logic
 * Online Hostel Management System
 */

require_once APP_PATH . '/models/Booking.php';
require_once APP_PATH . '/models/Room.php';
require_once APP_PATH . '/services/EmailService.php';

class BookingService {
    private $bookingModel;
    private $roomModel;

    public function __construct() {
        $this->bookingModel = new Booking();
        $this->roomModel = new Room();
    }

    /**
     * Create a new booking with validation
     */
    public function processBooking($data) {
        // 1. Check if room exists and is available
        $room = $this->roomModel->getById($data['room_id']);
        if (!$room || $room['status'] !== 'available') {
            return ['success' => false, 'message' => 'This room is no longer available.'];
        }

        // 2. Prevent double booking (ensure no confirmed/pending booking for this user for same semester if needed)
        // (Simplified for now: just allow the booking if room is available)

        // 3. Create booking record
        $booking_id = $this->bookingModel->createBooking($data);
        
        if ($booking_id) {
            // 4. Update room status to 'booked' (pending payment)
            $this->roomModel->updateStatus($data['room_id'], 'booked');

            // 5. Send booking confirmation email
            try {
                $bookingDetails = $this->bookingModel->getDetails($booking_id);
                if ($bookingDetails) {
                    $emailService = new EmailService();
                    $emailService->sendBookingConfirmation(
                        $bookingDetails['email'],
                        $bookingDetails['first_name'],
                        $bookingDetails['booking_code'],
                        $bookingDetails['room_number'],
                        $bookingDetails['semester'],
                        formatCurrency($bookingDetails['price_per_semester']),
                        $booking_id
                    );
                }
            } catch (\Throwable $e) {
                logMessage('BookingService email error: ' . $e->getMessage(), 'error');
            }

            return [
                'success'    => true,
                'message'    => 'Booking created successfully! Please proceed to payment.',
                'booking_id' => $booking_id
            ];
        }

        return ['success' => false, 'message' => 'Failed to process booking. Please try again.'];
    }

    /**
     * Get user's bookings
     */
    public function getUserBookings($user_id) {
        return $this->bookingModel->getByUser($user_id);
    }

    /**
     * Get booking details
     */
    public function getBooking($booking_id) {
        return $this->bookingModel->getDetails($booking_id);
    }
}
