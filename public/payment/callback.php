<?php
/**
 * PesaPal IPN Callback Endpoint
 * Online Hostel Management System
 *
 * PesaPal calls this URL via GET with:
 *   ?OrderTrackingId=xxx&OrderNotificationType=IPNCHANGE&OrderMerchantReference=xxx
 *
 * Must respond with HTTP 200 and the OrderNotificationType.
 * See: https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/ipn-notifications
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/PesapalService.php';
require_once APP_PATH . '/services/NotificationService.php';
require_once APP_PATH . '/services/EmailService.php';
require_once APP_PATH . '/models/Payment.php';

// PesaPal sends GET, not POST
$orderTrackingId = $_GET['OrderTrackingId']       ?? '';
$notifType       = $_GET['OrderNotificationType'] ?? '';
$merchantRef     = $_GET['OrderMerchantReference'] ?? '';

// Log raw call
logMessage("PesaPal IPN received: tracking={$orderTrackingId} type={$notifType} ref={$merchantRef}", 'activity');

if (!$orderTrackingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing OrderTrackingId']);
    exit;
}

// Check transaction status with PesaPal
$pesapal = new PesapalService();
$result  = $pesapal->getTransactionStatus($orderTrackingId);

$pesapalStatus = strtoupper($result['status'] ?? '');

logMessage("PesaPal IPN status for {$orderTrackingId}: {$pesapalStatus}", 'activity');

// Find the payment record by tracking ID
$payment = getRow(
    "SELECT p.*, b.user_id, b.booking_id, b.booking_code, b.room_id
     FROM payments p
     JOIN bookings b ON p.booking_id = b.booking_id
     WHERE p.transaction_reference = ?",
    [$orderTrackingId]
);

if (!$payment) {
    // May have been saved as pending merchant reference before tracking ID was set
    $payment = getRow(
        "SELECT p.*, b.user_id, b.booking_id, b.booking_code, b.room_id
         FROM payments p
         JOIN bookings b ON p.booking_id = b.booking_id
         WHERE p.notes LIKE ?",
        ['%' . $merchantRef . '%']
    );
}

if ($payment && in_array($pesapalStatus, ['COMPLETED', 'PAID', 'SUCCESS'])) {

    // Idempotency — only process once
    if ($payment['status'] !== 'completed') {
        $paymentModel = new Payment();
        $confirmCode  = $result['confirmation_code'] ?: $orderTrackingId;

        // 1. Mark payment completed
        $paymentModel->updateStatus($payment['payment_id'], 'completed', $confirmCode);

        // 2. Confirm booking
        executeQuery(
            "UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?",
            [$payment['booking_id']]
        );

        // 3. Mark room occupied
        executeQuery(
            "UPDATE rooms SET status = 'occupied' WHERE room_id = ?",
            [$payment['room_id']]
        );

        // 4. Notify student
        NotificationService::send(
            (int)$payment['user_id'],
            '✅ Payment Confirmed — ' . $payment['booking_code'],
            'Your payment of ' . number_format($payment['amount'], 0) . ' UGX has been received. ' .
            'Booking ' . $payment['booking_code'] . ' is now confirmed.',
            'payment',
            (int)$payment['booking_id']
        );

        logMessage("Payment {$payment['payment_id']} completed via IPN. Booking {$payment['booking_id']} confirmed.", 'activity');

        // Send payment confirmation email
        try {
            $pRow = getRow(
                "SELECT p.*, b.booking_code, b.semester, r.room_number, u.email, u.first_name
                 FROM payments p
                 JOIN bookings b ON p.booking_id = b.booking_id
                 JOIN rooms r    ON b.room_id    = r.room_id
                 JOIN users u    ON b.user_id    = u.user_id
                 WHERE p.payment_id = ?",
                [$payment['payment_id']]
            );
            if ($pRow) {
                (new EmailService())->sendPaymentConfirmation(
                    $pRow['email'], $pRow['first_name'],
                    $pRow['booking_code'], $pRow['room_number'],
                    formatCurrency($pRow['amount']),
                    $confirmCode,
                    $payment['payment_id']
                );
            }
        } catch (\Throwable $e) {
            logMessage('IPN email error: ' . $e->getMessage(), 'error');
        }
    }

} elseif ($payment && in_array($pesapalStatus, ['FAILED', 'INVALID', 'REVERSED'])) {
    if ($payment['status'] === 'pending') {
        $paymentModel = new Payment();
        $paymentModel->updateStatus($payment['payment_id'], 'failed', $orderTrackingId);
        logMessage("Payment {$payment['payment_id']} failed via IPN.", 'activity');
    }
}

// PesaPal requires this specific response format
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'orderNotificationType'   => $notifType,
    'orderTrackingId'         => $orderTrackingId,
    'orderMerchantReference'  => $merchantRef,
    'status'                  => 200,
]);
exit;
