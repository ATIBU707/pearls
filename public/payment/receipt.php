<?php
/**
 * Payment e-Receipt Page
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/models/Payment.php';
require_once APP_PATH . '/models/Booking.php';

requireLogin();

$payment_id = (int)($_GET['id'] ?? 0);
if (!$payment_id) {
    header('Location: ../payments.php');
    exit;
}

$paymentModel = new Payment();
$bookingModel = new Booking();

$payment = $paymentModel->getById($payment_id);
if (!$payment) {
    redirectWithMessage('../payments.php', 'Receipt not found.', 'error');
}

$booking = $bookingModel->getDetails($payment['booking_id']);

// Security: only the booking owner or admin can view
if (!isAdmin() && $booking['user_id'] != getCurrentUserId()) {
    redirectWithMessage('../payments.php', 'Access denied.', 'error');
}

// ── Generate / retrieve QR token ─────────────────────────────────────────
// Token stored in DB on payment completion: sha256(payment_id . booking_id . amount)
// If already stored use it; otherwise compute on the fly for backward compat
$qr_token = $payment['receipt_token']
    ?: hash('sha256', $payment['payment_id'] . $payment['booking_id'] . $payment['amount']);

$verify_url = rtrim(APP_URL, '/') . '/payment/verify-receipt.php?token=' . $qr_token;

// Build QR code URL via Google Charts API (no server-side library needed)
$qr_img_url = 'https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=6&color=1e1b4b&bgcolor=ffffff&data=' . urlencode($verify_url);

// Payment date display
$pay_date = $payment['payment_date'] ?? $payment['created_at'];

$page_title = 'Payment Receipt — ' . $booking['booking_code'];
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <!-- Action Bar (hidden on print) -->
            <div class="d-flex justify-content-between align-items-center mb-4 no-print">
                <a href="<?php echo isAdmin() ? '../admin/payments.php' : '../payments.php'; ?>"
                   class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="fas fa-arrow-left me-1"></i>
                    <?php echo isAdmin() ? 'Back to Payments' : 'My Payments'; ?>
                </a>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                    <button class="btn btn-sm btn-primary rounded-pill" onclick="savePDF()">
                        <i class="fas fa-file-pdf me-1"></i>Save as PDF
                    </button>
                </div>
            </div>

            <!-- ══ RECEIPT CARD ══ -->
            <div id="receiptCard" class="card border-0 shadow-lg overflow-hidden" style="border-radius:20px;">

                <!-- Header Strip — full-width hostel photo banner -->
                <div style="position:relative;height:160px;overflow:hidden;background:linear-gradient(135deg,#1e1b4b,#4f46e5);">
                    <!-- Hostel photo as full background -->
                    <img src="<?php echo APP_URL; ?>assets/images/hostel-exterior.jpg"
                         alt="Pearls of Wisdom Hostel"
                         style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center;display:block;">
                    <!-- Dark overlay for text readability -->
                    <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(30,27,75,0.78),rgba(79,70,229,0.72));"></div>
                    <!-- Text content -->
                    <div style="position:relative;z-index:2;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px 36px;text-align:center;">
                        <div style="font-size:0.65rem;color:rgba(255,255,255,0.65);letter-spacing:.12em;font-weight:700;text-transform:uppercase;margin-bottom:6px;">
                            Official Payment Receipt
                        </div>
                        <h2 style="color:white;font-weight:900;margin:0 0 4px;font-size:1.4rem;letter-spacing:-0.01em;text-shadow:0 2px 8px rgba(0,0,0,0.4);">Pearls of Wisdom Hostel</h2>
                        <p style="color:rgba(255,255,255,0.75);margin:0;font-size:0.8rem;text-shadow:0 1px 4px rgba(0,0,0,0.3);">Kasindikwa Village, Fort Portal, Uganda</p>
                        <p style="color:rgba(255,255,255,0.5);margin:4px 0 0;font-size:0.7rem;">+256 765 536 881 &bull; admin@pearlswisdom.com</p>
                    </div>
                </div>

                <!-- Status Banner -->
                <div style="background:<?php echo $payment['status']==='completed' ? 'rgba(34,197,94,0.12)' : 'rgba(251,191,36,0.12)'; ?>;padding:14px 36px;display:flex;align-items:center;justify-content:center;gap:10px;border-bottom:1px solid rgba(255,255,255,0.07);">
                    <?php if ($payment['status'] === 'completed'): ?>
                    <i class="fas fa-check-double" style="color:#4ade80;font-size:1.2rem;"></i>
                    <span style="color:#4ade80;font-weight:800;font-size:1rem;letter-spacing:.03em;">PAYMENT CONFIRMED</span>
                    <?php else: ?>
                    <i class="fas fa-clock" style="color:#fbbf24;font-size:1.2rem;"></i>
                    <span style="color:#fbbf24;font-weight:800;font-size:1rem;"><?php echo strtoupper($payment['status']); ?></span>
                    <?php endif; ?>
                </div>

                <div style="padding:30px 36px;">

                    <!-- Amount highlight -->
                    <div style="background:linear-gradient(135deg,rgba(79,70,229,0.15),rgba(124,58,237,0.1));border:1px solid rgba(79,70,229,0.25);border-radius:14px;padding:20px 24px;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
                        <div>
                            <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:4px;">Total Amount Paid</div>
                            <div style="font-size:1.8rem;font-weight:900;color:#f1f5f9;"><?php echo formatCurrency($payment['amount']); ?></div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-bottom:4px;">Receipt #</div>
                            <div style="font-size:0.9rem;font-weight:700;color:#818cf8;">PMT-<?php echo str_pad($payment['payment_id'],6,'0',STR_PAD_LEFT); ?></div>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <?php
                    $rows = [
                        ['Booking Code',     '<code style="color:#818cf8;font-weight:700;">' . htmlspecialchars($booking['booking_code']) . '</code>'],
                        ['Transaction Ref',  htmlspecialchars($payment['transaction_reference'] ?: 'N/A')],
                        ['Student Name',     htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name'])],
                        ['Student ID',       htmlspecialchars($booking['student_id'] ?: '—')],
                        ['Email',            htmlspecialchars($booking['email'])],
                        ['Room',             'Room ' . htmlspecialchars($booking['room_number']) . ' &nbsp;<span style="color:#64748b;font-size:0.8rem;">(' . htmlspecialchars($booking['type_name']) . ')</span>'],
                        ['Semester',         htmlspecialchars($booking['semester'])],
                        ['Check-in Date',    date('M d, Y', strtotime($booking['check_in_date']))],
                        ['Payment Method',   ucwords(str_replace('_', ' ', $payment['payment_method']))],
                        ['Payment Date',     date('M d, Y  H:i', strtotime($pay_date))],
                    ];
                    ?>
                    <div style="margin-bottom:24px;">
                        <?php foreach ($rows as $i => [$label, $value]): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;<?php echo $i < count($rows)-1 ? 'border-bottom:1px solid rgba(255,255,255,0.07);' : ''; ?>">
                            <span style="font-size:0.8rem;color:#94a3b8;font-weight:500;"><?php echo $label; ?></span>
                            <span style="font-size:0.875rem;color:#f1f5f9;font-weight:600;text-align:right;"><?php echo $value; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- QR Code Section -->
                    <div style="border:1px solid rgba(129,140,248,0.3);border-radius:14px;padding:20px;display:flex;align-items:center;gap:20px;background:rgba(79,70,229,0.07);margin-bottom:24px;">
                        <div style="flex-shrink:0;background:white;padding:8px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.3);">
                            <img src="<?php echo htmlspecialchars($qr_img_url); ?>"
                                 alt="Verification QR Code"
                                 width="120" height="120"
                                 style="display:block;border-radius:6px;">
                        </div>
                        <div>
                            <div style="font-size:0.75rem;color:#818cf8;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">
                                <i class="fas fa-qrcode me-1"></i>Authenticity QR Code
                            </div>
                            <div style="font-size:0.82rem;color:#cbd5e1;line-height:1.5;margin-bottom:8px;">
                                Scan this QR code with any phone camera to verify this receipt is genuine and unmodified.
                            </div>
                            <div style="font-size:0.7rem;color:#64748b;word-break:break-all;font-family:monospace;">
                                Token: <span style="color:#818cf8;"><?php echo $qr_token; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer note -->
                    <div style="text-align:center;padding-top:16px;border-top:1px solid rgba(255,255,255,0.08);">
                        <p style="font-size:0.75rem;color:#64748b;margin:0 0 4px;">
                            This is an officially generated e-receipt. For queries contact:
                            <strong style="color:#94a3b8;">admin@pearlswisdom.com</strong> &bull; +256 765 536 881
                        </p>
                        <p style="font-size:0.72rem;color:#475569;margin:0;">
                            Generated on <?php echo date('M d, Y \a\t H:i:s'); ?>
                        </p>
                    </div>

                </div><!-- /padding -->
            </div><!-- /receipt card -->

        </div>
    </div>
</div>

<style>
/* Ensure background images print */
* { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }

@media print {
    /* Hide everything except the receipt card */
    .no-print,
    .sidebar, aside, .topbar, header, nav, footer,
    .app-footer { display: none !important; }

    /* Reset layout */
    body { background: white !important; margin: 0 !important; padding: 0 !important; }
    .app-wrapper { margin: 0 !important; }
    .app-main { padding: 0 !important; margin: 0 !important; }
    .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
    .row { margin: 0 !important; }
    .col-lg-7 { width: 100% !important; max-width: 100% !important; padding: 0 !important; }

    /* Force single page */
    @page {
        size: A4 portrait;
        margin: 6mm 8mm;
    }

    /* Receipt card */
    #receiptCard {
        box-shadow: none !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        width: 100% !important;
        page-break-inside: avoid;
        break-inside: avoid;
        overflow: hidden !important;
    }

    /* Header banner — keep photo */
    #receiptCard > div:first-child {
        height: 110px !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    #receiptCard > div:first-child img {
        width: 100% !important;
        height: 110px !important;
        object-fit: cover !important;
    }

    /* Compact body padding */
    #receiptCard > div:nth-child(3) { padding: 14px 20px !important; }

    /* Reduce all font sizes */
    #receiptCard { font-size: 0.75rem !important; }
    #receiptCard h2 { font-size: 1rem !important; margin-bottom: 2px !important; }
    #receiptCard p  { font-size: 0.7rem !important; margin: 0 !important; }

    /* Amount box compact */
    #receiptCard [style*="padding:20px 24px"] { padding: 10px 14px !important; }
    #receiptCard [style*="font-size:1.8rem"]  { font-size: 1.2rem !important; }

    /* Row items compact */
    #receiptCard [style*="padding:10px 0"] { padding: 4px 0 !important; }
    #receiptCard [style*="font-size:0.8rem"]  { font-size: 0.72rem !important; }
    #receiptCard [style*="font-size:0.875rem"]{ font-size: 0.75rem !important; }

    /* Status banner compact */
    #receiptCard [style*="padding:14px 36px"] { padding: 6px 20px !important; }

    /* QR section compact */
    #receiptCard [style*="padding:20px;display:flex"] { padding: 10px !important; }
    #receiptCard img[width="120"] { width: 80px !important; height: 80px !important; }

    /* Token text */
    #receiptCard [style*="word-break:break-all"] {
        font-size: 0.6rem !important;
        word-break: break-all !important;
        font-family: monospace !important;
    }

    /* Footer note compact */
    #receiptCard [style*="padding-top:16px"] { padding-top: 8px !important; }
}
</style>

<script>
function savePDF() {
    var orig = document.title;
    document.title = 'Receipt-<?php echo $booking["booking_code"]; ?>';
    window.print();
    document.title = orig;
}
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
