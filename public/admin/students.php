<?php
/**
 * Admin - Student Management
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';

requireAdminAuth();

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verifyCsrfOrDie();
    $uid    = (int)$_POST['user_id'];
    $active = (int)$_POST['is_active'];
    $newVal = $active ? 0 : 1;
    executeQuery("UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'student'", [$newVal, $uid]);
    redirectWithMessage('students.php', 'Student status updated.', 'success');
}

// Search
$search = sanitize($_GET['search'] ?? '');
$params = [];

$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.user_id) AS booking_count
        FROM users u
        WHERE u.role = 'student'";

if ($search) {
    $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}
$sql .= " ORDER BY u.created_at DESC";

$students = getRows($sql, $params);
$total    = count($students);

$page_title = 'Student Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Student Management</h1>
            <p class="text-muted mb-0"><?php echo $total; ?> student<?php echo $total !== 1 ? 's' : ''; ?> registered</p>
        </div>
    </div>

    <?php $alert = getAlertMessage(); if ($alert): ?>
    <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show border-0 shadow-sm">
        <?php echo htmlspecialchars($alert['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Search -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="students.php" method="GET" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0 bg-light"
                               placeholder="Search by name, email or student ID..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($search): ?>
                    <a href="students.php" class="btn btn-light ms-1">Clear</a>
                    <?php endif; ?>
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
                            <th class="ps-4">#</th>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Phone</th>
                            <th>Bookings</th>
                            <th>Registered</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-user-slash fa-2x mb-2 opacity-25 d-block"></i>
                                No students found<?php echo $search ? ' matching "' . htmlspecialchars($search) . '"' : ''; ?>.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($students as $i => $s): ?>
                        <tr>
                            <td class="ps-4 text-muted"><?php echo $i + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-2">
                                        <?php echo strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($s['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($s['student_id'] ?: '—'); ?></td>
                            <td><?php echo htmlspecialchars($s['phone_number'] ?: '—'); ?></td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary">
                                    <?php echo $s['booking_count']; ?> booking<?php echo $s['booking_count'] != 1 ? 's' : ''; ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                            <td>
                                <?php if ($s['is_active']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" action="students.php" class="d-inline">
                                    <?php echo getCSRFTokenInput(); ?>
                                    <input type="hidden" name="user_id"   value="<?php echo $s['user_id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $s['is_active']; ?>">
                                    <button type="submit" name="toggle_status"
                                            class="btn btn-sm <?php echo $s['is_active'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                            onclick="return confirm('<?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?> this student?')">
                                        <?php echo $s['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
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

<style>
.avatar-circle {
    width: 38px; height: 38px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; font-weight: 700;
    flex-shrink: 0;
}
</style>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
