<?php
/**
 * Room Details Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/RoomService.php';

requireLogin();

$room_id = $_GET['id'] ?? null;
if (!$room_id) {
    header('Location: rooms.php');
    exit;
}

$roomService = new RoomService();
$room = $roomService->getRoomDetails($room_id);

if (!$room) {
    redirectWithMessage('rooms.php', 'Room not found.', 'error');
}

$page_title = 'Room ' . $room['room_number'];
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="rooms.php">Rooms</a></li>
            <li class="breadcrumb-item active" aria-current="page">Room <?php echo htmlspecialchars($room['room_number']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Room Images Gallery -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 overflow-hidden rounded-4 animate-fade-in">
                <img src="<?php echo $room['photo_url'] ?: 'assets/images/room-placeholder.jpg'; ?>" class="img-fluid w-100" alt="Room Image" style="max-height: 500px; object-fit: cover;">
                
                <?php if (!empty($room['photos'])): ?>
                <div class="row g-2 p-2 bg-light">
                    <?php foreach (json_decode($room['photos']) as $photo): ?>
                    <div class="col-3">
                        <img src="<?php echo $photo; ?>" class="img-fluid rounded border cursor-pointer hover-opacity" alt="Room Photo">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Room Details & Booking -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 animate-fade-up">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary px-3 py-2"><?php echo htmlspecialchars($room['type_name']); ?></span>
                        <span class="text-success fw-bold"><i class="fas fa-circle me-1 small"></i> Available</span>
                    </div>

                    <h1 class="h2 mb-2">Room <?php echo htmlspecialchars($room['room_number']); ?></h1>
                    <div class="h3 text-primary mb-4"><?php echo formatCurrency($room['price_per_semester']); ?> <small class="text-muted fs-6 fw-normal">/ semester</small></div>

                    <p class="text-muted mb-4">
                        <?php echo nl2br(htmlspecialchars($room['description'])); ?>
                    </p>

                    <h5 class="mb-3">Room Features</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <i class="fas fa-user-friends text-primary me-2"></i>
                                <span>Max <?php echo $room['capacity']; ?> Occupants</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <i class="fas fa-bed text-primary me-2"></i>
                                <span>Standard Bed</span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($room['facilities'])): ?>
                    <h5 class="mb-3">Facilities Included</h5>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php foreach ($room['facilities'] as $facility): ?>
                            <span class="badge bg-light text-dark border p-2 fw-normal">
                                <i class="<?php echo $facility['icon_url'] ?: 'fas fa-check'; ?> text-primary me-1"></i>
                                <?php echo htmlspecialchars($facility['facility_name']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-3">
                        <a href="booking.php?room_id=<?php echo $room['room_id']; ?>" class="btn btn-primary btn-lg rounded-pill shadow">
                            <i class="fas fa-calendar-check me-2"></i> Book This Room
                        </a>
                        <button class="btn btn-outline-secondary rounded-pill">
                            <i class="fas fa-share-alt me-2"></i> Share Room
                        </button>
                    </div>
                </div>
            </div>

            <!-- Host Info / Assistance -->
            <div class="card shadow-sm border-0 rounded-4 mt-4 animate-fade-up">
                <div class="card-body d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                            <i class="fas fa-headset fa-2x"></i>
                        </div>
                    </div>
                    <div class="ms-3">
                        <h6 class="mb-1">Need help with booking?</h6>
                        <p class="text-muted small mb-0">Call us at <a href="tel:+256765536881">+256 765 536 881</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-opacity:hover {
    opacity: 0.8;
    transition: opacity 0.2s;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
