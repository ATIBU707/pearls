<?php
/**
 * Student Notifications Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';

requireLogin();

$user_id = getCurrentUserId();

// Mark all as read on visit
executeQuery("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0", [$user_id]);

// Fetch all notifications
$notifications = getRows(
    "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50",
    [$user_id]
);

$page_title = 'Notifications';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-4">
    <div class="row mb-4 animate-fade-down">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Notifications</h1>
                <p class="text-muted mb-0"><?php echo count($notifications); ?> notification<?php echo count($notifications) !== 1 ? 's' : ''; ?></p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 animate-fade-up">
        <?php if (empty($notifications)): ?>
        <div class="card-body text-center py-5">
            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                 style="width:80px;height:80px">
                <i class="fas fa-bell fa-2x text-muted opacity-50"></i>
            </div>
            <h5 class="text-muted">No notifications yet</h5>
            <p class="text-muted small">You'll be notified about booking confirmations, payments, and maintenance updates.</p>
            <a href="rooms.php" class="btn btn-primary rounded-pill mt-2">
                <i class="fas fa-search me-1"></i> Browse Rooms
            </a>
        </div>
        <?php else: ?>
        <div class="list-group list-group-flush rounded-4">
            <?php
            $iconMap = [
                'booking'     => ['icon' => 'fas fa-calendar-check', 'color' => 'primary'],
                'payment'     => ['icon' => 'fas fa-money-check-alt', 'color' => 'success'],
                'maintenance' => ['icon' => 'fas fa-tools',           'color' => 'warning'],
                'alert'       => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'danger'],
                'general'     => ['icon' => 'fas fa-bell',            'color' => 'info'],
            ];
            foreach ($notifications as $n):
                $meta  = $iconMap[$n['type']] ?? $iconMap['general'];
                $isNew = !$n['is_read'];
            ?>
            <div class="list-group-item border-0 px-4 py-3 <?php echo $isNew ? 'bg-primary bg-opacity-5' : ''; ?>">
                <div class="d-flex gap-3 align-items-start">
                    <div class="bg-<?php echo $meta['color']; ?> bg-opacity-10 text-<?php echo $meta['color']; ?> rounded-3 p-2 flex-shrink-0 mt-1">
                        <i class="<?php echo $meta['icon']; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 <?php echo $isNew ? 'fw-bold' : ''; ?>">
                                <?php echo htmlspecialchars($n['title']); ?>
                                <?php if ($isNew): ?>
                                <span class="badge bg-primary ms-1 small">New</span>
                                <?php endif; ?>
                            </h6>
                            <small class="text-muted"><?php echo humanReadableDate($n['created_at']); ?></small>
                        </div>
                        <p class="text-muted mb-0 small"><?php echo htmlspecialchars($n['message']); ?></p>
                        <?php if ($n['related_booking_id']): ?>
                        <a href="booking-confirmation.php?id=<?php echo $n['related_booking_id']; ?>"
                           class="small text-primary mt-1 d-inline-block">
                            View Booking <i class="fas fa-arrow-right ms-1 small"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
