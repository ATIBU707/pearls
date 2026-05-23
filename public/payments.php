<?php
/**
 * Student Payments Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';

requireLogin();
if (isAdmin()) { header('Location: admin/payments.php'); exit; }

$uid = getCurrentUserId();

// All payments for this student with booking/room data
$payments = getRows(
    "SELECT p.*, b.booking_code, b.semester, b.check_in_date, b.status AS booking_status,
            r.room_number, rt.type_name,
            b.booking_id
     FROM payments p
     JOIN bookings b  ON p.booking_id  = b.booking_id
     JOIN rooms r     ON b.room_id     = r.room_id
     JOIN room_types rt ON r.room_type_id = rt.type_id
     WHERE b.user_id = ?
     ORDER BY p.created_at DESC",
    [$uid]
);

// Pending bookings that have NO completed payment yet — include balance info
$pending_bookings = getRows(
    "SELECT b.*, r.room_number, r.price_per_semester, rt.type_name,
            COALESCE((SELECT SUM(amount) FROM payments WHERE booking_id = b.booking_id AND status = 'completed'), 0) AS paid_amount
     FROM bookings b
     JOIN rooms r       ON b.room_id      = r.room_id
     JOIN room_types rt ON r.room_type_id = rt.type_id
     WHERE b.user_id = ?
       AND b.status IN ('pending','confirmed')
       AND (r.price_per_semester - COALESCE((SELECT SUM(amount) FROM payments WHERE booking_id = b.booking_id AND status = 'completed'), 0)) > 0
     ORDER BY b.created_at DESC",
    [$uid]
);

// Totals
$total_paid    = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));
$total_pending = array_sum(array_map(fn($b) => $b['price_per_semester'] - $b['paid_amount'], $pending_bookings));

$page_title = 'My Payments';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container-fluid py-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Payments</h1>
            <p class="text-muted mb-0 small">Track your payment history and download e-receipts.</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:rgba(79,70,229,0.12);">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="background:rgba(79,70,229,0.2);border-radius:12px;padding:14px;">
                        <i class="fas fa-receipt" style="color:#818cf8;font-size:1.4rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Total Transactions</div>
                        <div style="font-size:1.5rem;font-weight:800;color:#f1f5f9;"><?php echo count($payments); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:rgba(34,197,94,0.1);">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="background:rgba(34,197,94,0.2);border-radius:12px;padding:14px;">
                        <i class="fas fa-check-circle" style="color:#4ade80;font-size:1.4rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Total Paid</div>
                        <div style="font-size:1.3rem;font-weight:800;color:#4ade80;"><?php echo formatCurrency($total_paid); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card border-0 shadow-sm rounded-4 h-100" style="background:rgba(251,191,36,0.1);">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div style="background:rgba(251,191,36,0.2);border-radius:12px;padding:14px;">
                        <i class="fas fa-clock" style="color:#fbbf24;font-size:1.4rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;font-weight:700;">Outstanding</div>
                        <div style="font-size:1.3rem;font-weight:800;color:#fbbf24;"><?php echo formatCurrency($total_pending); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-4 gap-2" id="payTabs">
        <li class="nav-item">
            <button class="nav-link active px-4" onclick="showTab('pending',this)">
                <i class="fas fa-clock me-1"></i>Pending
                <?php if ($pending_bookings): ?>
                <span class="badge bg-warning text-dark ms-1"><?php echo count($pending_bookings); ?></span>
                <?php endif; ?>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" onclick="showTab('history',this)">
                <i class="fas fa-history me-1"></i>Payment History
            </button>
        </li>
    </ul>

    <!-- ── PENDING TAB ── -->
    <div id="tab-pending">
        <?php if (empty($pending_bookings)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fas fa-check-double fa-3x mb-3" style="color:#4ade80;"></i>
                <h5 style="color:#f1f5f9;">All payments are up to date!</h5>
                <p class="text-muted">You have no outstanding payments.</p>
                <a href="rooms.php" class="btn btn-primary rounded-pill px-4 mt-2">
                    <i class="fas fa-door-open me-2"></i>Book a Room
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="row g-3">
            <?php foreach ($pending_bookings as $pb): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm rounded-4 h-100" style="border-left:4px solid #fbbf24 !important;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <div style="font-size:0.72rem;color:#94a3b8;text-transform:uppercase;font-weight:700;">Booking Code</div>
                                <code style="color:#818cf8;font-size:0.9rem;font-weight:700;"><?php echo htmlspecialchars($pb['booking_code']); ?></code>
                            </div>
                            <span class="badge bg-warning text-dark"><?php echo ucfirst($pb['status']); ?></span>
                        </div>

                        <div class="mb-3 p-3 rounded-3" style="background:rgba(255,255,255,0.05);">
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.8rem;color:#94a3b8;">Room</span>
                                <span style="font-size:0.85rem;font-weight:600;color:#f1f5f9;">Room <?php echo htmlspecialchars($pb['room_number']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.8rem;color:#94a3b8;">Type</span>
                                <span style="font-size:0.85rem;color:#cbd5e1;"><?php echo htmlspecialchars($pb['type_name']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.8rem;color:#94a3b8;">Semester</span>
                                <span style="font-size:0.85rem;color:#cbd5e1;"><?php echo htmlspecialchars($pb['semester']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.8rem;color:#94a3b8;">Check-in</span>
                                <span style="font-size:0.85rem;color:#cbd5e1;"><?php echo date('M d, Y', strtotime($pb['check_in_date'])); ?></span>
                            </div>
                            <?php if ($pb['paid_amount'] > 0): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span style="font-size:0.8rem;color:#4ade80;">Already Paid</span>
                                <span style="font-size:0.85rem;color:#4ade80;font-weight:600;"><?php echo formatCurrency($pb['paid_amount']); ?></span>
                            </div>
                            <?php endif; ?>
                            <hr style="border-color:rgba(255,255,255,0.08);margin:8px 0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-size:0.8rem;color:#94a3b8;">Total Fee</span>
                                <span style="font-size:0.85rem;color:#94a3b8;"><?php echo formatCurrency($pb['price_per_semester']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span style="font-size:0.8rem;color:#fbbf24;font-weight:700;">Balance Due</span>
                                <span style="font-size:1.1rem;font-weight:800;color:#fbbf24;"><?php echo formatCurrency($pb['price_per_semester'] - $pb['paid_amount']); ?></span>
                            </div>
                        </div>

                        <a href="payment/initiate.php?booking_id=<?php echo $pb['booking_id']; ?>"
                           class="btn btn-warning w-100 rounded-pill fw-bold" style="color:#1e1b4b;">
                            <i class="fas fa-credit-card me-2"></i>Pay Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── HISTORY TAB ── -->
    <div id="tab-history" style="display:none;">
        <?php if (empty($payments)): ?>
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <i class="fas fa-receipt fa-3x mb-3 text-muted opacity-50"></i>
                <h5 style="color:#f1f5f9;">No payment history yet.</h5>
                <p class="text-muted">Completed payments will appear here.</p>
            </div>
        </div>
        <?php else: ?>
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background:rgba(255,255,255,0.05);">
                        <tr>
                            <th class="ps-4" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;padding-top:16px;padding-bottom:16px;">Booking</th>
                            <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Room</th>
                            <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Method</th>
                            <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Date</th>
                            <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Amount</th>
                            <th style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Status</th>
                            <th class="text-end pe-4" style="font-size:0.72rem;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;font-weight:700;border:none;">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $p):
                        $smap = [
                            'completed' => ['bg:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3)', 'Completed'],
                            'pending'   => ['bg:rgba(251,191,36,0.15);color:#fbbf24;border:1px solid rgba(251,191,36,0.3)', 'Pending'],
                            'failed'    => ['bg:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3)', 'Failed'],
                            'refunded'  => ['bg:rgba(148,163,184,0.15);color:#94a3b8;border:1px solid rgba(148,163,184,0.3)', 'Refunded'],
                        ];
                        [$sstyle, $slabel] = $smap[$p['status']] ?? ['bg:rgba(148,163,184,0.15);color:#94a3b8;', ucfirst($p['status'])];
                    ?>
                    <tr style="border-color:rgba(255,255,255,0.06);">
                        <td class="ps-4">
                            <div style="font-size:0.82rem;font-weight:700;color:#818cf8;"><?php echo htmlspecialchars($p['booking_code']); ?></div>
                            <div style="font-size:0.72rem;color:#64748b;"><?php echo htmlspecialchars($p['semester']); ?></div>
                        </td>
                        <td>
                            <div style="font-size:0.85rem;font-weight:600;color:#f1f5f9;">Room <?php echo htmlspecialchars($p['room_number']); ?></div>
                            <div style="font-size:0.72rem;color:#64748b;"><?php echo htmlspecialchars($p['type_name']); ?></div>
                        </td>
                        <td style="font-size:0.82rem;color:#cbd5e1;"><?php echo ucwords(str_replace('_',' ',$p['payment_method'])); ?></td>
                        <td style="font-size:0.82rem;color:#94a3b8;"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                        <td style="font-size:0.9rem;font-weight:700;color:#f1f5f9;"><?php echo formatCurrency($p['amount']); ?></td>
                        <td>
                            <span style="padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;<?php echo $sstyle; ?>">
                                <?php echo $slabel; ?>
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <?php if ($p['status'] === 'completed'): ?>
                            <a href="payment/receipt.php?id=<?php echo $p['payment_id']; ?>"
                               class="btn btn-sm rounded-pill"
                               style="background:rgba(79,70,229,0.2);color:#818cf8;border:1px solid rgba(79,70,229,0.35);font-size:0.78rem;font-weight:600;"
                               title="View &amp; Print Receipt">
                                <i class="fas fa-download me-1"></i>Receipt
                            </a>
                            <?php else: ?>
                            <?php if (in_array($p['status'], ['pending','failed'])): ?>
                            <a href="payment/initiate.php?booking_id=<?php echo $p['booking_id']; ?>"
                               class="btn btn-sm rounded-pill"
                               style="background:rgba(251,191,36,0.15);color:#fbbf24;border:1px solid rgba(251,191,36,0.3);font-size:0.78rem;font-weight:600;">
                                <i class="fas fa-redo me-1"></i>Retry
                            </a>
                            <?php else: ?>
                            <span style="color:#475569;font-size:0.75rem;">—</span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="border-color:rgba(255,255,255,0.06);">
                            <td colspan="4" class="ps-4 pe-3 py-3 text-end" style="font-size:0.82rem;color:#94a3b8;font-weight:600;">Total Paid (completed):</td>
                            <td colspan="3" class="py-3" style="font-size:1rem;font-weight:800;color:#4ade80;"><?php echo formatCurrency($total_paid); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<style>
.nav-pills .nav-link {
    background: rgba(255,255,255,0.06);
    color: #94a3b8;
    border-radius: 999px;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s;
}
.nav-pills .nav-link.active,
.nav-pills .nav-link:hover {
    background: linear-gradient(135deg,#4f46e5,#7c3aed);
    color: #fff;
}
.table > :not(caption) > * > * { background: transparent; color: inherit; }
.table-hover tbody tr:hover { background: rgba(255,255,255,0.04); }
</style>

<script>
function showTab(name, btn) {
    ['pending','history'].forEach(t => {
        document.getElementById('tab-' + t).style.display = 'none';
    });
    document.querySelectorAll('#payTabs .nav-link').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
