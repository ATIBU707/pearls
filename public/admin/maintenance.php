<?php
/**
 * Admin Maintenance Management
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/MaintenanceRequest.php';
require_once APP_PATH . '/services/NotificationService.php';

requireAdminAuth();

$requestModel = new MaintenanceRequest();

// Handle status updates
if (isset($_POST['update_status'])) {
    verifyCsrfOrDie();
    $request_id = (int)$_POST['request_id'];
    $new_status = $_POST['status'];
    $allowed_statuses = ['open', 'in_progress', 'resolved', 'closed'];
    if ($request_id && in_array($new_status, $allowed_statuses)) {
        $update_data = ['status' => $new_status];
        if ($new_status === 'resolved') {
            $update_data['resolved_at'] = date('Y-m-d H:i:s');
        }
        executeQuery("UPDATE maintenance_requests SET status = ? WHERE request_id = ?", [$new_status, $request_id]);

        // Notify the student who raised the request
        $req = getRow(
            "SELECT mr.user_id, mr.title, r.room_number
               FROM maintenance_requests mr
               LEFT JOIN rooms r ON mr.room_id = r.room_id
               WHERE mr.request_id = ?",
            [$request_id]
        );
        if ($req && $req['user_id']) {
            NotificationService::maintenanceStatusChanged(
                (int)$req['user_id'],
                $req['title'] ?? 'Maintenance Request',
                $req['room_number'] ?? 'N/A',
                $new_status
            );
        }
    }
    redirectWithMessage('maintenance.php', 'Request status updated.', 'success');
}

$allRequests = $requestModel->getAllDetailed();

$page_title = 'Maintenance Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-0">Maintenance Management</h1>
        <p class="text-muted mb-0">Review and track repair requests from students.</p>
    </div>

    <div class="card shadow-sm border-0 animate-fade-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">Student & Room</th>
                            <th>Issue Category</th>
                            <th>Priority</th>
                            <th>Date Reported</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allRequests as $req): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></div>
                                <div class="small text-primary">Room <?php echo htmlspecialchars($req['room_number'] ?: 'N/A'); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($req['title'] ?? ''); ?></div>
                                <div class="small text-muted text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($req['description']); ?></div>
                            </td>
                            <td>
                                <?php 
                                $priorityClass = [
                                    'high'   => 'text-danger',
                                    'medium' => 'text-warning',
                                    'low'    => 'text-info'
                                ][$req['priority']] ?? 'text-secondary';
                                ?>
                                <span class="fw-bold <?php echo $priorityClass; ?>">
                                    <i class="fas fa-circle small me-1"></i> <?php echo ucfirst($req['priority']); ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?php echo date('M d, Y H:i', strtotime($req['created_at'])); ?></td>
                            <td>
                                <?php 
                                $statusClass = [
                                    'open'        => 'bg-warning text-dark',
                                    'in_progress' => 'bg-info text-white',
                                    'resolved'    => 'bg-success',
                                    'closed'      => 'bg-secondary'
                                ][$req['status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Update
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                        <li>
                                            <form method="POST">
                                                <?php echo getCSRFTokenInput(); ?>
                                                <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                                <input type="hidden" name="status" value="in_progress">
                                                <button type="submit" name="update_status" class="dropdown-item text-info">
                                                    <i class="fas fa-tools me-2"></i> Mark In Progress
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST">
                                                <?php echo getCSRFTokenInput(); ?>
                                                <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                                <input type="hidden" name="status" value="resolved">
                                                <button type="submit" name="update_status" class="dropdown-item text-success">
                                                    <i class="fas fa-check-double me-2"></i> Mark Resolved
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST">
                                                <?php echo getCSRFTokenInput(); ?>
                                                <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" name="update_status" class="dropdown-item text-muted">
                                                    <i class="fas fa-folder-minus me-2"></i> Close Request
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($allRequests)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No maintenance requests found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
