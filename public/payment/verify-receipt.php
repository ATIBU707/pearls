<?php
/**
 * Receipt Verification Page
 * Accessed by scanning the QR code on a receipt.
 * Public — no login required (so anyone can verify authenticity).
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/Booking.php';

$token = trim($_GET['token'] ?? '');
$valid   = false;
$payment = null;
$booking = null;

if ($token) {
    $paymentModel = new Payment();
    $bookingModel = new Booking();

    // Fast O(1) lookup via indexed receipt_token column (set on payment completion)
    $row = getRow(
        "SELECT p.*, b.booking_code
         FROM payments p
         JOIN bookings b ON p.booking_id = b.booking_id
         WHERE p.receipt_token = ? AND p.status = 'completed'
         LIMIT 1",
        [$token]
    );

    // Fallback: compute token on-the-fly for payments before migration
    if (!$row) {
        $rows = getRows(
            "SELECT p.*, b.booking_code FROM payments p
             JOIN bookings b ON p.booking_id = b.booking_id
             WHERE p.status = 'completed' AND p.receipt_token IS NULL
             LIMIT 500"
        );
        foreach ($rows as $candidate) {
            $expected = hash('sha256', $candidate['payment_id'] . $candidate['booking_id'] . $candidate['amount']);
            if (hash_equals($expected, $token)) {
                $row = $candidate;
                // Store it for next time
                executeQuery("UPDATE payments SET receipt_token = ? WHERE payment_id = ?", [$token, $candidate['payment_id']]);
                break;
            }
        }
    }

    if ($row) {
        $payment = $row;
        $booking = $bookingModel->getDetails($row['booking_id']);
        $valid   = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Receipt Verification — Pearls of Wisdom Hostel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { background:#0f0e1a; color:#f1f5f9; font-family:'Inter',sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; }
.verify-card { background:#1e1b4b; border:1px solid rgba(255,255,255,0.1); border-radius:20px; max-width:480px; width:100%; padding:40px 36px; box-shadow:0 24px 80px rgba(0,0,0,0.6); }
.row-item { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid rgba(255,255,255,0.07); font-size:0.875rem; }
.row-item:last-child { border:none; }
.label { color:#94a3b8; }
.value { color:#f1f5f9; font-weight:600; text-align:right; }
</style>
</head>
<body>
<div class="verify-card">
    <div class="text-center mb-4">
        <div style="font-size:0.78rem;color:#818cf8;letter-spacing:.08em;font-weight:700;text-transform:uppercase;margin-bottom:6px;">
            <i class="fas fa-building me-1"></i>Pearls of Wisdom Hostel
        </div>
        <h1 style="font-size:1.25rem;font-weight:800;margin-bottom:4px;">Receipt Verification</h1>
        <p style="font-size:0.82rem;color:#94a3b8;margin:0;">Scan result for QR code authentication</p>
    </div>

    <?php if ($valid && $payment && $booking): ?>
    <!-- VALID -->
    <div class="text-center mb-4">
        <div style="width:70px;height:70px;border-radius:50%;background:rgba(34,197,94,0.15);border:2px solid rgba(34,197,94,0.4);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-check-double" style="color:#4ade80;font-size:1.6rem;"></i>
        </div>
        <div style="font-size:1.1rem;font-weight:800;color:#4ade80;">AUTHENTIC RECEIPT</div>
        <div style="font-size:0.78rem;color:#64748b;">This receipt has been verified as genuine.</div>
    </div>

    <div style="background:rgba(255,255,255,0.04);border-radius:12px;padding:16px 20px;margin-bottom:20px;">
        <div class="row-item"><span class="label">Booking Code</span><span class="value" style="color:#818cf8;"><?php echo htmlspecialchars($booking['booking_code']); ?></span></div>
        <div class="row-item"><span class="label">Student</span><span class="value"><?php echo htmlspecialchars($booking['first_name'].' '.$booking['last_name']); ?></span></div>
        <div class="row-item"><span class="label">Student ID</span><span class="value"><?php echo htmlspecialchars($booking['student_id'] ?: '—'); ?></span></div>
        <div class="row-item"><span class="label">Room</span><span class="value">Room <?php echo htmlspecialchars($booking['room_number']); ?> (<?php echo htmlspecialchars($booking['type_name']); ?>)</span></div>
        <div class="row-item"><span class="label">Semester</span><span class="value"><?php echo htmlspecialchars($booking['semester']); ?></span></div>
        <div class="row-item"><span class="label">Payment Method</span><span class="value"><?php echo ucwords(str_replace('_',' ',$payment['payment_method'])); ?></span></div>
        <div class="row-item"><span class="label">Transaction Ref</span><span class="value"><?php echo htmlspecialchars($payment['transaction_reference'] ?: 'N/A'); ?></span></div>
        <div class="row-item"><span class="label">Payment Date</span><span class="value"><?php echo date('M d, Y H:i', strtotime($payment['payment_date'] ?? $payment['created_at'])); ?></span></div>
        <div class="row-item">
            <span class="label">Amount Paid</span>
            <span class="value" style="color:#4ade80;font-size:1.05rem;"><?php echo formatCurrency($payment['amount']); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Status</span>
            <span style="background:rgba(34,197,94,0.2);color:#4ade80;padding:2px 12px;border-radius:999px;font-size:0.72rem;font-weight:700;">PAID IN FULL</span>
        </div>
    </div>

    <p style="font-size:0.72rem;color:#64748b;text-align:center;margin:0;">
        Verified on <?php echo date('M d, Y \a\t H:i'); ?> &bull; admin@pearlswisdom.com
    </p>

    <?php else: ?>
    <!-- INVALID -->
    <div class="text-center mb-4">
        <div style="width:70px;height:70px;border-radius:50%;background:rgba(239,68,68,0.15);border:2px solid rgba(239,68,68,0.4);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-times-circle" style="color:#f87171;font-size:1.6rem;"></i>
        </div>
        <div style="font-size:1.1rem;font-weight:800;color:#f87171;">INVALID RECEIPT</div>
        <div style="font-size:0.82rem;color:#94a3b8;margin-top:8px;">
            This QR code could not be verified. The receipt may be altered or invalid.
        </div>
    </div>
    <p style="font-size:0.72rem;color:#64748b;text-align:center;">
        Contact admin@pearlswisdom.com to report a suspicious receipt.
    </p>
    <?php endif; ?>
</div>
</body>
</html>
