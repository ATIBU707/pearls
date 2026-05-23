<?php
/**
 * Admin Dashboard - Overview
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';

requireAdminAuth();

// Fetch stats (mock for now, real queries in production)
$totalStudents = getValue("SELECT COUNT(*) FROM users WHERE role = 'student'");
$totalRooms    = getValue("SELECT COUNT(*) FROM rooms");
$availableRooms= getValue("SELECT COUNT(*) FROM rooms WHERE status = 'available'");
$totalBookings = getValue("SELECT COUNT(*) FROM bookings");
$revenue       = getValue("SELECT SUM(amount) FROM payments WHERE status = 'completed'");

$page_title = 'Admin Dashboard';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4 animate-fade-down">
        <div class="col-12">
            <h1 class="h3">Admin Dashboard</h1>
            <p class="text-muted">Welcome back, Admin. Here's what's happening today.</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4 animate-fade-up">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                            <i class="fas fa-users fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Total Students</h6>
                    </div>
                    <h2 class="mb-0"><?php echo $totalStudents ?: 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                            <i class="fas fa-door-open fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Available Rooms</h6>
                    </div>
                    <h2 class="mb-0"><?php echo $availableRooms ?: 0; ?> / <?php echo $totalRooms ?: 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                            <i class="fas fa-calendar-check fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Total Bookings</h6>
                    </div>
                    <h2 class="mb-0"><?php echo $totalBookings ?: 0; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 text-info p-3 rounded-3 me-3">
                            <i class="fas fa-wallet fa-lg"></i>
                        </div>
                        <h6 class="text-muted mb-0">Total Revenue</h6>
                    </div>
                    <h2 class="mb-0"><?php echo formatCurrency($revenue ?: 0); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row animate-fade-up" style="animation-delay: 0.1s;">
        <!-- Recent Bookings -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php 
                    $recentBookings = getRows("SELECT b.*, r.room_number, u.first_name, u.last_name 
                                              FROM bookings b 
                                              JOIN rooms r ON b.room_id = r.room_id 
                                              JOIN users u ON b.user_id = u.user_id 
                                              ORDER BY b.created_at DESC LIMIT 5");
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBookings as $b): ?>
                                <tr>
                                    <td class="ps-4"><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></td>
                                    <td>Room <?php echo htmlspecialchars($b['room_number']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($b['status'] == 'confirmed' ? 'success' : 'warning'); ?> text-<?php echo ($b['status'] == 'confirmed' ? 'white' : 'dark'); ?>">
                                            <?php echo ucfirst($b['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <a href="bookings.php?id=<?php echo $b['booking_id']; ?>" class="btn btn-sm btn-light border">Details</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recentBookings)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No recent bookings found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">System Status</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database</span>
                        <span class="text-success"><i class="fas fa-check-circle me-1"></i> Connected</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>PHP Version</span>
                        <span><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>WAMP Server</span>
                        <span class="text-success">Running</span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="rooms.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i> Manage Rooms</a>
                        <a href="students.php" class="btn btn-outline-primary"><i class="fas fa-user-graduate me-2"></i> View Students</a>
                        <a href="reports.php" class="btn btn-outline-primary"><i class="fas fa-file-invoice-dollar me-2"></i> Financial Reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
