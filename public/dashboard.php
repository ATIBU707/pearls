<?php
/**
 * Student Dashboard
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';

require_once APP_PATH . '/services/BookingService.php';

requireLogin();

$user_id = getCurrentUserId();
$first_name = $_SESSION['first_name'];

$bookingService = new BookingService();
$bookings = $bookingService->getUserBookings($user_id);
$notifications = []; // Still placeholder for now

// Calculate total payments (mock logic for demo)
$totalPayments = 0;
foreach($bookings as $b) {
    if($b['status'] === 'confirmed' || $b['status'] === 'checked_in') {
        // This is a simple mock, real apps would sum the payments table
        // But for dashboard overview we'll just show something
    }
}

$page_title = 'My Dashboard';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-4">
    <div class="row mb-4 animate-fade-down">
        <div class="col-12">
            <h1 class="h3">Hello, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
            <p class="text-muted">Welcome back to your hostel management dashboard.</p>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="row g-4 mb-5 animate-fade-up">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 bg-primary text-white h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-white-50 mb-2">My Bookings</h6>
                            <h2 class="mb-0"><?php echo count($bookings); ?></h2>
                        </div>
                        <div class="h1 mb-0"><i class="fas fa-bookmark text-white-50"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Total Payments</h6>
                            <h2 class="mb-0">UGX 0</h2>
                        </div>
                        <div class="h1 mb-0 text-success"><i class="fas fa-money-check-alt opacity-25"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">Notifications</h6>
                            <h2 class="mb-0"><?php echo count($notifications); ?></h2>
                        </div>
                        <div class="h1 mb-0 text-warning"><i class="fas fa-bell opacity-25"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row animate-fade-up" style="animation-delay: 0.1s;">
        <!-- Recent Bookings -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Recent Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($bookings)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3 opacity-25"></i>
                            <p class="text-muted">You haven't made any bookings yet.</p>
                            <a href="rooms.php" class="btn btn-primary">Browse Rooms & Book</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Room</th>
                                        <th>Semester</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold">Room <?php echo htmlspecialchars($booking['room_number']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($booking['type_name']); ?></div>
                                            </td>
                                            <td><?php echo htmlspecialchars($booking['semester']); ?></td>
                                            <td>
                                                <?php 
                                                $badgeClass = [
                                                    'pending'   => 'bg-warning text-dark',
                                                    'confirmed' => 'bg-success',
                                                    'checked_in'=> 'bg-primary',
                                                    'cancelled' => 'bg-danger'
                                                ][$booking['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $badgeClass; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $booking['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($booking['status'] === 'pending'): ?>
                                                    <a href="payment/initiate.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-primary">Pay Now</a>
                                                <?php else: ?>
                                                    <a href="booking-confirmation.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-sm btn-light border">View</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="rooms.php" class="btn btn-outline-primary text-start p-3">
                            <i class="fas fa-search me-2"></i> Browse Rooms
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary text-start p-3">
                            <i class="fas fa-user-edit me-2"></i> Update Profile
                        </a>
                        <a href="maintenance.php" class="btn btn-outline-primary text-start p-3">
                            <i class="fas fa-tools me-2"></i> Request Maintenance
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
