<?php
/**
 * Admin Payments Overview
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';

requireAdminAuth();

// Filters
$status_filter = $_GET['status'] ?? '';
$search        = sanitize($_GET['search'] ?? '');
$params        = [];

$sql = "SELECT p.*, b.booking_code, r.room_number,
               u.first_name, u.last_name, u.email, u.student_id
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        JOIN rooms r    ON b.room_id = r.room_id
        JOIN users u    ON b.user_id = u.user_id
        WHERE 1=1";

if ($status_filter) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}
if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR b.booking_code LIKE ? OR p.transaction_reference LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql .= " ORDER BY p.created_at DESC";

$payments = getRows($sql, $params);

// Summary totals
$totalRevenue  = (float)(getValue("SELECT SUM(amount) FROM payments WHERE status = 'completed'") ?? 0);
$pendingTotal  = (float)(getValue("SELECT SUM(amount) FROM payments WHERE status = 'pending'") ?? 0);
$totalCount    = (int)(getValue("SELECT COUNT(*) FROM payments") ?? 0);

$page_title = 'Payment Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Payment Management</h1>
            <p class="text-muted mb-0"><?php echo count($payments); ?> payment record<?php echo count($payments) !== 1 ? 's' : ''; ?></p>
        </div>
        <a href="reports.php" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-chart-bar me-1"></i> View Reports
        </a>
    </div>

    <!-- KPI Summary -->
    <div class="row g-4 mb-4 animate-fade-up">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Collected</div>
                        <div class="h5 mb-0 fw-bold"><?php echo formatCurrency($totalRevenue); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Pending Payments</div>
                        <div class="h5 mb-0 fw-bold"><?php echo formatCurrency($pendingTotal); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3">
                        <i class="fas fa-receipt fa-lg"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Transactions</div>
                        <div class="h5 mb-0 fw-bold"><?php echo $totalCount; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="payments.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-light"
                               placeholder="Search by name, booking code, or reference…"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select border-0 bg-light">
                        <option value="">All Statuses</option>
                        <option value="pending"   <?php echo $status_filter === 'pending'   ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="failed"    <?php echo $status_filter === 'failed'    ? 'selected' : ''; ?>>Failed</option>
                        <option value="refunded"  <?php echo $status_filter === 'refunded'  ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="payments.php" class="btn btn-light ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0 animate-fade-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Booking Code</th>
                            <th>Room</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-receipt fa-2x mb-2 d-block opacity-25"></i>
                                No payments found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($payments as $p):
                            $badgeMap = [
                                'pending'   => 'bg-warning text-dark',
                                'completed' => 'bg-success',
                                'failed'    => 'bg-danger',
                                'refunded'  => 'bg-secondary',
                            ];
                            $badge = $badgeMap[$p['status']] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($p['student_id'] ?: $p['email']); ?></div>
                            </td>
                            <td><code class="text-primary"><?php echo htmlspecialchars($p['booking_code']); ?></code></td>
                            <td>Room <?php echo htmlspecialchars($p['room_number']); ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $p['payment_method'])); ?></td>
                            <td class="small text-muted"><?php echo htmlspecialchars($p['transaction_reference'] ?: '—'); ?></td>
                            <td class="small text-muted"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                            <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                            <td class="text-end pe-4 fw-bold text-primary"><?php echo formatCurrency($p['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($payments)): ?>
                    <tfoot class="bg-light">
                        <tr>
                            <td colspan="7" class="text-end pe-3 fw-bold ps-4">Total (completed):</td>
                            <td class="text-end pe-4 fw-bold text-success"><?php echo formatCurrency($totalRevenue); ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
