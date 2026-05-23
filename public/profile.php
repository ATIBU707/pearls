<?php
/**
 * User Profile Page
 * Online Hostel Management System
 */

require_once '../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/models/User.php';

requireLogin();

$userModel  = new User();
$user_id    = getCurrentUserId();
$user       = $userModel->getById($user_id);
$isAdmin    = ($user['role'] ?? '') === 'admin';

$success = '';
$error   = '';

// ── Handle avatar upload ───────────────────────────────────────────────────
function handleAvatarUpload(array $file, int $user_id, string $oldPath = ''): string|false
{
    if ($file['error'] === UPLOAD_ERR_NO_FILE) return false;
    if ($file['error'] !== UPLOAD_ERR_OK)       return false;

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $finfo   = finfo_open(FILEINFO_MIME_TYPE);
    $mime    = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed))     return false;
    if ($file['size'] > 3 * 1024 * 1024) return false; // 3 MB

    $dir = __DIR__ . '/assets/images/avatars/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) ?: 'jpg';
    $filename = 'avatar_' . $user_id . '_' . uniqid() . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    // Delete old avatar (not the default placeholder)
    if (!empty($oldPath) && strpos($oldPath, 'default-avatar') === false) {
        $absOld = __DIR__ . '/' . ltrim($oldPath, '/');
        if (file_exists($absOld)) @unlink($absOld);
    }
    return 'assets/images/avatars/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    // ── Update Profile Info ──────────────────────────────────────────────
    if (isset($_POST['update_profile'])) {
        $data = [
            'first_name'   => sanitize($_POST['first_name']   ?? ''),
            'last_name'    => sanitize($_POST['last_name']    ?? ''),
            'phone_number' => sanitize($_POST['phone_number'] ?? ''),
        ];

        // Student-only fields
        if (!$isAdmin) {
            $data['identification_type']   = in_array($_POST['id_type'] ?? '', ['student_id','national_id','passport'])
                                              ? $_POST['id_type'] : 'student_id';
            $data['identification_number'] = sanitize($_POST['id_number'] ?? '');
        }

        // Handle avatar upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $uploaded = handleAvatarUpload($_FILES['profile_photo'], $user_id, $user['profile_photo'] ?? '');
            if ($uploaded !== false) {
                $data['profile_photo'] = $uploaded;
            } else {
                $error = 'Invalid photo. Use JPG/PNG/WebP under 3 MB.';
            }
        }

        if (!$error) {
            if ($userModel->update($user_id, $data)) {
                $_SESSION['first_name'] = $data['first_name'];
                $_SESSION['last_name']  = $data['last_name'];
                $success = 'Profile updated successfully!';
                $user    = $userModel->getById($user_id); // refresh
            } else {
                $error = 'Failed to update profile.';
            }
        }
    }

    // ── Change Password ──────────────────────────────────────────────────
    elseif (isset($_POST['update_password'])) {
        $old = $_POST['current_password'] ?? '';
        $new = $_POST['new_password']     ?? '';
        $cfm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $cfm) {
            $error = 'New passwords do not match.';
        } elseif (!password_verify($old, $user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } else {
            if ($userModel->updatePassword($user_id, $new)) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password.';
            }
        }
    }
}

// ── Safe fallbacks to avoid null errors ───────────────────────────────────
$safeFirst  = htmlspecialchars($user['first_name']  ?? '', ENT_QUOTES, 'UTF-8');
$safeLast   = htmlspecialchars($user['last_name']   ?? '', ENT_QUOTES, 'UTF-8');
$safeEmail  = htmlspecialchars($user['email']       ?? '', ENT_QUOTES, 'UTF-8');
$safePhone  = htmlspecialchars($user['phone_number']?? '', ENT_QUOTES, 'UTF-8');
$safeIdNum  = htmlspecialchars($user['identification_number'] ?? '', ENT_QUOTES, 'UTF-8');
$safeStId   = htmlspecialchars($user['student_id']  ?? '', ENT_QUOTES, 'UTF-8');
$idType     = $user['identification_type'] ?? 'student_id';
$avatarSrc  = htmlspecialchars($user['profile_photo'] ?: 'assets/images/default-avatar.png', ENT_QUOTES, 'UTF-8');
$roleLabel  = $isAdmin ? 'Administrator' : 'Student';
$roleBadge  = $isAdmin
    ? '<span style="background:rgba(79,70,229,0.2);color:#818cf8;border:1px solid rgba(79,70,229,0.35);padding:3px 12px;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">ADMIN</span>'
    : '<span style="background:rgba(6,182,212,0.15);color:#06b6d4;border:1px solid rgba(6,182,212,0.3);padding:3px 12px;border-radius:999px;font-size:0.72rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">STUDENT</span>';

$page_title = 'My Profile';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-4">

    <!-- ── Avatar Header ── -->
    <div class="row mb-4 animate-fade-down">
        <div class="col-12 text-center">
            <!-- Avatar with upload trigger -->
            <div class="position-relative d-inline-block mb-3" style="cursor:pointer;" onclick="document.getElementById('avatarFileInput').click()" title="Click to change photo">
                <img id="avatarPreview"
                     src="<?php echo $avatarSrc; ?>"
                     class="rounded-circle shadow"
                     style="width:120px;height:120px;object-fit:cover;border:3px solid rgba(79,70,229,0.6);transition:opacity 0.2s;"
                     onmouseover="this.style.opacity='0.8'"
                     onmouseout="this.style.opacity='1'"
                     alt="Profile Photo">
                <div style="
                    position:absolute;bottom:4px;right:4px;
                    width:32px;height:32px;border-radius:50%;
                    background:linear-gradient(135deg,#4f46e5,#7c3aed);
                    display:flex;align-items:center;justify-content:center;
                    box-shadow:0 2px 8px rgba(79,70,229,0.5);
                    border:2px solid #1e1b4b;
                ">
                    <i class="fas fa-camera" style="color:white;font-size:0.72rem;"></i>
                </div>
            </div>

            <h1 class="h3 mb-1"><?php echo $safeFirst . ' ' . $safeLast; ?></h1>
            <p class="text-muted mb-2"><?php echo $safeEmail; ?></p>
            <?php echo $roleBadge; ?>
        </div>
    </div>

    <!-- ── Alerts ── -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── Personal Information ── -->
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 rounded-4 animate-fade-up">
                <div class="card-body p-4">
                    <h5 class="mb-1">
                        <i class="fas fa-user-circle me-2" style="color:#818cf8;"></i>
                        Personal Information
                    </h5>
                    <p class="text-muted small mb-4">
                        <?php echo $isAdmin ? 'Manage your administrator account details.' : 'Manage your student account details.'; ?>
                    </p>

                    <form method="POST" enctype="multipart/form-data">
                        <?php echo getCSRFTokenInput(); ?>
                        <!-- Hidden file input for avatar -->
                        <input type="file" id="avatarFileInput" name="profile_photo" accept="image/*" style="display:none" onchange="previewAvatar(this)">

                        <div class="row g-3">
                            <!-- First Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">First Name</label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo $safeFirst; ?>" required>
                            </div>
                            <!-- Last Name -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo $safeLast; ?>" required>
                            </div>
                            <!-- Phone -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="tel" name="phone_number" class="form-control" value="<?php echo $safePhone; ?>" placeholder="+256 7xx xxx xxx">
                            </div>
                            <!-- Email (read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" value="<?php echo $safeEmail; ?>" disabled style="opacity:0.6;">
                                <div class="form-text" style="font-size:0.72rem;">Email cannot be changed.</div>
                            </div>

                            <?php if ($isAdmin): ?>
                            <!-- Admin: Role display -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Role</label>
                                <input type="text" class="form-control" value="Administrator" disabled style="opacity:0.6;">
                            </div>
                            <!-- Admin: Department / Title (cosmetic) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Account Type</label>
                                <input type="text" class="form-control" value="Hostel Management Staff" disabled style="opacity:0.6;">
                            </div>

                            <?php else: ?>
                            <!-- Student: Student ID (read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Student ID</label>
                                <input type="text" class="form-control" value="<?php echo $safeStId ?: 'Not assigned'; ?>" disabled style="opacity:0.6;">
                            </div>
                            <!-- Student: ID Type -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold">ID Type</label>
                                <select name="id_type" class="form-select">
                                    <option value="student_id" <?php echo $idType === 'student_id'  ? 'selected' : ''; ?>>Student ID</option>
                                    <option value="national_id"<?php echo $idType === 'national_id' ? 'selected' : ''; ?>>National ID</option>
                                    <option value="passport"   <?php echo $idType === 'passport'    ? 'selected' : ''; ?>>Passport</option>
                                </select>
                            </div>
                            <!-- Student: ID Number -->
                            <div class="col-12">
                                <label class="form-label fw-bold">ID Number</label>
                                <input type="text" name="id_number" class="form-control" value="<?php echo $safeIdNum; ?>" placeholder="Enter your ID number">
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Profile Photo upload hint -->
                        <div class="mt-3 p-3 rounded-3" style="background:rgba(79,70,229,0.08);border:1px dashed rgba(129,140,248,0.35);">
                            <div class="d-flex align-items-center gap-3">
                                <img id="avatarMiniPreview" src="<?php echo $avatarSrc; ?>" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid rgba(129,140,248,0.5);" alt="avatar">
                                <div>
                                    <div style="font-size:0.825rem;font-weight:600;color:#f1f5f9;">Profile Photo</div>
                                    <div style="font-size:0.75rem;color:#94a3b8;">
                                        <button type="button" onclick="document.getElementById('avatarFileInput').click()" style="background:none;border:none;color:#818cf8;padding:0;font-size:0.75rem;cursor:pointer;text-decoration:underline;">Click to upload</button>
                                        &nbsp;&bull;&nbsp;JPG / PNG / WebP &bull; max 3 MB
                                    </div>
                                    <div id="avatarFilename" style="font-size:0.72rem;color:#4ade80;display:none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" name="update_profile" class="btn btn-primary px-5 rounded-pill">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Security ── -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 rounded-4 animate-fade-up" style="animation-delay:0.1s;">
                <div class="card-body p-4">
                    <h5 class="mb-1">
                        <i class="fas fa-shield-alt me-2" style="color:#818cf8;"></i>Security
                    </h5>
                    <p class="text-muted small mb-4">Change your account password.</p>

                    <form method="POST">
                        <?php echo getCSRFTokenInput(); ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">New Password</label>
                            <input type="password" name="new_password" id="newPwdInput" class="form-control" placeholder="Min. 6 characters" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="cfmPwdInput" class="form-control" placeholder="Repeat new password" required>
                            <div id="pwdMatchHint" style="font-size:0.75rem;margin-top:4px;display:none;"></div>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-outline-primary w-100 rounded-pill">
                            <i class="fas fa-lock me-2"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Account Info card -->
            <div class="card shadow-sm border-0 rounded-4 mt-4 animate-fade-up" style="animation-delay:0.2s;">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2" style="color:#818cf8;"></i>Account Info
                    </h5>
                    <div style="display:flex;flex-direction:column;gap:12px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,0.05);">
                            <span style="font-size:0.82rem;color:#94a3b8;">Member Since</span>
                            <span style="font-size:0.85rem;font-weight:600;color:#f1f5f9;"><?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,0.05);">
                            <span style="font-size:0.82rem;color:#94a3b8;">Account Role</span>
                            <?php echo $roleBadge; ?>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:rgba(255,255,255,0.05);">
                            <span style="font-size:0.82rem;color:#94a3b8;">Account Status</span>
                            <span style="background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);padding:2px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
/* ── Avatar live preview ── */
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src     = e.target.result;
            document.getElementById('avatarMiniPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
        var fn = document.getElementById('avatarFilename');
        fn.textContent = '✓ ' + input.files[0].name;
        fn.style.display = 'block';
    }
}

/* ── Password match indicator ── */
var newPwd = document.getElementById('newPwdInput');
var cfmPwd = document.getElementById('cfmPwdInput');
var hint   = document.getElementById('pwdMatchHint');
function checkMatch() {
    if (!cfmPwd.value) { hint.style.display='none'; return; }
    if (newPwd.value === cfmPwd.value) {
        hint.textContent = '✓ Passwords match';
        hint.style.color = '#4ade80';
    } else {
        hint.textContent = '✗ Passwords do not match';
        hint.style.color = '#f87171';
    }
    hint.style.display = 'block';
}
newPwd.addEventListener('input', checkMatch);
cfmPwd.addEventListener('input', checkMatch);
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
