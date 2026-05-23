<?php
/**
 * Admin Room Management
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/RoomService.php';

requireAdminAuth();

$roomService = new RoomService();
$rooms = $roomService->getRooms();
$types = $roomService->getRoomTypes();

$page_title = 'Room Management';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Room Management</h1>
            <p class="text-muted mb-0">Add, edit, or remove hostel rooms.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus me-2"></i> Add New Room
        </button>
    </div>

    <?php $alert = getAlertMessage(); if ($alert): ?>
    <div class="alert alert-<?php echo $alert['type'] === 'error' ? 'danger' : $alert['type']; ?> alert-dismissible fade show border-0 shadow-sm">
        <?php echo htmlspecialchars($alert['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 animate-fade-up">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4" style="width:56px;">Photo</th>
                            <th>Room #</th>
                            <th>Type</th>
                            <th>Capacity</th>
                            <th>Price / Semester</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-door-open fa-2x mb-2 d-block opacity-25"></i>
                                No rooms found. Click "Add New Room" to get started.
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="ps-4">
                                <img src="<?php echo htmlspecialchars($room['photo_url'] ? '../' . $room['photo_url'] : '../assets/images/room-placeholder.jpg'); ?>"
                                     alt="Room <?php echo htmlspecialchars($room['room_number']); ?>"
                                     style="width:44px; height:44px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,0.15);">
                            </td>
                            <td class="fw-bold"><?php echo htmlspecialchars($room['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                            <td><?php echo $room['capacity']; ?> Person<?php echo $room['capacity'] > 1 ? 's' : ''; ?></td>
                            <td><?php echo formatCurrency($room['price_per_semester']); ?></td>
                            <td>
                                <?php
                                $statusClass = [
                                    'available'   => 'bg-success-subtle text-success border-success-subtle',
                                    'booked'      => 'bg-warning-subtle text-warning border-warning-subtle',
                                    'occupied'    => 'bg-primary-subtle text-primary border-primary-subtle',
                                    'maintenance' => 'bg-danger-subtle text-danger border-danger-subtle'
                                ][$room['status']] ?? 'bg-secondary-subtle';
                                ?>
                                <span class="badge border <?php echo $statusClass; ?> px-2 py-1">
                                    <?php echo ucfirst($room['status']); ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary"
                                            onclick="openEditModal(<?php echo htmlspecialchars(json_encode($room)); ?>)"
                                            title="Edit Room">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" action="process-room.php" class="d-inline"
                                          onsubmit="return confirm('Delete Room <?php echo addslashes($room['room_number']); ?>? This cannot be undone.')">
                                        <?php echo getCSRFTokenInput(); ?>
                                        <input type="hidden" name="action"  value="delete">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Room">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ══ ADD ROOM MODAL (custom overlay — avoids sidebar z-index conflicts) ══ -->
<div id="addRoomOverlay" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.7); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
" onclick="if(event.target===this) closeAddModal()">
    <div style="
        background:#1e1b4b; border:1px solid rgba(255,255,255,0.15);
        border-radius:18px; width:100%; max-width:540px;
        margin:16px; box-shadow:0 24px 80px rgba(0,0,0,0.7);
        animation: modalSlideIn 0.25s cubic-bezier(0.16,1,0.3,1) both;
        max-height:90vh; overflow-y:auto;
    ">
        <!-- Header -->
        <div style="
            display:flex; align-items:center; justify-content:space-between;
            padding:20px 24px 16px;
            border-bottom:1px solid rgba(255,255,255,0.1);
            background:rgba(79,70,229,0.12); border-radius:18px 18px 0 0;
            position:sticky; top:0; z-index:1;
        ">
            <h5 style="margin:0; color:white; font-weight:700; font-size:1rem;">
                <i class="fas fa-plus-circle me-2" style="color:#818cf8"></i>Add New Room
            </h5>
            <button type="button" onclick="closeAddModal()" style="
                background:none; border:none; color:rgba(255,255,255,0.5);
                font-size:1.2rem; cursor:pointer; padding:4px 8px; border-radius:6px; line-height:1;
            " aria-label="Close">&times;</button>
        </div>

        <form action="process-room.php" method="POST" enctype="multipart/form-data">
            <?php echo getCSRFTokenInput(); ?>
            <input type="hidden" name="action" value="add">

            <div style="padding:24px;">
                <!-- Room Number -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Number <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="room_number" placeholder="e.g. A-101" required style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                    ">
                </div>

                <!-- Room Type -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Type <span style="color:#f87171;">*</span>
                    </label>
                    <select name="type_id" required style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; appearance:auto; box-sizing:border-box;
                    ">
                        <option value="" style="background:#1e1b4b;">Select type…</option>
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['type_id']; ?>" style="background:#1e1b4b;"><?php echo htmlspecialchars($type['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Capacity + Price row -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
                    <div>
                        <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                            Capacity <span style="color:#f87171;">*</span>
                        </label>
                        <input type="number" name="capacity" value="1" min="1" max="10" required style="
                            width:100%; padding:10px 14px; border-radius:10px;
                            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                            color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                        ">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                            Price (UGX) <span style="color:#f87171;">*</span>
                        </label>
                        <input type="number" name="price" placeholder="250000" min="0" step="1000" required style="
                            width:100%; padding:10px 14px; border-radius:10px;
                            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                            color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                        ">
                    </div>
                </div>

                <!-- Initial Status -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Initial Status</label>
                    <select name="status" style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; appearance:auto; box-sizing:border-box;
                    ">
                        <option value="available" style="background:#1e1b4b;">Available</option>
                        <option value="maintenance" style="background:#1e1b4b;">Maintenance</option>
                    </select>
                </div>

                <!-- Description -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Description</label>
                    <textarea name="description" rows="3" placeholder="Brief description of the room…" style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; resize:vertical; font-family:inherit; box-sizing:border-box;
                    "></textarea>
                </div>

                <!-- Room Photo -->
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Photo <span style="color:#64748b; font-weight:400; text-transform:none; letter-spacing:0;">(JPG / PNG / WebP &mdash; max 5 MB)</span>
                    </label>
                    <!-- Drop zone -->
                    <div id="addDropZone" onclick="document.getElementById('add_room_image').click()" style="
                        border:2px dashed rgba(129,140,248,0.45); border-radius:12px;
                        padding:20px; text-align:center; cursor:pointer;
                        background:rgba(255,255,255,0.04); transition:border-color 0.2s, background 0.2s;
                    "
                        ondragover="event.preventDefault(); this.style.borderColor='#818cf8'; this.style.background='rgba(79,70,229,0.12)';"
                        ondragleave="this.style.borderColor='rgba(129,140,248,0.45)'; this.style.background='rgba(255,255,255,0.04)';"
                        ondrop="handleAddDrop(event)">
                        <img id="add_image_preview" src="" alt="" style="display:none; max-height:140px; border-radius:10px; margin-bottom:10px; max-width:100%;">
                        <div id="add_drop_hint">
                            <i class="fas fa-cloud-upload-alt" style="font-size:1.8rem; color:#818cf8; display:block; margin-bottom:8px;"></i>
                            <span style="color:#94a3b8; font-size:0.875rem;">Click or drag &amp; drop to upload a photo</span>
                        </div>
                    </div>
                    <input type="file" id="add_room_image" name="room_image" accept="image/*" style="display:none" onchange="previewAddImage(this)">
                </div>
            </div>

            <!-- Footer -->
            <div style="
                padding:16px 24px 20px; display:flex; justify-content:flex-end; gap:10px;
                border-top:1px solid rgba(255,255,255,0.08);
            ">
                <button type="button" onclick="closeAddModal()" style="
                    padding:10px 24px; border-radius:999px; background:transparent;
                    border:1px solid rgba(255,255,255,0.2); color:#94a3b8;
                    font-size:0.875rem; font-weight:600; cursor:pointer;
                " onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">Cancel</button>
                <button type="submit" style="
                    padding:10px 28px; border-radius:999px;
                    background:linear-gradient(135deg,#4f46e5,#7c3aed);
                    border:none; color:white; font-size:0.875rem; font-weight:600;
                    cursor:pointer; box-shadow:0 4px 16px rgba(79,70,229,0.4);
                "><i class="fas fa-save me-1"></i> Save Room</button>
            </div>
        </form>
    </div>
</div>

<!-- ══ EDIT ROOM MODAL (custom overlay) ══ -->
<div id="editRoomOverlay" style="
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,0.7); backdrop-filter:blur(6px);
    align-items:center; justify-content:center;
" onclick="if(event.target===this) closeEditRoomModal()">
    <div style="
        background:#1e1b4b; border:1px solid rgba(255,255,255,0.15);
        border-radius:18px; width:100%; max-width:540px;
        margin:16px; box-shadow:0 24px 80px rgba(0,0,0,0.7);
        animation: modalSlideIn 0.25s cubic-bezier(0.16,1,0.3,1) both;
        max-height:90vh; overflow-y:auto;
    ">
        <!-- Header -->
        <div style="
            display:flex; align-items:center; justify-content:space-between;
            padding:20px 24px 16px;
            border-bottom:1px solid rgba(255,255,255,0.1);
            background:rgba(79,70,229,0.12); border-radius:18px 18px 0 0;
            position:sticky; top:0; z-index:1;
        ">
            <h5 style="margin:0; color:white; font-weight:700; font-size:1rem;">
                <i class="fas fa-edit me-2" style="color:#818cf8"></i>Edit Room
            </h5>
            <button type="button" onclick="closeEditRoomModal()" style="
                background:none; border:none; color:rgba(255,255,255,0.5);
                font-size:1.2rem; cursor:pointer; padding:4px 8px; border-radius:6px; line-height:1;
            " aria-label="Close">&times;</button>
        </div>

        <form action="process-room.php" method="POST" enctype="multipart/form-data">
            <?php echo getCSRFTokenInput(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="room_id" id="edit_room_id">

            <div style="padding:24px;">
                <!-- Room Number -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Number <span style="color:#f87171;">*</span>
                    </label>
                    <input type="text" name="room_number" id="edit_room_number" required style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                    ">
                </div>

                <!-- Room Type -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Type <span style="color:#f87171;">*</span>
                    </label>
                    <select name="type_id" id="edit_type_id" required style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; appearance:auto; box-sizing:border-box;
                    ">
                        <?php foreach ($types as $type): ?>
                        <option value="<?php echo $type['type_id']; ?>" style="background:#1e1b4b;"><?php echo htmlspecialchars($type['type_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Capacity + Price -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:18px;">
                    <div>
                        <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                            Capacity <span style="color:#f87171;">*</span>
                        </label>
                        <input type="number" name="capacity" id="edit_capacity" min="1" max="10" required style="
                            width:100%; padding:10px 14px; border-radius:10px;
                            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                            color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                        ">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                            Price (UGX) <span style="color:#f87171;">*</span>
                        </label>
                        <input type="number" name="price" id="edit_price" min="0" step="1000" required style="
                            width:100%; padding:10px 14px; border-radius:10px;
                            background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                            color:white; font-size:0.9rem; outline:none; font-family:inherit; box-sizing:border-box;
                        ">
                    </div>
                </div>

                <!-- Status -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Status</label>
                    <select name="status" id="edit_status" style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; appearance:auto; box-sizing:border-box;
                    ">
                        <option value="available"   style="background:#1e1b4b;">Available</option>
                        <option value="booked"      style="background:#1e1b4b;">Booked</option>
                        <option value="occupied"    style="background:#1e1b4b;">Occupied</option>
                        <option value="maintenance" style="background:#1e1b4b;">Maintenance</option>
                    </select>
                </div>

                <!-- Description -->
                <div style="margin-bottom:18px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">Description</label>
                    <textarea name="description" id="edit_description" rows="3" style="
                        width:100%; padding:10px 14px; border-radius:10px;
                        background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.2);
                        color:white; font-size:0.9rem; outline:none; resize:vertical; font-family:inherit; box-sizing:border-box;
                    "></textarea>
                </div>

                <!-- Room Photo -->
                <div style="margin-bottom:8px;">
                    <label style="display:block; font-size:0.78rem; font-weight:700; text-transform:uppercase; letter-spacing:0.05em; color:#94a3b8; margin-bottom:8px;">
                        Room Photo <span style="color:#64748b; font-weight:400; text-transform:none; letter-spacing:0;">(leave blank to keep current)</span>
                    </label>
                    <!-- Current photo strip -->
                    <div id="edit_current_photo_wrap" style="display:none; margin-bottom:12px; padding:10px; border-radius:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);">
                        <p style="margin:0 0 8px; font-size:0.78rem; color:#94a3b8;">Current photo:</p>
                        <img id="edit_current_photo" src="" alt="Current room photo"
                             style="max-height:120px; border-radius:8px; max-width:100%;">
                    </div>
                    <!-- Drop zone -->
                    <div id="editDropZone" onclick="document.getElementById('edit_room_image').click()" style="
                        border:2px dashed rgba(129,140,248,0.45); border-radius:12px;
                        padding:20px; text-align:center; cursor:pointer;
                        background:rgba(255,255,255,0.04); transition:border-color 0.2s, background 0.2s;
                    "
                        ondragover="event.preventDefault(); this.style.borderColor='#818cf8'; this.style.background='rgba(79,70,229,0.12)';"
                        ondragleave="this.style.borderColor='rgba(129,140,248,0.45)'; this.style.background='rgba(255,255,255,0.04)';"
                        ondrop="handleEditDrop(event)">
                        <img id="edit_image_preview" src="" alt="" style="display:none; max-height:140px; border-radius:10px; margin-bottom:10px; max-width:100%;">
                        <div id="edit_drop_hint">
                            <i class="fas fa-cloud-upload-alt" style="font-size:1.8rem; color:#818cf8; display:block; margin-bottom:8px;"></i>
                            <span style="color:#94a3b8; font-size:0.875rem;">Click or drag &amp; drop to replace photo</span>
                        </div>
                    </div>
                    <input type="file" id="edit_room_image" name="room_image" accept="image/*" style="display:none" onchange="previewEditImage(this)">
                </div>
            </div>

            <!-- Footer -->
            <div style="
                padding:16px 24px 20px; display:flex; justify-content:flex-end; gap:10px;
                border-top:1px solid rgba(255,255,255,0.08);
            ">
                <button type="button" onclick="closeEditRoomModal()" style="
                    padding:10px 24px; border-radius:999px; background:transparent;
                    border:1px solid rgba(255,255,255,0.2); color:#94a3b8;
                    font-size:0.875rem; font-weight:600; cursor:pointer;
                " onmouseover="this.style.background='rgba(255,255,255,0.08)'" onmouseout="this.style.background='transparent'">Cancel</button>
                <button type="submit" style="
                    padding:10px 28px; border-radius:999px;
                    background:linear-gradient(135deg,#4f46e5,#7c3aed);
                    border:none; color:white; font-size:0.875rem; font-weight:600;
                    cursor:pointer; box-shadow:0 4px 16px rgba(79,70,229,0.4);
                "><i class="fas fa-save me-1"></i> Update Room</button>
            </div>
        </form>
    </div>
</div>

<style>
@keyframes modalSlideIn {
    from { opacity:0; transform:translateY(-20px) scale(0.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);    }
}
#addRoomOverlay input:focus,
#addRoomOverlay select:focus,
#addRoomOverlay textarea:focus,
#editRoomOverlay input:focus,
#editRoomOverlay select:focus,
#editRoomOverlay textarea:focus {
    border-color: #818cf8 !important;
    box-shadow: 0 0 0 3px rgba(79,70,229,0.3);
    background: rgba(255,255,255,0.15) !important;
}
#addRoomOverlay select option,
#editRoomOverlay select option { background: #1e1b4b; color: white; }
</style>

<script>
/* ── Add Modal ── */
function openAddModal() {
    // Reset add form image preview
    document.getElementById('add_image_preview').style.display = 'none';
    document.getElementById('add_drop_hint').style.display = 'block';
    document.getElementById('add_room_image').value = '';
    var el = document.getElementById('addRoomOverlay');
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeAddModal() {
    document.getElementById('addRoomOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

/* ── Edit Modal ── */
function openEditModal(room) {
    document.getElementById('edit_room_id').value     = room.room_id;
    document.getElementById('edit_room_number').value = room.room_number;
    document.getElementById('edit_type_id').value     = room.room_type_id;
    document.getElementById('edit_capacity').value    = room.capacity;
    document.getElementById('edit_price').value       = room.price_per_semester;
    document.getElementById('edit_status').value      = room.status;
    document.getElementById('edit_description').value = room.description || '';

    // Show current photo if available
    var photoWrap = document.getElementById('edit_current_photo_wrap');
    var currentPhoto = document.getElementById('edit_current_photo');
    if (room.photo_url) {
        currentPhoto.src = '../' + room.photo_url;
        photoWrap.style.display = 'block';
    } else {
        photoWrap.style.display = 'none';
    }

    // Reset new-upload preview
    document.getElementById('edit_image_preview').style.display = 'none';
    document.getElementById('edit_drop_hint').style.display = 'block';
    document.getElementById('edit_room_image').value = '';

    var el = document.getElementById('editRoomOverlay');
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeEditRoomModal() {
    document.getElementById('editRoomOverlay').style.display = 'none';
    document.body.style.overflow = '';
}

/* ── Image preview helpers ── */
function previewAddImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('add_image_preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('add_drop_hint').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function previewEditImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById('edit_image_preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.getElementById('edit_drop_hint').style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function handleAddDrop(event) {
    event.preventDefault();
    var dt = event.dataTransfer;
    if (dt.files && dt.files[0]) {
        document.getElementById('add_room_image').files = dt.files;
        previewAddImage(document.getElementById('add_room_image'));
    }
    var zone = document.getElementById('addDropZone');
    zone.style.borderColor = 'rgba(129,140,248,0.45)';
    zone.style.background  = 'rgba(255,255,255,0.04)';
}
function handleEditDrop(event) {
    event.preventDefault();
    var dt = event.dataTransfer;
    if (dt.files && dt.files[0]) {
        document.getElementById('edit_room_image').files = dt.files;
        previewEditImage(document.getElementById('edit_room_image'));
    }
    var zone = document.getElementById('editDropZone');
    zone.style.borderColor = 'rgba(129,140,248,0.45)';
    zone.style.background  = 'rgba(255,255,255,0.04)';
}

/* Close on Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closeAddModal(); closeEditRoomModal(); }
});
</script>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
