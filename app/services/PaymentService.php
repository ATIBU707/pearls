<?php
/**
 * PaymentService - Handles payment gateway integration
 * Online Hostel Management System
 */

require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/Booking.php';
require_once APP_PATH . '/services/PesapalService.php';

class PaymentService {
    private $paymentModel;
    private $bookingModel;

    public function __construct() {
        $this->paymentModel = new Payment();
        $this->bookingModel = new Booking();
    }

    /**
     * Initiate a payment — creates a pending DB record.
     * Actual PesaPal submission is done in initiate.php via PesapalService directly.
     */
    public function initiate($booking_id, $method) {
        $booking = $this->bookingModel->getDetails($booking_id);
        if (!$booking) return ['success' => false, 'message' => 'Booking not found.'];

        $payment_id = $this->paymentModel->createPayment([
            'booking_id'     => $booking_id,
            'amount'         => $booking['price_per_semester'],
            'payment_method' => $method,
            'status'         => 'pending',
        ]);

        if ($payment_id) {
            return [
                'success'    => true,
                'payment_id' => $payment_id,
                'amount'     => $booking['price_per_semester'],
                'booking'    => $booking,
            ];
        }

        return ['success' => false, 'message' => 'Failed to initiate payment.'];
    }

    /**
     * Verify payment status via PesaPal API.
     *
     * @param int    $payment_id  Internal payment record ID
     * @param string $tracking_id PesaPal OrderTrackingId
     */
    public function verify($payment_id, $tracking_id) {
        $payment = $this->paymentModel->getById($payment_id);
        if (!$payment) return ['success' => false, 'message' => 'Payment record not found.'];

        // Already marked complete — skip API call
        if ($payment['status'] === 'completed') {
            return ['success' => true, 'message' => 'Payment already completed.'];
        }

        $pesapal = new PesapalService();
        $result  = $pesapal->getTransactionStatus($tracking_id);
        $status  = strtoupper($result['status'] ?? '');

        if (in_array($status, ['COMPLETED', 'PAID', 'SUCCESS'])) {
            $confirmCode = $result['confirmation_code'] ?: $tracking_id;
            $this->paymentModel->updateStatus($payment_id, 'completed', $confirmCode);
            $this->bookingModel->updateStatus($payment['booking_id'], 'confirmed');
            executeQuery(
                "UPDATE rooms SET status = 'occupied'
                 WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = ?)",
                [$payment['booking_id']]
            );
            return ['success' => true, 'message' => 'Payment verified successfully!'];
        }

        if (in_array($status, ['FAILED', 'INVALID', 'REVERSED'])) {
            $this->paymentModel->updateStatus($payment_id, 'failed', $tracking_id);
            return ['success' => false, 'message' => 'Payment failed or was reversed.'];
        }

        // Still pending
        return ['success' => false, 'message' => 'Payment is still pending.'];
    }
}
?>
