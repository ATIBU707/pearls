<?php
/**
 * Admin Bookings Management
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/NotificationService.php';
require_once APP_PATH . '/services/EmailService.php';

requireAdminAuth();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrfOrDie();
    $bid    = (int)$_POST['booking_id'];
    $status = $_POST['new_status'];
    $allowed = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
    if ($bid && in_array($status, $allowed)) {
        executeQuery("UPDATE bookings SET status = ? WHERE booking_id = ?", [$status, $bid]);
        // If confirming, update room to occupied; if cancelling, set room to available
        if ($status === 'confirmed') {
            executeQuery("UPDATE rooms SET status = 'occupied' WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = ?)", [$bid]);
        } elseif ($status === 'cancelled') {
            executeQuery("UPDATE rooms SET status = 'available' WHERE room_id = (SELECT room_id FROM bookings WHERE booking_id = ?)", [$bid]);
        }

        // Notify the student
        $brow = getRow(
            "SELECT b.user_id, b.booking_code, b.booking_id, r.room_number
               FROM bookings b
               JOIN rooms r ON b.room_id = r.room_id
               WHERE b.booking_id = ?",
            [$bid]
        );
        if ($brow) {
            NotificationService::bookingStatusChanged(
                (int)$brow['user_id'],
                $brow['booking_code'],
                $brow['room_number'],
                $status,
                (int)$brow['booking_id']
            );
            // Send email notification
            try {
                $uRow = getRow("SELECT email, first_name FROM users WHERE user_id = ?", [$brow['user_id']]);
                if ($uRow) {
                    (new EmailService())->sendBookingStatusUpdate(
                        $uRow['email'], $uRow['first_name'],
                        $brow['booking_code'], $brow['room_number'],
                        $status, (int)$brow['booking_id']
                    );
                }
            } catch (\Throwable $e) { logMessage('Email error: '.$e->getMessage(),'error'); }
        }
    }
    redirectWithMessage('bookings.php', 'Booking status updated.', 'success');
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search        = sanitize($_GET['search'] ?? '');
$params        = [];

$sql = "SELECT b.*, r.room_number, r.price_per_semester,
               rt.type_name, u.first_name, u.last_name, u.email, u.student_id
        FROM bookings b
        JOIN rooms r      ON b.room_id  = r.room_id
        JOIN room_types rt ON r.room_type_id = rt.type_id
        JOIN users u      ON b.user_id  = u.user_id
        WHERE 1=1";

if ($status_filter) {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
}
if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR r.room_number LIKE ? OR b.booking_code LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$sql .= " ORDER BY b.created_at DESC";

$bookings = getRows($sql, $params);

$page_title = 'Booking Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Booking Management</h1>
            <p class="text-muted mb-0"><?php echo count($bookings); ?> booking<?php echo count($bookings) !== 1 ? 's' : ''; ?> found</p>
        </div>
    </div>

    <?php $alert = getAlertMessage(); if ($alert): ?>
    <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show border-0 shadow-sm">
        <?php echo htmlspecialchars($alert['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="bookings.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-light"
                               placeholder="Search by student name, room, or booking code…"
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select border-0 bg-light">
                        <option value="">All Statuses</option>
                        <option value="pending"     <?php echo $status_filter === 'pending'     ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed"   <?php echo $status_filter === 'confirmed'   ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in"  <?php echo $status_filter === 'checked_in'  ? 'selected' : ''; ?>>Checked In</option>
                        <option value="checked_out" <?php echo $status_filter === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                        <option value="cancelled"   <?php echo $status_filter === 'cancelled'   ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="bookings.php" class="btn btn-light ms-1">Reset</a>
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
                            <th class="ps-4">Booking Code</th>
                            <th>Student</th>
                            <th>Room</th>
                            <th>Semester</th>
                            <th>Check-in</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-times fa-2x mb-2 d-block opacity-25"></i>
                                No bookings found.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($bookings as $b):
                            $badgeMap = [
                                'pending'     => 'bg-warning text-dark',
                                'confirmed'   => 'bg-success',
                                'checked_in'  => 'bg-primary',
                                'checked_out' => 'bg-secondary',
                                'cancelled'   => 'bg-danger',
                            ];
                            $badge = $badgeMap[$b['status']] ?? 'bg-secondary';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <code class="text-primary fw-bold"><?php echo htmlspecialchars($b['booking_code']); ?></code>
                            </td>
                            <td>
                                <div class="fw-bold"><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($b['student_id'] ?: $b['email']); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold">Room <?php echo htmlspecialchars($b['room_number']); ?></div>
                                <div class="small text-muted"><?php echo htmlspecialchars($b['type_name']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($b['semester']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($b['check_in_date'])); ?></td>
                            <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $b['status'])); ?></span></td>
                            <td class="text-end pe-4">
                                <form method="POST" action="bookings.php" class="d-inline-flex align-items-center gap-2">
                                    <?php echo getCSRFTokenInput(); ?>
                                    <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                    <select name="new_status" class="form-select form-select-sm border-0 bg-light" style="width: 140px">
                                        <option value="pending"     <?php echo $b['status'] === 'pending'     ? 'selected' : ''; ?>>Pending</option>
                                        <option value="confirmed"   <?php echo $b['status'] === 'confirmed'   ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="checked_in"  <?php echo $b['status'] === 'checked_in'  ? 'selected' : ''; ?>>Checked In</option>
                                        <option value="checked_out" <?php echo $b['status'] === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                        <option value="cancelled"   <?php echo $b['status'] === 'cancelled'   ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
