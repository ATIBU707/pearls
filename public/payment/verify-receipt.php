<?php
/**
 * Receipt Verification Page
 * Accessed by scanning the QR code on a receipt.
 * Public — no login required for verification.
 * Admins get an extra Check-In button.
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/Booking.php';
require_once APP_PATH . '/services/NotificationService.php';

$token   = trim($_GET['token'] ?? '');
$valid   = false;
$payment = null;
$booking = null;
$checkinMsg   = '';
$checkinError = '';

// ── Handle admin check-in POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_checkin'])) {
    // Verify admin is logged in
    if (!isAdmin()) {
        $checkinError = 'Access denied. Admin login required.';
    } else {
        // Simple token check — use booking_id + secret hash instead of CSRF
        $bid       = (int)$_POST['booking_id'];
        $submitted = $_POST['checkin_token'] ?? '';
        $expected  = hash('sha256', 'checkin_' . $bid . session_id());

        if (!hash_equals($expected, $submitted)) {
            $checkinError = 'Security token mismatch. Please try again.';
        } else {
            $brow = getRow("SELECT b.*, r.room_id, r.room_number FROM bookings b JOIN rooms r ON b.room_id=r.room_id WHERE b.booking_id=?", [$bid]);
            if ($brow && $brow['status'] !== 'checked_in') {
                executeQuery("UPDATE bookings SET status='checked_in' WHERE booking_id=?", [$bid]);
                executeQuery("UPDATE rooms SET status='occupied' WHERE room_id=?", [$brow['room_id']]);
                NotificationService::bookingStatusChanged((int)$brow['user_id'], $brow['booking_code'], $brow['room_number'], 'checked_in', $bid);
                $checkinMsg = '✅ Student checked in to Room ' . $brow['room_number'] . ' successfully!';
                logMessage("QR check-in: {$brow['booking_code']} — Room {$brow['room_number']}", 'activity');
            } elseif ($brow && $brow['status'] === 'checked_in') {
                $checkinError = 'Student is already checked in.';
            } else {
                $checkinError = 'Booking not found.';
            }
        }
    }
}

// ── Token lookup ──────────────────────────────────────────────────────────
if ($token) {
    $row = getRow(
        "SELECT p.*,
                b.booking_id, b.booking_code, b.semester, b.status AS booking_status,
                b.check_in_date, b.user_id,
                r.room_number, rt.type_name,
                u.first_name, u.last_name, u.student_id, u.phone_number AS student_phone
         FROM payments p
         JOIN bookings b    ON p.booking_id    = b.booking_id
         JOIN rooms r       ON b.room_id       = r.room_id
         JOIN room_types rt ON r.room_type_id  = rt.type_id
         JOIN users u       ON b.user_id       = u.user_id
         WHERE p.receipt_token = ? AND p.status = 'completed'
         LIMIT 1",
        [$token]
    );

    if (!$row) {
        $candidates = getRows(
            "SELECT p.*,
                    b.booking_id, b.booking_code, b.semester, b.status AS booking_status,
                    b.check_in_date, b.user_id,
                    r.room_number, rt.type_name,
                    u.first_name, u.last_name, u.student_id, u.phone_number AS student_phone
             FROM payments p
             JOIN bookings b    ON p.booking_id   = b.booking_id
             JOIN rooms r       ON b.room_id      = r.room_id
             JOIN room_types rt ON r.room_type_id = rt.type_id
             JOIN users u       ON b.user_id      = u.user_id
             WHERE p.status = 'completed' AND p.receipt_token IS NULL LIMIT 500"
        );
        foreach ($candidates as $c) {
            $expected = hash('sha256', $c['payment_id'] . $c['booking_id'] . $c['amount']);
            if (hash_equals($expected, $token)) {
                $row = $c;
                executeQuery("UPDATE payments SET receipt_token=? WHERE payment_id=?", [$token, $c['payment_id']]);
                break;
            }
        }
    }

    if ($row) {
        $payment = $row;
        $booking = $row; // same join
        $valid   = true;
    }
}

// Parse payment notes for phone number used in mobile money
$paymentNotes = [];
if ($payment && !empty($payment['notes'])) {
    $decoded = json_decode($payment['notes'], true);
    if (is_array($decoded)) $paymentNotes = $decoded;
}
$mobilePhone = $paymentNotes['phone'] ?? $booking['student_phone'] ?? '';
$isMobile    = in_array($payment['payment_method'] ?? '', ['mtn_momo', 'airtel_money']);
$methodLabel = [
    'mtn_momo'     => 'MTN Mobile Money',
    'airtel_money' => 'Airtel Money',
    'card'         => 'Visa / MasterCard',
][$payment['payment_method'] ?? ''] ?? ucwords(str_replace('_', ' ', $payment['payment_method'] ?? ''));
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
body{background:#f5f6fa;color:#1e293b;font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.verify-card{background:#fff;border:1px solid #e8edf5;border-radius:20px;max-width:500px;width:100%;box-shadow:0 8px 40px rgba(0,0,0,0.1);}
.row-item{display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f1f5f9;font-size:0.875rem;}
.row-item:last-child{border:none;}
.label{color:#64748b;font-weight:500;}
.value{color:#1e293b;font-weight:600;text-align:right;max-width:60%;}
</style>
</head>
<body>
<div class="verify-card">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#1e1b4b,#4f46e5);padding:24px 28px;border-radius:20px 20px 0 0;text-align:center;">
        <div style="font-size:0.75rem;color:rgba(255,255,255,0.6);letter-spacing:.08em;font-weight:700;text-transform:uppercase;margin-bottom:4px;">
            <i class="fas fa-building me-1"></i>Pearls of Wisdom Hostel
        </div>
        <h1 style="font-size:1.1rem;font-weight:800;color:white;margin:0;">Receipt Verification</h1>
    </div>

    <div style="padding:28px;">

    <?php if ($checkinMsg): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#15803d;font-weight:600;font-size:0.875rem;">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($checkinMsg); ?>
    </div>
    <?php endif; ?>
    <?php if ($checkinError): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:20px;color:#b91c1c;font-size:0.875rem;">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($checkinError); ?>
    </div>
    <?php endif; ?>

    <?php if ($valid && $payment && $booking): ?>

    <!-- Status badge -->
    <div style="text-align:center;margin-bottom:20px;">
        <div style="width:64px;height:64px;border-radius:50%;background:#dcfce7;border:2px solid #86efac;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
            <i class="fas fa-check-double" style="color:#16a34a;font-size:1.5rem;"></i>
        </div>
        <div style="font-size:1rem;font-weight:800;color:#16a34a;">AUTHENTIC RECEIPT</div>
        <div style="font-size:0.75rem;color:#64748b;margin-top:2px;">Verified — payment confirmed</div>
    </div>

    <!-- Amount highlight -->
    <div style="background:linear-gradient(135deg,#eef2ff,#f5f3ff);border:1px solid #c7d2fe;border-radius:12px;padding:16px 20px;display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-weight:700;">Amount Paid</div>
            <div style="font-size:1.8rem;font-weight:900;color:#4f46e5;"><?php echo formatCurrency($payment['amount']); ?></div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:0.7rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em;font-weight:700;">Receipt #</div>
            <div style="font-size:0.9rem;font-weight:700;color:#4f46e5;">PMT-<?php echo str_pad($payment['payment_id'],6,'0',STR_PAD_LEFT); ?></div>
        </div>
    </div>

    <!-- Details -->
    <div style="background:#f8f9ff;border-radius:12px;padding:16px 20px;margin-bottom:20px;">
        <div class="row-item">
            <span class="label">Booking Code</span>
            <span class="value" style="color:#4f46e5;"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Student Name</span>
            <span class="value"><?php echo htmlspecialchars($booking['first_name'].' '.$booking['last_name']); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Student ID</span>
            <span class="value"><?php echo htmlspecialchars($booking['student_id'] ?: '—'); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Room</span>
            <span class="value">Room <?php echo htmlspecialchars($booking['room_number']); ?> (<?php echo htmlspecialchars($booking['type_name']); ?>)</span>
        </div>
        <div class="row-item">
            <span class="label">Semester</span>
            <span class="value"><?php echo htmlspecialchars($booking['semester']); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Check-in Date</span>
            <span class="value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Payment Method</span>
            <span class="value"><?php echo htmlspecialchars($methodLabel); ?></span>
        </div>
        <?php if ($isMobile && $mobilePhone): ?>
        <div class="row-item">
            <span class="label">Mobile Number</span>
            <span class="value" style="color:#4f46e5;font-weight:700;">+<?php echo htmlspecialchars(ltrim($mobilePhone, '+')); ?></span>
        </div>
        <?php endif; ?>
        <div class="row-item">
            <span class="label">Transaction Ref</span>
            <span class="value" style="font-size:0.78rem;"><?php echo htmlspecialchars($payment['transaction_reference'] ?: 'N/A'); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Payment Date</span>
            <span class="value"><?php echo date('M d, Y  H:i', strtotime($payment['payment_date'] ?? $payment['created_at'])); ?></span>
        </div>
        <div class="row-item">
            <span class="label">Booking Status</span>
            <span class="value">
                <?php
                $statusColors = [
                    'confirmed'  => ['#dcfce7','#15803d'],
                    'checked_in' => ['#dbeafe','#1d4ed8'],
                    'pending'    => ['#fef3c7','#92400e'],
                    'cancelled'  => ['#fee2e2','#b91c1c'],
                ];
                [$bg,$fg] = $statusColors[$booking['booking_status']] ?? ['#f1f5f9','#475569'];
                ?>
                <span style="background:<?php echo $bg;?>;color:<?php echo $fg;?>;padding:3px 12px;border-radius:999px;font-size:0.72rem;font-weight:700;">
                    <?php echo ucfirst(str_replace('_',' ',$booking['booking_status'])); ?>
                </span>
            </span>
        </div>
    </div>

    <?php if (isAdmin()): ?>
    <!-- Admin check-in button -->
    <?php if ($booking['booking_status'] === 'checked_in'): ?>
    <div style="background:#dbeafe;border:1px solid #93c5fd;border-radius:10px;padding:12px 16px;text-align:center;color:#1d4ed8;font-weight:700;font-size:0.875rem;">
        <i class="fas fa-check-double me-2"></i>Already Checked In
    </div>
    <?php elseif (in_array($booking['booking_status'], ['confirmed','pending'])): ?>
    <form method="POST">
        <input type="hidden" name="do_checkin"    value="1">
        <input type="hidden" name="booking_id"    value="<?php echo $booking['booking_id']; ?>">
        <input type="hidden" name="checkin_token" value="<?php echo hash('sha256', 'checkin_' . $booking['booking_id'] . session_id()); ?>">
        <button type="submit" style="width:100%;padding:14px;border-radius:999px;background:linear-gradient(135deg,#16a34a,#15803d);border:none;color:white;font-size:0.95rem;font-weight:700;cursor:pointer;box-shadow:0 4px 16px rgba(22,163,74,0.3);">
            <i class="fas fa-sign-in-alt me-2"></i>Check In Student
        </button>
    </form>
    <?php endif; ?>
    <div style="text-align:center;margin-top:12px;">
        <a href="../admin/checkin.php" style="font-size:0.78rem;color:#4f46e5;text-decoration:none;">
            <i class="fas fa-arrow-left me-1"></i>Back to Scanner
        </a>
    </div>
    <?php endif; ?>

    <p style="font-size:0.7rem;color:#94a3b8;text-align:center;margin-top:16px;margin-bottom:0;">
        Verified <?php echo date('M d, Y \a\t H:i'); ?> &bull; admin@pearlswisdom.com
    </p>

    <?php else: ?>
    <!-- INVALID -->
    <div style="text-align:center;padding:20px 0;">
        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;border:2px solid #fca5a5;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
            <i class="fas fa-times-circle" style="color:#dc2626;font-size:1.5rem;"></i>
        </div>
        <div style="font-size:1rem;font-weight:800;color:#dc2626;">INVALID RECEIPT</div>
        <div style="font-size:0.82rem;color:#64748b;margin-top:8px;">
            This QR code could not be verified. The receipt may be altered or does not exist.
        </div>
        <p style="font-size:0.72rem;color:#94a3b8;margin-top:16px;">
            Contact admin@pearlswisdom.com to report a suspicious receipt.
        </p>
    </div>
    <?php endif; ?>

    </div><!-- /padding -->
</div><!-- /verify-card -->
</body>
</html>
