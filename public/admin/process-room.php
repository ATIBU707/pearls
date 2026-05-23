<?php
/**
 * Admin Room CRUD Handler
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/Room.php';
require_once APP_PATH . '/services/NotificationService.php';

requireAdminAuth();
verifyCsrfOrDie();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: rooms.php');
    exit;
}

$action    = trim($_POST['action'] ?? 'add');
$roomModel = new Room();

// ── Shared: handle image upload ────────────────────────────────────────────
/**
 * Uploads a room image and returns the web-accessible path, or false on error.
 * @param array  $file       $_FILES entry
 * @param string $oldPath    existing photo_url to delete (edit flow)
 * @return string|false
 */
function handleRoomImageUpload(array $file, string $oldPath = ''): string|false
{
    // No file chosen — keep existing
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldPath ?: false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Validate type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return false;
    }

    // Max 5 MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }

    $uploadDir = __DIR__ . '/../assets/images/rooms/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $filename = 'room_' . uniqid() . '.' . strtolower($ext);
    $dest     = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return false;
    }

    // Delete old image if it exists and isn't the placeholder
    if (!empty($oldPath) && strpos($oldPath, 'room-placeholder') === false) {
        $absOld = __DIR__ . '/../' . ltrim($oldPath, '/');
        if (file_exists($absOld)) {
            @unlink($absOld);
        }
    }

    return 'assets/images/rooms/' . $filename;
}

// ── ADD ROOM ──────────────────────────────────────────────
if ($action === 'add') {
    $room_number = sanitize($_POST['room_number']  ?? '');
    $type_id     = (int)($_POST['type_id']         ?? 0);
    $capacity    = (int)($_POST['capacity']         ?? 1);
    $price       = (float)($_POST['price']          ?? 0);
    $description = sanitize($_POST['description']   ?? '');
    $status      = $_POST['status']                 ?? 'available';

    if (empty($room_number) || !$type_id || $price <= 0) {
        redirectWithMessage('rooms.php', 'Please fill all required room fields.', 'error');
    }

    $allowed_statuses = ['available', 'booked', 'occupied', 'maintenance'];
    if (!in_array($status, $allowed_statuses)) $status = 'available';

    // Handle image upload
    $photo_url = '';
    if (!empty($_FILES['room_image']['name'])) {
        $uploaded = handleRoomImageUpload($_FILES['room_image']);
        if ($uploaded === false) {
            redirectWithMessage('rooms.php', 'Invalid image. Use JPG/PNG/WebP under 5 MB.', 'error');
        }
        $photo_url = $uploaded;
    }

    $insertData = [
        'room_number'        => $room_number,
        'room_type_id'       => $type_id,
        'capacity'           => $capacity,
        'price_per_semester' => $price,
        'description'        => $description,
        'status'             => $status,
    ];
    if ($photo_url) {
        $insertData['photo_url'] = $photo_url;
    }

    $result = $roomModel->insert($insertData);

    if ($result) {
        // Notify all active students about the new room
        $typeName = getRow("SELECT type_name FROM room_types WHERE type_id = ?", [$type_id]);
        NotificationService::newRoomAvailable(
            $room_number,
            $typeName['type_name'] ?? 'Standard',
            number_format($price, 0) . ' UGX'
        );
        redirectWithMessage('rooms.php', 'Room ' . $room_number . ' added successfully!', 'success');
    } else {
        redirectWithMessage('rooms.php', 'Failed to add room. Room number may already exist.', 'error');
    }
}

// ── EDIT ROOM ─────────────────────────────────────────────
if ($action === 'edit') {
    $room_id     = (int)($_POST['room_id']          ?? 0);
    $room_number = sanitize($_POST['room_number']   ?? '');
    $type_id     = (int)($_POST['type_id']          ?? 0);
    $capacity    = (int)($_POST['capacity']          ?? 1);
    $price       = (float)($_POST['price']           ?? 0);
    $description = sanitize($_POST['description']    ?? '');
    $status      = $_POST['status']                  ?? 'available';

    if (!$room_id || empty($room_number) || !$type_id || $price <= 0) {
        redirectWithMessage('rooms.php', 'Invalid room edit data.', 'error');
    }

    // Fetch existing room to get old photo_url
    $existing  = $roomModel->getById($room_id);
    $old_photo = $existing['photo_url'] ?? '';

    // Handle image upload
    $photo_url = $old_photo; // keep existing by default
    if (!empty($_FILES['room_image']['name'])) {
        $uploaded = handleRoomImageUpload($_FILES['room_image'], $old_photo);
        if ($uploaded === false) {
            redirectWithMessage('rooms.php', 'Invalid image. Use JPG/PNG/WebP under 5 MB.', 'error');
        }
        $photo_url = $uploaded;
    }

    $updateData = [
        'room_number'        => $room_number,
        'room_type_id'       => $type_id,
        'capacity'           => $capacity,
        'price_per_semester' => $price,
        'description'        => $description,
        'status'             => $status,
        'photo_url'          => $photo_url,
    ];

    $result = $roomModel->update($room_id, $updateData);

    if ($result) {
        redirectWithMessage('rooms.php', 'Room updated successfully.', 'success');
    } else {
        redirectWithMessage('rooms.php', 'Failed to update room.', 'error');
    }
}

// ── DELETE ROOM ───────────────────────────────────────────
if ($action === 'delete') {
    $room_id = (int)($_POST['room_id'] ?? 0);

    if (!$room_id) {
        redirectWithMessage('rooms.php', 'Invalid room ID.', 'error');
    }

    // Safety: don't delete rooms with active bookings
    $hasBookings = getValue(
        "SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status IN ('pending','confirmed','checked_in')",
        [$room_id]
    );

    if ($hasBookings > 0) {
        redirectWithMessage('rooms.php', 'Cannot delete room with active bookings.', 'error');
    }

    // Delete the room photo from disk before deleting the record
    $existing = $roomModel->getById($room_id);
    if (!empty($existing['photo_url']) && strpos($existing['photo_url'], 'room-placeholder') === false) {
        $absPhoto = __DIR__ . '/../' . ltrim($existing['photo_url'], '/');
        if (file_exists($absPhoto)) {
            @unlink($absPhoto);
        }
    }

    $result = $roomModel->delete($room_id);

    if ($result) {
        redirectWithMessage('rooms.php', 'Room deleted successfully.', 'success');
    } else {
        redirectWithMessage('rooms.php', 'Failed to delete room.', 'error');
    }
}

// Fallback
header('Location: rooms.php');
exit;
?>
