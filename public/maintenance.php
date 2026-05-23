<?php
/**
 * Maintenance Requests Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/MaintenanceRequest.php';

requireLogin();

$requestModel = new MaintenanceRequest();
$user_id = getCurrentUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();
    $action = $_POST['action'] ?? 'create';

    // ── CREATE ──
    if ($action === 'create') {
        $data = [
            'user_id'     => $user_id,
            'category'    => sanitize($_POST['category'] ?? ''),
            'description' => sanitize($_POST['description'] ?? ''),
            'priority'    => sanitize($_POST['priority'] ?? 'medium')
        ];

        if (empty($data['description'])) {
            $error = 'Please provide a description of the issue.';
        } else {
            if ($requestModel->createRequest($data)) {
                $success = 'Maintenance request submitted successfully! Our team will look into it.';
            } else {
                $error = 'Failed to submit request. You must have an active booking (confirmed or checked-in) before filing a maintenance request.';
            }
        }
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $request_id = (int)($_POST['request_id'] ?? 0);
        // Verify ownership and status
        $existing = $requestModel->getById($request_id);
        if (!$existing || (int)$existing['user_id'] !== $user_id) {
            $error = 'Request not found.';
        } elseif ($existing['status'] !== 'open') {
            $error = 'Only open requests can be edited.';
        } else {
            $category = sanitize($_POST['category'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $priority = sanitize($_POST['priority'] ?? 'medium');
            if (empty($category)) {
                $error = 'Category is required.';
            } elseif (empty($description)) {
                $error = 'Description cannot be empty.';
            } else {
                $updateData = [
                    'title'       => $category,
                    'description' => $description,
                    'priority'    => $priority
                ];
                if ($requestModel->update($request_id, $updateData)) {
                    $success = 'Request updated successfully.';
                } else {
                    $error = 'Failed to update request.';
                }
            }
        }
    }

    // ── DELETE ──
    if ($action === 'delete') {
        $request_id = (int)($_POST['request_id'] ?? 0);
        $existing = $requestModel->getById($request_id);
        if (!$existing || (int)$existing['user_id'] !== $user_id) {
            $error = 'Request not found.';
        } elseif (!in_array($existing['status'], ['open'])) {
            $error = 'Only open requests can be deleted.';
        } elseif ($requestModel->delete($request_id)) {
            $success = 'Request deleted.';
        } else {
            $error = 'Failed to delete request.';
        }
    }
}

$myRequests = $requestModel->getByUser($user_id);

$page_title = 'Maintenance Requests';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-4">
    <div class="row mb-4 animate-fade-down">
        <div class="col-12">
            <h1 class="h3">Maintenance Requests</h1>
            <p class="text-muted">Report any issues in your room or common areas.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Submission Form -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 rounded-4 animate-fade-up">
                <div class="card-body p-4">
                    <h5 class="mb-4"><i class="fas fa-plus-circle me-2 text-primary"></i>New Request</h5>
                    <form method="POST">
                        <?php echo getCSRFTokenInput(); ?>
                        <input type="hidden" name="action" value="create">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Issue Category</label>
                            <select name="category" class="form-select" required>
                                <option value="Plumbing">Plumbing (Water, Toilet, Sink)</option>
                                <option value="Electrical">Electrical (Lights, Sockets)</option>
                                <option value="Furniture">Furniture (Bed, Desk, Chair)</option>
                                <option value="Structural">Structural (Walls, Doors, Windows)</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Priority</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority" id="p1" value="low">
                                    <label class="form-check-label" for="p1">Low</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority" id="p2" value="medium" checked>
                                    <label class="form-check-label" for="p2">Medium</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="priority" id="p3" value="high">
                                    <label class="form-check-label" for="p3">High</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Please describe the issue in detail..." required></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary py-3 rounded-pill shadow-sm">
                                <i class="fas fa-paper-plane me-2"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- History -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 animate-fade-up" style="animation-delay: 0.1s;">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>My Request History</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($myRequests)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                            <p>You haven't submitted any requests yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4">Date</th>
                                        <th>Category</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myRequests as $req):
                                        $statusClass = [
                                            'open'        => 'bg-warning text-dark',
                                            'in_progress' => 'bg-info text-white',
                                            'resolved'    => 'bg-success',
                                            'closed'      => 'bg-secondary'
                                        ][$req['status']] ?? 'bg-secondary';

                                        $priorityClass = [
                                            'high'   => 'text-danger',
                                            'medium' => 'text-warning',
                                            'low'    => 'text-info'
                                        ][$req['priority']] ?? 'text-muted';

                                        $canEdit = ($req['status'] === 'open');
                                    ?>
                                    <tr>
                                        <td class="ps-4 small text-muted"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($req['title'] ?? ''); ?></div>
                                            <div class="small text-muted text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($req['description']); ?></div>
                                        </td>
                                        <td>
                                            <span class="fw-bold <?php echo $priorityClass; ?>">
                                                <i class="fas fa-circle small me-1"></i><?php echo ucfirst($req['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($canEdit): ?>
                                            <button class="btn btn-sm btn-outline-primary me-1"
                                                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($req)); ?>)"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                                <?php echo getCSRFTokenInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="request_id" value="<?php echo $req['request_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="small text-muted"><i class="fas fa-lock me-1"></i><?php echo ucfirst(str_replace('_', ' ', $req['status'])); ?></span>
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
    </div>
</div>

<!-- Custom Edit Modal (avoids Bootstrap z-index conflicts with sidebar) -->
<div id="editModalOverlay" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.7); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
" onclick="if(event.target===this) closeEditModal()">
    <div style="
        background:#1e1b4b; border:1px solid rgba(255,255,255,0.15);
        border-radius:18px; width:100%; max-width:520px;
        margin:16px; box-shadow:0 24px 80px rgba(0,0,0,0.7);
        animation: modalSlideIn 0.25s cubic-bezier(0.16,1,0.3,1) both;
    ">
        <form method="POST" id="editForm">
            <?php echo getCSRFTokenInput(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="request_id" id="edit_request_id">

            <!-- Header -->
            <div style="
                display:flex; align-items:center; justify-content:space-between;
                padding:20px 24px 16px;
                border-bottom:1px solid rgba(255,255,255,0.1);
                background:rgba(79,70,229,0.12); border-radius:18px 18px 0 0;
            ">
                <h5 style="margin:0; color:white; font-weight:700; font-size:1rem;">
                    <i class="fas fa-edit me-2" style="color:#818cf8"></i>Edit Request
                </h5>
                <button type="button" onclick="closeEditModal()" style="
                    background:none; border:none; color:rgba(255,255,255,0.5);
                    font-size:1.2rem; cursor:pointer; padding:4px 8px; border-radius:6px;
                    line-height:1;
                " aria-label="Close">&times;</button>
            </div>

            <!-- Body -->
            <div style="padding:24px;">
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Category</label>
                    <select name="category" id="edit_category" required style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none;
                        appearance:auto; -webkit-appearance:auto;
                    ">
                        <option value="Plumbing" style="background:#1e1b4b;">Plumbing (Water, Toilet, Sink)</option>
                        <option value="Electrical" style="background:#1e1b4b;">Electrical (Lights, Sockets)</option>
                        <option value="Furniture" style="background:#1e1b4b;">Furniture (Bed, Desk, Chair)</option>
                        <option value="Structural" style="background:#1e1b4b;">Structural (Walls, Doors, Windows)</option>
                        <option value="Other" style="background:#1e1b4b;">Other</option>
                    </select>
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Priority</label>
                    <div style="display:flex; gap:16px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#f1f5f9; font-size:0.9rem;">
                            <input type="radio" name="priority" id="edit_p_low" value="low" style="accent-color:#818cf8;"> Low
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#f1f5f9; font-size:0.9rem;">
                            <input type="radio" name="priority" id="edit_p_med" value="medium" style="accent-color:#818cf8;"> Medium
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:#f1f5f9; font-size:0.9rem;">
                            <input type="radio" name="priority" id="edit_p_high" value="high" style="accent-color:#818cf8;"> High
                        </label>
                    </div>
                </div>

                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Description</label>
                    <textarea name="description" id="edit_description" rows="4" required
                        placeholder="Describe the issue in detail..."
                        style="
                            width:100%; padding:10px 14px; border-radius:10px;
                            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                            color:white; font-size:0.9rem; outline:none; resize:vertical;
                            font-family:inherit;
                        "></textarea>
                </div>
            </div>

            <!-- Footer -->
            <div style="
                padding:16px 24px 20px; display:flex; justify-content:flex-end; gap:10px;
                border-top:1px solid rgba(255,255,255,0.08);
            ">
                <button type="button" onclick="closeEditModal()" style="
                    padding:10px 24px; border-radius:999px; background:transparent;
                    border:1px solid rgba(255,255,255,0.2); color:#94a3b8;
                    font-size:0.875rem; font-weight:600; cursor:pointer; transition:all 0.2s;
                " onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">Cancel</button>
                <button type="submit" style="
                    padding:10px 28px; border-radius:999px;
                    background:linear-gradient(135deg,#4f46e5,#7c3aed);
                    border:none; color:white; font-size:0.875rem; font-weight:600;
                    cursor:pointer; box-shadow:0 4px 16px rgba(79,70,229,0.4);
                    transition:all 0.2s;
                " onmouseover="this.style.transform='translateY(-1px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-save me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalSlideIn {
    from { opacity:0; transform:translateY(-20px) scale(0.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);    }
}
#editModalOverlay select option { background: #1e1b4b; color: white; }
#editModalOverlay textarea:focus,
#editModalOverlay select:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.3);
}
</style>

<script>
function openEditModal(req) {
    document.getElementById('edit_request_id').value = req.request_id;

    // Set category select
    var catSelect = document.getElementById('edit_category');
    catSelect.value = req.title || 'Plumbing';

    // Set priority radio
    var priority = req.priority || 'medium';
    var radios = document.querySelectorAll('input[name="priority"]');
    radios.forEach(function(r) { r.checked = (r.value === priority); });

    // Set description
    document.getElementById('edit_description').value = req.description || '';

    // Show overlay
    var overlay = document.getElementById('editModalOverlay');
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModalOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
});
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
