<?php
/**
 * Admin Reports & Analytics
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';

requireAdminAuth();

// ── Summary stats ──────────────────────────────────────────────────────────
$totalRevenue    = (float)(getValue("SELECT SUM(amount) FROM payments WHERE status = 'completed'") ?? 0);
$monthlyRevenue  = (float)(getValue("SELECT SUM(amount) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())") ?? 0);
$totalBookings   = (int)(getValue("SELECT COUNT(*) FROM bookings") ?? 0);
$pendingBookings = (int)(getValue("SELECT COUNT(*) FROM bookings WHERE status = 'pending'") ?? 0);
$occupancyRate   = 0;
$totalRooms      = (int)(getValue("SELECT COUNT(*) FROM rooms") ?? 0);
$occupiedRooms   = (int)(getValue("SELECT COUNT(*) FROM rooms WHERE status IN ('booked','occupied')") ?? 0);
if ($totalRooms > 0) $occupancyRate = round(($occupiedRooms / $totalRooms) * 100);

// ── Monthly revenue for chart (last 6 months) ──────────────────────────────
$monthlyData = getRows(
    "SELECT DATE_FORMAT(payment_date, '%b %Y') AS month,
            SUM(amount) AS total
     FROM payments
     WHERE status = 'completed'
       AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
     ORDER BY DATE_FORMAT(payment_date, '%Y-%m') ASC"
);

// ── Bookings by status for donut ───────────────────────────────────────────
$bookingsByStatus = getRows(
    "SELECT status, COUNT(*) as count FROM bookings GROUP BY status"
);

// ── Recent payments table ──────────────────────────────────────────────────
$recentPayments = getRows(
    "SELECT p.*, b.booking_code, r.room_number, u.first_name, u.last_name
     FROM payments p
     JOIN bookings b ON p.booking_id = b.booking_id
     JOIN rooms r    ON b.room_id = r.room_id
     JOIN users u    ON b.user_id = u.user_id
     ORDER BY p.created_at DESC
     LIMIT 10"
);

$page_title = 'Reports & Analytics';
include BASE_PATH . '/views/layouts/header.php';

// Build chart arrays
$chartLabels = array_column($monthlyData, 'month');
$chartValues = array_column($monthlyData, 'total');
$statusLabels = array_column($bookingsByStatus, 'status');
$statusCounts = array_column($bookingsByStatus, 'count');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Reports & Analytics</h1>
            <p class="text-muted mb-0">Financial overview and booking insights</p>
        </div>
        <button class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Print Report
        </button>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4 animate-fade-up">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Total Revenue</h6>
                    </div>
                    <h2 class="mb-0 fw-bold"><?php echo formatCurrency($totalRevenue); ?></h2>
                    <small class="text-muted">All time</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                            <i class="fas fa-calendar-alt fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">This Month</h6>
                    </div>
                    <h2 class="mb-0 fw-bold"><?php echo formatCurrency($monthlyRevenue); ?></h2>
                    <small class="text-muted"><?php echo date('F Y'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Total Bookings</h6>
                    </div>
                    <h2 class="mb-0 fw-bold"><?php echo $totalBookings; ?></h2>
                    <small class="text-muted"><?php echo $pendingBookings; ?> pending</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3">
                            <i class="fas fa-bed fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Occupancy Rate</h6>
                    </div>
                    <h2 class="mb-0 fw-bold"><?php echo $occupancyRate; ?>%</h2>
                    <small class="text-muted"><?php echo $occupiedRooms; ?> / <?php echo $totalRooms; ?> rooms</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4 animate-fade-up" style="animation-delay:0.1s">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="mb-0">Monthly Revenue (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h5 class="mb-0">Bookings by Status</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="statusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Occupancy Progress Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h6 class="mb-0">Room Occupancy</h6>
                <span class="fw-bold"><?php echo $occupancyRate; ?>%</span>
            </div>
            <div class="progress" style="height:12px; border-radius:8px">
                <div class="progress-bar bg-primary" role="progressbar"
                     style="width: <?php echo $occupancyRate; ?>%; border-radius:8px"
                     aria-valuenow="<?php echo $occupancyRate; ?>" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted"><?php echo $occupiedRooms; ?> Occupied</small>
                <small class="text-muted"><?php echo $totalRooms - $occupiedRooms; ?> Available</small>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="card border-0 shadow-sm animate-fade-up">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">Recent Payments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student</th>
                            <th>Booking Code</th>
                            <th>Room</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentPayments as $p): ?>
                        <tr>
                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($p['booking_code']); ?></code></td>
                            <td>Room <?php echo htmlspecialchars($p['room_number']); ?></td>
                            <td><?php echo ucwords(str_replace('_',' ',$p['payment_method'])); ?></td>
                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $p['status'] === 'completed' ? 'bg-success' : ($p['status'] === 'pending' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                    <?php echo ucfirst($p['status']); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4 fw-bold text-primary"><?php echo formatCurrency($p['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Revenue chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels ?: ['No Data']); ?>,
        datasets: [{
            label: 'Revenue (UGX)',
            data: <?php echo json_encode($chartValues ?: [0]); ?>,
            backgroundColor: 'rgba(79, 70, 229, 0.15)',
            borderColor: 'rgba(79, 70, 229, 1)',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' },
                ticks: { callback: v => 'UGX ' + v.toLocaleString() }
            },
            x: { grid: { display: false } }
        }
    }
});

// Booking status donut
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(fn($s) => ucfirst(str_replace('_',' ',$s)), $statusLabels ?: ['No Data'])); ?>,
        datasets: [{
            data: <?php echo json_encode($statusCounts ?: [1]); ?>,
            backgroundColor: ['#f59e0b','#22c55e','#3b82f6','#6b7280','#ef4444'],
            borderWidth: 0,
            hoverOffset: 8
        }]
    },
    options: {
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<style>
@media print {
    .navbar, footer, .no-print { display:none !important; }
    body { background: white !important; }
}
</style>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
