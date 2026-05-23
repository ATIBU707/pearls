<?php
/**
 * Rooms Listing Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/RoomService.php';

// Require login to view rooms (optional, but good for booking context)
requireLogin();

$roomService = new RoomService();

// Handle filters
$filters = [
    'type_id'   => $_GET['type'] ?? '',
    'status'    => 'available', // Default to only showing available rooms for students
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
];

$rooms = $roomService->getRooms($filters);
$types = $roomService->getRoomTypes();

$page_title = 'Browse Rooms';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm border-0 animate-fade-in">
                <div class="card-body">
                    <h5 class="card-title mb-4"><i class="fas fa-filter text-primary me-2"></i> Filter Rooms</h5>
                    
                    <form action="rooms.php" method="GET">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Room Type</label>
                            <select name="type" class="form-select border-0 bg-light">
                                <option value="">All Types</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?php echo $type['type_id']; ?>" <?php echo ($filters['type_id'] == $type['type_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Price Range (UGX)</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" name="min_price" class="form-control border-0 bg-light" placeholder="Min" value="<?php echo htmlspecialchars($filters['min_price']); ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" name="max_price" class="form-control border-0 bg-light" placeholder="Max" value="<?php echo htmlspecialchars($filters['max_price']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="rooms.php" class="btn btn-outline-secondary">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Rooms List -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0"><?php echo count($rooms); ?> Rooms Found</h2>
                <div class="btn-group">
                    <button class="btn btn-light border-0"><i class="fas fa-th-large"></i></button>
                    <button class="btn btn-light border-0"><i class="fas fa-list"></i></button>
                </div>
            </div>

            <div class="row g-4">
                <?php if (empty($rooms)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <p class="lead text-muted">No rooms found matching your criteria.</p>
                        <a href="rooms.php" class="btn btn-primary">Reset Filters</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-6 col-xl-4 animate-fade-up">
                            <div class="card h-100 shadow-sm border-0 room-card">
                                <div class="position-relative">
                                    <img src="<?php echo $room['photo_url'] ?: 'assets/images/room-placeholder.jpg'; ?>" class="card-img-top" alt="Room <?php echo $room['room_number']; ?>" style="height: 200px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 m-3">
                                        <span class="badge bg-success shadow-sm">Available</span>
                                    </div>
                                    <div class="position-absolute bottom-0 start-0 m-3">
                                        <span class="badge bg-dark bg-opacity-75"><?php echo htmlspecialchars($room['type_name']); ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">Room <?php echo htmlspecialchars($room['room_number']); ?></h5>
                                        <div class="text-primary fw-bold h5 mb-0"><?php echo formatCurrency($room['price_per_semester']); ?></div>
                                    </div>
                                    <p class="card-text text-muted small mb-3">
                                        <?php echo truncate($room['description'], 80); ?>
                                    </p>
                                    <div class="room-features mb-3">
                                        <span class="me-3 text-muted"><i class="fas fa-user-friends me-1"></i> Max <?php echo $room['capacity']; ?></span>
                                        <span class="text-muted"><i class="fas fa-bed me-1"></i> Standard</span>
                                    </div>
                                    <div class="d-grid">
                                        <a href="room-details.php?id=<?php echo $room['room_id']; ?>" class="btn btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.room-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    overflow: hidden;
}
.room-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
.room-card img {
    transition: transform 0.5s ease;
}
.room-card:hover img {
    transform: scale(1.05);
}
</style>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
