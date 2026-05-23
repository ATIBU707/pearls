<?php
/**
 * Booking Confirmation Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/BookingService.php';

requireLogin();

$booking_id = $_GET['id'] ?? null;
if (!$booking_id) {
    header('Location: dashboard.php');
    exit;
}

$bookingService = new BookingService();
$booking = $bookingService->getBooking($booking_id);

if (!$booking || $booking['user_id'] != getCurrentUserId()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Booking Confirmed';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow border-0 rounded-4 animate-fade-in">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success fa-5x animate-bounce"></i>
                    </div>
                    
                    <h1 class="h2 mb-3">Booking Successfully Placed!</h1>
                    <p class="lead text-muted mb-4">
                        Your reservation for <strong>Room <?php echo htmlspecialchars($booking['room_number']); ?></strong> has been received.
                    </p>

                    <div class="bg-light p-4 rounded-4 mb-4 text-start">
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Booking Code:</div>
                            <div class="col-7 fw-bold"><?php echo htmlspecialchars($booking['booking_code']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Semester:</div>
                            <div class="col-7"><?php echo htmlspecialchars($booking['semester']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Amount Due:</div>
                            <div class="col-7 text-primary fw-bold"><?php echo formatCurrency($booking['price_per_semester']); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-5 text-muted small">Status:</div>
                            <div class="col-7"><span class="badge bg-warning text-dark">Pending Payment</span></div>
                        </div>
                    </div>

                    <div class="alert alert-warning border-0 rounded-4 mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        Please complete your payment within 24 hours to secure your room.
                    </div>

                    <div class="d-grid gap-3">
                        <a href="payment/initiate.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary btn-lg rounded-pill shadow">
                            <i class="fas fa-credit-card me-2"></i> Pay Now (Mobile Money)
                        </a>
                        <div class="row g-2">
                            <div class="col-6">
                                <a href="dashboard.php" class="btn btn-outline-secondary w-100 rounded-pill">My Dashboard</a>
                            </div>
                            <div class="col-6">
                                <a href="rooms.php" class="btn btn-outline-secondary w-100 rounded-pill">View More Rooms</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="mt-4 text-muted small">
                A confirmation email has been sent to <strong><?php echo htmlspecialchars($booking['email']); ?></strong>.
            </p>
        </div>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-20px);}
    60% {transform: translateY(-10px);}
}
.animate-bounce {
    animation: bounce 2s infinite;
}
</style>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
