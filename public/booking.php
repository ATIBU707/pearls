<?php
/**
 * Booking Form Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/RoomService.php';

requireLogin();

$room_id = $_GET['room_id'] ?? null;
if (!$room_id) {
    header('Location: rooms.php');
    exit;
}

$roomService = new RoomService();
$room = $roomService->getRoomDetails($room_id);

if (!$room || $room['status'] !== 'available') {
    redirectWithMessage('rooms.php', 'Room is not available for booking.', 'error');
}

$page_title = 'Book Room ' . $room['room_number'];
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow border-0 rounded-4 animate-fade-up">
                <div class="card-body p-4 p-md-5">
                    <h2 class="h3 mb-4"><i class="fas fa-calendar-check text-primary me-2"></i> Confirm Your Booking</h2>
                    
                    <div class="alert alert-info border-0 rounded-3 mb-4">
                        <div class="d-flex">
                            <i class="fas fa-info-circle mt-1 me-3"></i>
                            <div>
                                <strong>Selected:</strong> Room <?php echo htmlspecialchars($room['room_number']); ?> (<?php echo htmlspecialchars($room['type_name']); ?>)<br>
                                <strong>Price:</strong> <?php echo formatCurrency($room['price_per_semester']); ?> per semester
                            </div>
                        </div>
                    </div>

                    <form action="process-booking.php" method="POST" id="bookingForm">
                        <?php echo getCSRFTokenInput(); ?>
                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($_SESSION['first_name']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($_SESSION['last_name']); ?>" disabled>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control bg-light border-0" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" disabled>
                            </div>
                        </div>

                        <hr class="my-4 opacity-10">

                        <h5 class="mb-3">Booking Details</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="semester" class="form-label fw-bold">Select Semester <span class="text-danger">*</span></label>
                                <select name="semester" id="semester" class="form-select border-0 bg-light" required>
                                    <option value="">Choose...</option>
                                    <option value="Semester 1 2026/2027">Semester 1 2026/2027</option>
                                    <option value="Semester 2 2026/2027">Semester 2 2026/2027</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="check_in_date" class="form-label fw-bold">Preferred Check-in Date <span class="text-danger">*</span></label>
                                <input type="date" name="check_in_date" id="check_in_date" class="form-control border-0 bg-light" required min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" value="" id="terms" required>
                            <label class="form-check-label text-muted small" for="terms">
                                I agree to the <a href="#">Terms & Conditions</a> and <a href="#">Hostel Regulations</a>. I understand that booking is subject to payment confirmation.
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3">
                                <i class="fas fa-check-circle me-2"></i> Confirm Booking
                            </button>
                            <a href="room-details.php?id=<?php echo $room['room_id']; ?>" class="btn btn-link text-muted">Cancel and go back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
