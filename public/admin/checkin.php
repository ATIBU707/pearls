<?php
/**
 * Admin — QR Code Check-In Scanner
 * Scan student receipt QR codes to verify payment and check them in.
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/NotificationService.php';

requireAdminAuth();

$result  = null;
$error   = '';
$success = '';

// ── Handle AJAX token lookup ──────────────────────────────────────────────
if (isset($_GET['lookup']) && isset($_GET['token'])) {
    header('Content-Type: application/json');
    $token = trim($_GET['token']);

    $row = getRow(
        "SELECT p.*, b.booking_id, b.booking_code, b.semester, b.status AS booking_status,
                b.check_in_date, b.user_id,
                r.room_number, rt.type_name,
                u.first_name, u.last_name, u.email, u.student_id, u.phone_number
         FROM payments p
         JOIN bookings b ON p.booking_id = b.booking_id
         JOIN rooms r    ON b.room_id    = r.room_id
         JOIN room_types rt ON r.room_type_id = rt.type_id
         JOIN users u    ON b.user_id    = u.user_id
         WHERE p.receipt_token = ? AND p.status = 'completed'
         LIMIT 1",
        [$token]
    );

    if (!$row) {
        // Fallback: compute token on-the-fly
        $candidates = getRows(
            "SELECT p.*, b.booking_id, b.booking_code, b.semester, b.status AS booking_status,
                    b.check_in_date, b.user_id,
                    r.room_number, rt.type_name,
                    u.first_name, u.last_name, u.email, u.student_id, u.phone_number
             FROM payments p
             JOIN bookings b ON p.booking_id = b.booking_id
             JOIN rooms r    ON b.room_id    = r.room_id
             JOIN room_types rt ON r.room_type_id = rt.type_id
             JOIN users u    ON b.user_id    = u.user_id
             WHERE p.status = 'completed' AND p.receipt_token IS NULL LIMIT 200"
        );
        foreach ($candidates as $c) {
            $expected = hash('sha256', $c['payment_id'] . $c['booking_id'] . $c['amount']);
            if (hash_equals($expected, $token)) {
                $row = $c;
                executeQuery("UPDATE payments SET receipt_token = ? WHERE payment_id = ?", [$token, $c['payment_id']]);
                break;
            }
        }
    }

    if (!$row) {
        echo json_encode(['valid' => false, 'message' => 'Invalid or unrecognised QR code.']);
        exit;
    }

    echo json_encode([
        'valid'           => true,
        'booking_id'      => $row['booking_id'],
        'booking_code'    => $row['booking_code'],
        'booking_status'  => $row['booking_status'],
        'student_name'    => $row['first_name'] . ' ' . $row['last_name'],
        'student_id'      => $row['student_id'] ?: '—',
        'email'           => $row['email'],
        'phone'           => $row['phone_number'] ?: '—',
        'room'            => 'Room ' . $row['room_number'] . ' (' . $row['type_name'] . ')',
        'semester'        => $row['semester'],
        'check_in_date'   => date('M d, Y', strtotime($row['check_in_date'])),
        'amount_paid'     => formatCurrency($row['amount']),
        'payment_method'  => ucwords(str_replace('_', ' ', $row['payment_method'])),
        'already_checked' => $row['booking_status'] === 'checked_in',
    ]);
    exit;
}

// ── Handle check-in POST ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin_booking_id'])) {
    verifyCsrfOrDie();
    $bid = (int)$_POST['checkin_booking_id'];

    $brow = getRow(
        "SELECT b.*, r.room_id, r.room_number, u.first_name, u.last_name
         FROM bookings b
         JOIN rooms r ON b.room_id = r.room_id
         JOIN users u ON b.user_id = u.user_id
         WHERE b.booking_id = ?",
        [$bid]
    );

    if (!$brow) {
        $error = 'Booking not found.';
    } elseif ($brow['status'] === 'checked_in') {
        $error = $brow['first_name'] . ' is already checked in.';
    } elseif (!in_array($brow['status'], ['confirmed', 'pending'])) {
        $error = 'Cannot check in — booking status is: ' . ucfirst($brow['status']);
    } else {
        executeQuery("UPDATE bookings SET status = 'checked_in' WHERE booking_id = ?", [$bid]);
        executeQuery("UPDATE rooms SET status = 'occupied' WHERE room_id = ?", [$brow['room_id']]);

        NotificationService::bookingStatusChanged(
            (int)$brow['user_id'],
            $brow['booking_code'],
            $brow['room_number'],
            'checked_in',
            $bid
        );

        $success = '✅ ' . $brow['first_name'] . ' ' . $brow['last_name'] .
                   ' checked in to Room ' . $brow['room_number'] . ' successfully!';
        logMessage("Admin checked in: {$brow['booking_code']} — Room {$brow['room_number']}", 'activity');
    }
}

// ── Recent check-ins ──────────────────────────────────────────────────────
$recentCheckins = getRows(
    "SELECT b.booking_code, b.updated_at, r.room_number, u.first_name, u.last_name
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN users u ON b.user_id = u.user_id
     WHERE b.status = 'checked_in'
     ORDER BY b.updated_at DESC
     LIMIT 10"
);

$page_title = 'QR Check-In Scanner';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-qrcode me-2 text-primary"></i>QR Check-In Scanner</h1>
            <p class="text-muted mb-0">Scan a student's receipt QR code to verify payment and check them in</p>
        </div>
        <a href="bookings.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-list me-1"></i>All Bookings
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- ── Scanner ── -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-camera me-2 text-primary"></i>Camera Scanner</h5>
                </div>
                <div class="card-body text-center p-4">
                    <!-- Video preview -->
                    <div id="scannerWrap" style="position:relative;border-radius:14px;overflow:hidden;background:#0f0c29;aspect-ratio:1;max-width:320px;margin:0 auto 16px;">
                        <video id="qrVideo" style="width:100%;height:100%;object-fit:cover;" playsinline></video>
                        <!-- Scan overlay -->
                        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none;">
                            <div id="scanFrame" style="width:200px;height:200px;border:3px solid #4f46e5;border-radius:12px;box-shadow:0 0 0 9999px rgba(0,0,0,0.45);animation:scanPulse 2s ease-in-out infinite;"></div>
                        </div>
                        <div id="scanLine" style="position:absolute;left:calc(50% - 100px);width:200px;height:3px;background:linear-gradient(90deg,transparent,#4f46e5,transparent);animation:scanLine 2s linear infinite;top:50%;"></div>
                    </div>

                    <div id="scanStatus" style="font-size:0.85rem;color:#64748b;margin-bottom:16px;">
                        <i class="fas fa-circle-notch fa-spin me-1"></i>Starting camera…
                    </div>

                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <button id="startBtn" class="btn btn-primary btn-sm" onclick="startScanner()">
                            <i class="fas fa-play me-1"></i>Start Camera
                        </button>
                        <button id="stopBtn" class="btn btn-outline-secondary btn-sm d-none" onclick="stopScanner()">
                            <i class="fas fa-stop me-1"></i>Stop
                        </button>
                    </div>

                    <hr class="my-3">

                    <!-- Manual token entry -->
                    <div>
                        <label class="form-label text-start d-block" style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;">Or enter token manually</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="manualToken" class="form-control" placeholder="Paste QR token here…">
                            <button class="btn btn-primary" onclick="lookupToken(document.getElementById('manualToken').value)">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Result panel ── -->
        <div class="col-lg-7">
            <!-- Idle state -->
            <div id="resultIdle" class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column align-items-center justify-content-center text-center py-5">
                    <div style="width:80px;height:80px;background:#eef2ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                        <i class="fas fa-qrcode" style="font-size:2rem;color:#4f46e5;"></i>
                    </div>
                    <h5 style="color:#1e293b;">Waiting for QR Scan</h5>
                    <p class="text-muted small">Point the camera at a student's receipt QR code to verify their payment and check them in.</p>
                </div>
            </div>

            <!-- Result card (shown after scan) -->
            <div id="resultCard" class="card border-0 shadow-sm d-none">
                <div id="resultHeader" class="card-header py-3">
                    <div class="d-flex align-items-center gap-3">
                        <div id="resultIcon" style="width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;"></div>
                        <div>
                            <div id="resultTitle" style="font-size:1rem;font-weight:800;"></div>
                            <div id="resultSub" style="font-size:0.78rem;color:#64748b;"></div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div id="resultDetails"></div>
                    <form method="POST" id="checkinForm" class="mt-3 d-none">
                        <?php echo getCSRFTokenInput(); ?>
                        <input type="hidden" name="checkin_booking_id" id="checkinBookingId">
                        <button type="submit" class="btn btn-success w-100 fw-bold py-3 rounded-pill">
                            <i class="fas fa-sign-in-alt me-2"></i>Confirm Check-In
                        </button>
                    </form>
                    <button class="btn btn-outline-secondary w-100 mt-2 btn-sm" onclick="resetScanner()">
                        <i class="fas fa-redo me-1"></i>Scan Another
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent check-ins -->
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header py-3">
            <h5 class="mb-0"><i class="fas fa-history me-2 text-success"></i>Recent Check-Ins</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentCheckins)): ?>
            <div class="text-center py-4 text-muted small">No check-ins yet today.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr>
                        <th class="ps-4">Student</th>
                        <th>Booking Code</th>
                        <th>Room</th>
                        <th>Checked In At</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($recentCheckins as $c): ?>
                    <tr>
                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($c['first_name'].' '.$c['last_name']); ?></td>
                        <td><code class="text-primary"><?php echo htmlspecialchars($c['booking_code']); ?></code></td>
                        <td>Room <?php echo htmlspecialchars($c['room_number']); ?></td>
                        <td class="text-muted small"><?php echo date('M d, Y H:i', strtotime($c['updated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
@keyframes scanPulse { 0%,100%{opacity:1} 50%{opacity:0.6} }
@keyframes scanLine  { 0%{top:calc(50% - 100px)} 50%{top:calc(50% + 100px)} 100%{top:calc(50% - 100px)} }
</style>

<!-- jsQR library for QR decoding -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
var videoEl   = document.getElementById('qrVideo');
var stream    = null;
var scanning  = false;
var canvas    = document.createElement('canvas');
var ctx       = canvas.getContext('2d');
var lastToken = '';

function startScanner() {
    document.getElementById('scanStatus').innerHTML = '<i class="fas fa-circle-notch fa-spin me-1"></i>Requesting camera…';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(s) {
            stream   = s;
            scanning = true;
            videoEl.srcObject = s;
            videoEl.play();
            document.getElementById('startBtn').classList.add('d-none');
            document.getElementById('stopBtn').classList.remove('d-none');
            document.getElementById('scanStatus').innerHTML = '<i class="fas fa-circle text-success me-1"></i>Camera active — point at QR code';
            requestAnimationFrame(tick);
        })
        .catch(function(e) {
            document.getElementById('scanStatus').innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-1"></i>Camera error: ' + e.message;
        });
}

function stopScanner() {
    scanning = false;
    if (stream) { stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
    videoEl.srcObject = null;
    document.getElementById('startBtn').classList.remove('d-none');
    document.getElementById('stopBtn').classList.add('d-none');
    document.getElementById('scanStatus').innerHTML = '<i class="fas fa-camera-slash me-1"></i>Camera stopped';
}

function tick() {
    if (!scanning) return;
    if (videoEl.readyState === videoEl.HAVE_ENOUGH_DATA) {
        canvas.width  = videoEl.videoWidth;
        canvas.height = videoEl.videoHeight;
        ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
        var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: 'dontInvert' });
        if (code && code.data) {
            var raw = code.data;
            var token = raw;
            try {
                var url = new URL(raw);
                token = url.searchParams.get('token') || raw;
            } catch(e) {}
            if (token && token !== lastToken) {
                lastToken = token;
                stopScanner();
                // Redirect to verify-receipt page — shows all payment details + check-in button
                window.location.href = '<?php echo APP_URL; ?>payment/verify-receipt.php?token=' + encodeURIComponent(token);
            }
        }
    }
    requestAnimationFrame(tick);
}

function lookupToken(token) {
    if (!token) return;
    stopScanner();
    window.location.href = '<?php echo APP_URL; ?>payment/verify-receipt.php?token=' + encodeURIComponent(token);
}

function showResult(data) {
    document.getElementById('resultIdle').classList.add('d-none');
    document.getElementById('resultCard').classList.remove('d-none');

    if (!data.valid) {
        document.getElementById('resultHeader').style.background = '#fef2f2';
        document.getElementById('resultIcon').innerHTML = '<i class="fas fa-times-circle" style="color:#dc2626;"></i>';
        document.getElementById('resultIcon').style.background = '#fee2e2';
        document.getElementById('resultTitle').innerHTML = '<span style="color:#dc2626;">Invalid QR Code</span>';
        document.getElementById('resultSub').textContent  = data.message || 'Receipt not found.';
        document.getElementById('resultDetails').innerHTML = '';
        document.getElementById('checkinForm').classList.add('d-none');
        return;
    }

    var alreadyIn = data.already_checked;
    var headerBg  = alreadyIn ? '#f0fdf4' : '#f8f9ff';
    var iconColor = alreadyIn ? '#16a34a' : '#4f46e5';
    var iconBg    = alreadyIn ? '#dcfce7' : '#eef2ff';
    var iconHtml  = alreadyIn ? '<i class="fas fa-check-double"></i>' : '<i class="fas fa-id-card"></i>';
    var titleHtml = alreadyIn
        ? '<span style="color:#16a34a;">Already Checked In</span>'
        : '<span style="color:#1e293b;">Valid Receipt — Ready to Check In</span>';
    var subText   = alreadyIn ? 'This student is already checked in.' : 'Payment verified. Confirm check-in below.';

    document.getElementById('resultHeader').style.background = headerBg;
    document.getElementById('resultIcon').innerHTML = iconHtml;
    document.getElementById('resultIcon').style.background = iconBg;
    document.getElementById('resultIcon').style.color = iconColor;
    document.getElementById('resultTitle').innerHTML = titleHtml;
    document.getElementById('resultSub').textContent  = subText;

    var rows = [
        ['Student',        data.student_name],
        ['Student ID',     data.student_id],
        ['Booking Code',   '<code style="color:#4f46e5;font-weight:700;">' + data.booking_code + '</code>'],
        ['Room',           data.room],
        ['Semester',       data.semester],
        ['Check-in Date',  data.check_in_date],
        ['Amount Paid',    '<strong style="color:#16a34a;">' + data.amount_paid + '</strong>'],
        ['Payment Method', data.payment_method],
        ['Phone',          data.phone],
    ];

    var html = '<div style="background:#f8f9ff;border-radius:12px;padding:16px 20px;">';
    rows.forEach(function(r, i) {
        html += '<div style="display:flex;justify-content:space-between;padding:8px 0;' +
                (i < rows.length-1 ? 'border-bottom:1px solid #e8edf5;' : '') + '">' +
                '<span style="font-size:0.8rem;color:#64748b;">' + r[0] + '</span>' +
                '<span style="font-size:0.875rem;font-weight:600;color:#1e293b;text-align:right;">' + r[1] + '</span>' +
                '</div>';
    });
    html += '</div>';
    document.getElementById('resultDetails').innerHTML = html;

    if (!alreadyIn) {
        document.getElementById('checkinBookingId').value = data.booking_id;
        document.getElementById('checkinForm').classList.remove('d-none');
    } else {
        document.getElementById('checkinForm').classList.add('d-none');
    }
}

function showError(msg) {
    document.getElementById('scanStatus').innerHTML = '<i class="fas fa-exclamation-triangle text-danger me-1"></i>' + msg;
}

function resetScanner() {
    lastToken = '';
    document.getElementById('resultIdle').classList.remove('d-none');
    document.getElementById('resultCard').classList.add('d-none');
    document.getElementById('manualToken').value = '';
    document.getElementById('scanStatus').innerHTML = '<i class="fas fa-camera me-1"></i>Ready to scan';
    startScanner();
}

// Auto-start on page load
window.addEventListener('DOMContentLoaded', function() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        startScanner();
    } else {
        document.getElementById('scanStatus').innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-1"></i>Camera not available — use manual entry below';
    }
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
