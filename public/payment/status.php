<?php
/**
 * Payment Status / Waiting Page
 * Shown after STK push is sent — polls for completion.
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/Booking.php';
require_once APP_PATH . '/services/PesapalService.php';
require_once APP_PATH . '/services/NotificationService.php';
require_once APP_PATH . '/services/EmailService.php';

requireLogin();

$payment_id  = (int)($_GET['payment_id']  ?? 0);
$tracking_id = trim($_GET['tracking_id']  ?? '');
$booking_id  = (int)($_GET['booking_id']  ?? 0); // from PesaPal redirect

// ── Handle AJAX poll request ────────────────────────────────────────────
if (isset($_GET['poll'])) {
    header('Content-Type: application/json');
    if (!$payment_id && !$tracking_id) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
        exit;
    }

    $payRec = getRow("SELECT * FROM payments WHERE payment_id = ?", [$payment_id]);
    if (!$payRec) { echo json_encode(['status' => 'error', 'message' => 'Payment not found']); exit; }

    // ── Always check DB first — IPN may have already updated it ──────────
    if ($payRec['status'] === 'completed') {
        echo json_encode(['status' => 'completed', 'payment_id' => $payment_id]);
        exit;
    }
    if ($payRec['status'] === 'failed') {
        echo json_encode(['status' => 'failed', 'message' => 'Payment was declined or failed.']);
        exit;
    }

    // ── Demo mode: auto-complete after 3 seconds (2nd poll) ──────────────
    $isDemo = str_starts_with($tracking_id, 'DEMO-');
    if ($isDemo) {
        // Use session to count demo polls
        $pollKey = 'demo_polls_' . $payment_id;
        $_SESSION[$pollKey] = ($_SESSION[$pollKey] ?? 0) + 1;

        if ($_SESSION[$pollKey] >= 2) { // Complete on 2nd poll (~10 seconds in)
            $paymentModel = new Payment();
            $demoRef      = 'DEMO-PAID-' . strtoupper(bin2hex(random_bytes(4)));
            $paymentModel->updateStatus($payment_id, 'completed', $demoRef);

            $brow = getRow(
                "SELECT b.*, r.room_id FROM bookings b
                 JOIN rooms r ON b.room_id = r.room_id
                 WHERE b.booking_id = (SELECT booking_id FROM payments WHERE payment_id = ?)",
                [$payment_id]
            );
            if ($brow) {
                executeQuery("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?", [$brow['booking_id']]);
                executeQuery("UPDATE rooms SET status = 'occupied' WHERE room_id = ?",       [$brow['room_id']]);
                NotificationService::send(
                    (int)$brow['user_id'],
                    '✅ Payment Confirmed — ' . $brow['booking_code'],
                    '[Demo] Your payment has been confirmed. Booking ' . $brow['booking_code'] . ' is now active.',
                    'payment',
                    (int)$brow['booking_id']
                );
            }
            unset($_SESSION[$pollKey]);
            echo json_encode(['status' => 'completed', 'payment_id' => $payment_id]);

            // Send payment confirmation email
            try {
                $pRow = getRow("SELECT p.*, b.booking_code, b.semester, r.room_number, u.email, u.first_name FROM payments p JOIN bookings b ON p.booking_id=b.booking_id JOIN rooms r ON b.room_id=r.room_id JOIN users u ON b.user_id=u.user_id WHERE p.payment_id=?", [$payment_id]);
                if ($pRow) {
                    (new EmailService())->sendPaymentConfirmation($pRow['email'], $pRow['first_name'], $pRow['booking_code'], $pRow['room_number'], formatCurrency($pRow['amount']), $demoRef, $payment_id);
                }
            } catch (\Throwable $e) { logMessage('Email error: '.$e->getMessage(),'error'); }

            exit;
        }
        // Still "waiting"
        echo json_encode(['status' => 'pending']);
        exit;
    }

    // ── Live mode: check with PesaPal ────────────────────────────────────

        $pesapal = new PesapalService();
        $result  = $pesapal->getTransactionStatus($tracking_id);

        $pesapalStatus = strtoupper($result['status'] ?? '');

        if (in_array($pesapalStatus, ['COMPLETED', 'PAID', 'SUCCESS'])) {
            // Update payment & booking
            $paymentModel = new Payment();
            $paymentModel->updateStatus($payment_id, 'completed', $result['confirmation_code'] ?: $tracking_id);

            $brow = getRow("SELECT * FROM bookings WHERE booking_id = (SELECT booking_id FROM payments WHERE payment_id = ?)", [$payment_id]);
            if ($brow) {
                executeQuery("UPDATE bookings SET status = 'confirmed' WHERE booking_id = ?", [$brow['booking_id']]);
                executeQuery("UPDATE rooms SET status = 'occupied' WHERE room_id = ?", [$brow['room_id']]);

                NotificationService::send(
                    (int)$brow['user_id'],
                    '✅ Payment Received — Booking Confirmed!',
                    'Your payment for booking ' . $brow['booking_code'] . ' has been received. Your room is now confirmed.',
                    'payment',
                    (int)$brow['booking_id']
                );
            }

            echo json_encode(['status' => 'completed', 'payment_id' => $payment_id]);

            // Send payment confirmation email
            try {
                $pRow = getRow("SELECT p.*, b.booking_code, b.semester, r.room_number, u.email, u.first_name FROM payments p JOIN bookings b ON p.booking_id=b.booking_id JOIN rooms r ON b.room_id=r.room_id JOIN users u ON b.user_id=u.user_id WHERE p.payment_id=?", [$payment_id]);
                if ($pRow) {
                    (new EmailService())->sendPaymentConfirmation($pRow['email'], $pRow['first_name'], $pRow['booking_code'], $pRow['room_number'], formatCurrency($pRow['amount']), $result['confirmation_code'] ?: $tracking_id, $payment_id);
                }
            } catch (\Throwable $e) { logMessage('Email error: '.$e->getMessage(),'error'); }

            exit;

        } elseif (in_array($pesapalStatus, ['FAILED', 'INVALID', 'REVERSED'])) {
            $paymentModel = new Payment();
            $paymentModel->updateStatus($payment_id, 'failed', $tracking_id);
            echo json_encode(['status' => 'failed', 'message' => 'Payment was declined or failed.']);
            exit;
        }

    echo json_encode(['status' => 'pending']);
    exit;
}

// ── Also handle PesaPal redirect back (card payments) ──────────────────
// PesaPal redirects here with: ?OrderTrackingId=xxx&OrderMerchantReference=xxx
if (!$payment_id && isset($_GET['OrderTrackingId'])) {
    $tracking_id = $_GET['OrderTrackingId'];
    $payRec = getRow("SELECT * FROM payments WHERE transaction_reference = ?", [$tracking_id]);
    if ($payRec) {
        $payment_id = $payRec['payment_id'];
        $booking_id = $payRec['booking_id'];
    }
}

$payRec  = $payment_id ? getRow("SELECT * FROM payments WHERE payment_id = ?", [$payment_id]) : null;
$booking = $payRec ? getRow(
    "SELECT b.*, r.room_number, rt.type_name FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN room_types rt ON r.room_type_id = rt.type_id
     WHERE b.booking_id = ?",
    [$payRec['booking_id']]
) : null;

$method      = $payRec['payment_method'] ?? 'mtn_momo';
$isMobile    = in_array($method, ['mtn_momo', 'airtel_money']);
$methodLabel = ['mtn_momo' => 'MTN Mobile Money', 'airtel_money' => 'Airtel Money', 'card' => 'Card'][$method] ?? ucfirst($method);
$isDemoPage  = str_starts_with($tracking_id, 'DEMO-');

$page_title = 'Processing Payment…';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-6 col-md-8">

    <div class="card border-0 rounded-4 shadow-lg text-center overflow-hidden" id="statusCard">

        <!-- Animated top bar -->
        <div style="height:4px;background:linear-gradient(90deg,#4f46e5,#7c3aed,#4f46e5);background-size:200%;animation:shimmer 2s infinite linear;"></div>

        <?php if ($isDemoPage): ?>
        <!-- Demo mode banner -->
        <div style="background:rgba(251,191,36,0.12);border-bottom:1px solid rgba(251,191,36,0.25);padding:8px 20px;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="fas fa-flask" style="color:#fbbf24;font-size:0.8rem;"></i>
            <span style="font-size:0.75rem;font-weight:700;color:#fbbf24;text-transform:uppercase;letter-spacing:.06em;">Demo Mode</span>
            <span style="font-size:0.75rem;color:#94a3b8;">— Payment will auto-confirm in ~10s</span>
        </div>
        <?php endif; ?>

        <div class="card-body p-5" id="pendingView">
            <!-- Animated pulse ring -->
            <div style="position:relative;width:90px;height:90px;margin:0 auto 24px;">
                <div style="position:absolute;inset:0;border-radius:50%;border:3px solid rgba(79,70,229,0.3);animation:pulse-ring 2s ease-out infinite;"></div>
                <div style="position:absolute;inset:8px;border-radius:50%;border:3px solid rgba(79,70,229,0.5);animation:pulse-ring 2s ease-out 0.5s infinite;"></div>
                <div style="position:absolute;inset:16px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-<?php echo $isMobile ? 'mobile-alt' : 'credit-card'; ?>" style="color:white;font-size:1.1rem;"></i>
                </div>
            </div>

            <?php if ($isMobile): ?>
            <h2 style="font-size:1.2rem;font-weight:800;color:#f1f5f9;margin-bottom:8px;">Check Your Phone!</h2>
            <p style="color:#94a3b8;font-size:0.9rem;margin-bottom:24px;line-height:1.6;">
                A <strong style="color:#f1f5f9;"><?php echo $methodLabel; ?></strong> payment request has been sent to your phone.<br>
                <strong style="color:#fbbf24;">Open it and enter your PIN</strong> to complete the payment.
            </p>

            <!-- Steps -->
            <div style="background:rgba(255,255,255,0.04);border-radius:12px;padding:16px;margin-bottom:24px;text-align:left;">
                <?php
                $steps = [
                    ['fas fa-mobile-alt','#818cf8', 'A notification appears on your phone'],
                    ['fas fa-hand-pointer','#fbbf24','Tap to open the payment prompt'],
                    ['fas fa-lock','#4ade80',  'Enter your mobile money PIN'],
                    ['fas fa-check-circle','#4ade80','Payment confirmed automatically here'],
                ];
                foreach ($steps as $i => [$icon, $col, $text]):
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:8px 0;<?php echo $i < 3 ? 'border-bottom:1px solid rgba(255,255,255,0.06);' : ''; ?>">
                    <div style="width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,0.07);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="<?php echo $icon; ?>" style="color:<?php echo $col; ?>;font-size:0.8rem;"></i>
                    </div>
                    <span style="font-size:0.82rem;color:#cbd5e1;"><?php echo $text; ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <?php else: ?>
            <h2 style="font-size:1.2rem;font-weight:800;color:#f1f5f9;margin-bottom:8px;">Verifying Payment…</h2>
            <p style="color:#94a3b8;font-size:0.9rem;margin-bottom:24px;">Please wait while we confirm your payment.</p>
            <?php endif; ?>

            <!-- Amount -->
            <?php if ($booking && $payRec): ?>
            <div style="background:rgba(79,70,229,0.12);border:1px solid rgba(79,70,229,0.3);border-radius:12px;padding:14px 20px;display:flex;justify-content:space-between;margin-bottom:24px;">
                <span style="font-size:0.82rem;color:#94a3b8;">Room <?php echo htmlspecialchars($booking['room_number']); ?> — <?php echo htmlspecialchars($booking['semester']); ?></span>
                <span style="font-size:0.95rem;font-weight:800;color:#f1f5f9;"><?php echo formatCurrency($payRec['amount']); ?></span>
            </div>
            <?php endif; ?>

            <!-- Countdown -->
            <div style="font-size:0.78rem;color:#64748b;margin-bottom:16px;">
                <?php if ($isMobile): ?>
                Auto-checking status in <strong id="countdown" style="color:#818cf8;">5</strong>s &bull;
                <span id="checkCount" style="color:#475569;">0 checks done</span>
                <?php endif; ?>
            </div>

            <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
                <button onclick="checkNow()" id="checkBtn"
                        style="padding:10px 24px;border-radius:999px;background:rgba(79,70,229,0.2);border:1px solid rgba(79,70,229,0.4);color:#818cf8;font-size:0.85rem;font-weight:600;cursor:pointer;">
                    <i class="fas fa-sync-alt me-1"></i>Check Now
                </button>
                <a href="../payments.php"
                   style="padding:10px 24px;border-radius:999px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#94a3b8;font-size:0.85rem;font-weight:600;text-decoration:none;display:inline-block;">
                   Cancel
                </a>
            </div>
            <div style="margin-top:16px;">
                <a href="../payments.php" style="font-size:0.75rem;color:#475569;text-decoration:none;">
                    Already paid? <span style="color:#818cf8;">Go to My Payments</span>
                </a>
            </div>
        </div>

        <!-- Success view (hidden) -->
        <div class="card-body p-5" id="successView" style="display:none;">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(34,197,94,0.15);border:2px solid rgba(34,197,94,0.4);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fas fa-check-double" style="color:#4ade80;font-size:1.8rem;"></i>
            </div>
            <h2 style="font-weight:900;color:#4ade80;margin-bottom:8px;">Payment Successful!</h2>
            <p style="color:#94a3b8;margin-bottom:28px;">Your booking has been confirmed.</p>
            <div style="display:flex;flex-direction:column;gap:12px;align-items:center;">
                <a id="receiptBtn" href="receipt.php"
                   style="display:inline-flex;align-items:center;gap:10px;padding:14px 36px;border-radius:999px;background:linear-gradient(135deg,#16a34a,#15803d);color:white;font-weight:800;text-decoration:none;font-size:0.95rem;box-shadow:0 4px 16px rgba(22,163,74,0.35);">
                    <i class="fas fa-file-invoice"></i> View & Download Receipt
                </a>
                <a href="../payments.php"
                   style="font-size:0.82rem;color:#94a3b8;text-decoration:none;">
                    Go to My Payments
                </a>
            </div>
        </div>

        <!-- Failed view (hidden) -->
        <div class="card-body p-5" id="failedView" style="display:none;">
            <div style="width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,0.15);border:2px solid rgba(239,68,68,0.4);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                <i class="fas fa-times-circle" style="color:#f87171;font-size:1.8rem;"></i>
            </div>
            <h2 style="font-weight:900;color:#f87171;margin-bottom:8px;">Payment Failed</h2>
            <p style="color:#94a3b8;margin-bottom:24px;" id="failMsg">The payment was not completed.</p>
            <a href="initiate.php?booking_id=<?php echo $payRec['booking_id'] ?? 0; ?>"
               style="display:inline-block;padding:12px 32px;border-radius:999px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;font-weight:700;text-decoration:none;">
                <i class="fas fa-redo me-2"></i>Try Again
            </a>
        </div>

    </div>
</div>
</div>
</div>

<style>
@keyframes pulse-ring {
    0%   { transform:scale(1);   opacity:1; }
    100% { transform:scale(1.5); opacity:0; }
}
@keyframes shimmer {
    0%   { background-position:0 0; }
    100% { background-position:200% 0; }
}
</style>

<script>
var paymentId  = <?php echo (int)$payment_id; ?>;
var trackingId = <?php echo json_encode($tracking_id); ?>;
var checks     = 0;
var maxChecks  = 48; // ~4 min at 5s intervals
var timer;

function checkNow() {
    clearInterval(timer);
    poll();
}

function poll() {
    checks++;
    var countEl = document.getElementById('checkCount');
    if (countEl) countEl.textContent = checks + ' check' + (checks !== 1 ? 's' : '') + ' done';

    var url = 'status.php?poll=1&payment_id=' + paymentId + '&tracking_id=' + encodeURIComponent(trackingId);
    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'completed') {
                showSuccess(data.payment_id);
            } else if (data.status === 'failed') {
                showFailed(data.message || 'Payment was declined.');
            } else if (checks >= maxChecks) {
                showFailed('Payment timed out. Please check My Payments page or contact support.');
            } else {
                startCountdown();
            }
        })
        .catch(() => { if (checks < maxChecks) startCountdown(); });
}

function startCountdown() {
    var n = 5;
    document.getElementById('countdown').textContent = n;
    clearInterval(timer);
    timer = setInterval(function() {
        n--;
        document.getElementById('countdown').textContent = n;
        if (n <= 0) { clearInterval(timer); poll(); }
    }, 1000);
}

function showSuccess(pid) {
    var receiptId = pid || paymentId;
    clearInterval(timer);
    document.getElementById('pendingView').style.display = 'none';
    document.getElementById('successView').style.display = 'block';
    document.getElementById('receiptBtn').href = 'receipt.php?id=' + receiptId;
}

function showFailed(msg) {
    clearInterval(timer);
    document.getElementById('pendingView').style.display = 'none';
    document.getElementById('failedView').style.display  = 'block';
    if (msg) document.getElementById('failMsg').textContent = msg;
}

// Start polling
<?php if ($isMobile): ?>
startCountdown();
<?php else: ?>
poll(); // Card: check immediately (PesaPal already redirected back)
<?php endif; ?>
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
