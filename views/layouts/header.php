<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Pearls of Wisdom Hostel Management System">
    <meta name="author" content="Wasswa Atibu & Karim Abdul">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Pearls of Wisdom Hostel</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>assets/css/sidebar.css" rel="stylesheet">
</head>

<?php
$role    = getCurrentUserRole();
$isLogin = isLoggedIn();
$current = basename($_SERVER['PHP_SELF']);
$dir     = basename(dirname($_SERVER['PHP_SELF']));

// Helper: active class
if (!function_exists('sidebarActive')) {
    function sidebarActive($file, $current, $dir = '') {
        $matches = (array)$file;
        foreach ($matches as $f) {
            $parts = explode('/', $f);
            $fname = end($parts);
            $fdir  = count($parts) > 1 ? $parts[0] : '';
            if ($fname === $current && ($fdir === '' || $fdir === $dir)) return ' active';
        }
        return '';
    }
}

// Fetch current user's profile photo for avatar display
$_layoutProfilePhoto = '';
if ($isLogin) {
    $_layoutUser = getRow("SELECT profile_photo FROM users WHERE user_id = ?", [getCurrentUserId()]);
    $_layoutProfilePhoto = $_layoutUser['profile_photo'] ?? '';
}
?>

<body>
<?php if (!$isLogin): ?>
<!-- ══ PUBLIC LAYOUT (no sidebar) ══════════════════ -->
<nav class="navbar navbar-expand-lg navbar-dark public-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="<?php echo APP_URL; ?>">
            <span class="brand-icon" style="overflow:hidden;padding:0;background:none;box-shadow:none;">
                <img src="<?php echo APP_URL; ?>assets/images/hostel-logo.jpg"
                     alt="Pearls of Wisdom"
                     style="width:36px;height:36px;object-fit:cover;border-radius:9px;display:block;">
            </span>
            <span><strong>Pearls of Wisdom</strong></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>"><i class="fas fa-home me-1"></i>Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo APP_URL; ?>auth/login.php"><i class="fas fa-sign-in-alt me-1"></i>Login</a></li>
                <li class="nav-item"><a class="btn btn-primary ms-2 px-4" href="<?php echo APP_URL; ?>auth/register.php"><i class="fas fa-user-plus me-1"></i>Register</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="public-main">

<?php else: ?>
<!-- ══ AUTHENTICATED LAYOUT (sidebar) ═════════════ -->

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ══ SIDEBAR ══════════════════════════════════ -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon" style="overflow:hidden;padding:0;">
            <img src="<?php echo APP_URL; ?>assets/images/hostel-logo.jpg"
                 alt="Pearls of Wisdom Hostel"
                 style="width:42px;height:42px;object-fit:cover;border-radius:11px;display:block;">
        </div>
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-name">Pearls of Wisdom</span>
            <span class="sidebar-brand-sub">Hostel System</span>
        </div>
        <button class="sidebar-close-btn d-lg-none" onclick="closeSidebar()">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Role label -->
    <div class="sidebar-role-label">
        <?php if ($role === 'admin'): ?>
        <span class="sidebar-role-badge admin"><i class="fas fa-shield-alt me-1"></i>Administrator</span>
        <?php else: ?>
        <span class="sidebar-role-badge student"><i class="fas fa-graduation-cap me-1"></i>Student Portal</span>
        <?php endif; ?>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <?php if ($role === 'admin'): ?>
        <!-- ── Admin Navigation ── -->
        <p class="sidebar-section-label">Main</p>

        <a href="<?php echo APP_URL; ?>admin/index.php"
           class="sidebar-link<?php echo sidebarActive('index.php', $current, $dir === 'admin' ? 'admin' : ''); ?>">
            <i class="fas fa-chart-pie sidebar-link-icon"></i>
            <span>Dashboard</span>
        </a>

        <p class="sidebar-section-label">Management</p>

        <a href="<?php echo APP_URL; ?>admin/rooms.php"
           class="sidebar-link<?php echo sidebarActive('rooms.php', $current, 'admin'); ?>">
            <i class="fas fa-door-open sidebar-link-icon"></i>
            <span>Rooms</span>
        </a>
        <a href="<?php echo APP_URL; ?>admin/bookings.php"
           class="sidebar-link<?php echo sidebarActive('bookings.php', $current, 'admin'); ?>">
            <i class="fas fa-calendar-check sidebar-link-icon"></i>
            <span>Bookings</span>
        </a>
        <a href="<?php echo APP_URL; ?>admin/students.php"
           class="sidebar-link<?php echo sidebarActive('students.php', $current, 'admin'); ?>">
            <i class="fas fa-users sidebar-link-icon"></i>
            <span>Students</span>
        </a>
        <a href="<?php echo APP_URL; ?>admin/payments.php"
           class="sidebar-link<?php echo sidebarActive('payments.php', $current, 'admin'); ?>">
            <i class="fas fa-credit-card sidebar-link-icon"></i>
            <span>Payments</span>
        </a>
        <a href="<?php echo APP_URL; ?>admin/maintenance.php"
           class="sidebar-link<?php echo sidebarActive('maintenance.php', $current, 'admin'); ?>">
            <i class="fas fa-tools sidebar-link-icon"></i>
            <span>Maintenance</span>
        </a>

        <p class="sidebar-section-label">Analytics</p>

        <a href="<?php echo APP_URL; ?>admin/reports.php"
           class="sidebar-link<?php echo sidebarActive('reports.php', $current, 'admin'); ?>">
            <i class="fas fa-chart-bar sidebar-link-icon"></i>
            <span>Reports</span>
        </a>

        <a href="<?php echo APP_URL; ?>admin/reminders.php"
           class="sidebar-link<?php echo sidebarActive('reminders.php', $current, 'admin'); ?>">
            <i class="fas fa-envelope sidebar-link-icon"></i>
            <span>Email Reminders</span>
        </a>

        <?php else: ?>
        <!-- ── Student Navigation ── -->
        <p class="sidebar-section-label">Menu</p>

        <a href="<?php echo APP_URL; ?>dashboard.php"
           class="sidebar-link<?php echo sidebarActive('dashboard.php', $current); ?>">
            <i class="fas fa-th-large sidebar-link-icon"></i>
            <span>Dashboard</span>
        </a>
        <a href="<?php echo APP_URL; ?>rooms.php"
           class="sidebar-link<?php echo sidebarActive('rooms.php', $current); ?>">
            <i class="fas fa-door-open sidebar-link-icon"></i>
            <span>Browse Rooms</span>
        </a>

        <p class="sidebar-section-label">My Account</p>

        <a href="<?php echo APP_URL; ?>booking-confirmation.php"
           class="sidebar-link<?php echo sidebarActive('booking-confirmation.php', $current); ?>">
            <i class="fas fa-calendar-check sidebar-link-icon"></i>
            <span>My Bookings</span>
        </a>
        <a href="<?php echo APP_URL; ?>maintenance.php"
           class="sidebar-link<?php echo sidebarActive('maintenance.php', $current); ?>">
            <i class="fas fa-tools sidebar-link-icon"></i>
            <span>Maintenance</span>
        </a>
        <a href="<?php echo APP_URL; ?>payments.php"
           class="sidebar-link<?php echo sidebarActive('payments.php', $current); ?>">
            <i class="fas fa-credit-card sidebar-link-icon"></i>
            <span>My Payments</span>
        </a>
        <a href="<?php echo APP_URL; ?>notifications.php"
           class="sidebar-link<?php echo sidebarActive('notifications.php', $current); ?>">
            <i class="fas fa-bell sidebar-link-icon"></i>
            <span>Notifications</span>
            <?php
            $notifCount = 0;
            if (isLoggedIn()) {
                $notifCount = (int)(getValue("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [getCurrentUserId()]) ?? 0);
            }
            if ($notifCount > 0): ?>
            <span class="sidebar-badge"><?php echo $notifCount; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Sidebar Footer: user + logout -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <?php if (!empty($_layoutProfilePhoto)): ?>
            <img src="<?php echo htmlspecialchars(APP_URL . $_layoutProfilePhoto); ?>"
                 alt="Profile"
                 class="sidebar-avatar"
                 style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;border:2px solid rgba(129,140,248,0.5);">
            <?php else: ?>
            <div class="sidebar-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1)); ?>
            </div>
            <?php endif; ?>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo htmlspecialchars(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '')); ?></span>
                <span class="sidebar-user-role"><?php echo ucfirst($role); ?></span>
            </div>
        </div>
        <div class="sidebar-footer-links">
            <a href="<?php echo APP_URL; ?>profile.php" class="sidebar-footer-btn" title="Profile">
                <i class="fas fa-user-cog"></i>
            </a>
            <a href="<?php echo APP_URL; ?>auth/logout.php" class="sidebar-footer-btn danger" title="Logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</aside>

<!-- ══ MAIN WRAPPER ══════════════════════════════ -->
<div class="app-wrapper">

    <!-- Top bar (thin header above content) -->
    <header class="topbar">
        <button class="topbar-toggle d-lg-none" onclick="openSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-title">
            <?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?>
        </div>
        <div class="topbar-actions">
            <?php if ($role === 'student'): ?>
            <a href="<?php echo APP_URL; ?>notifications.php" class="topbar-icon-btn" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if ($notifCount > 0): ?>
                <span class="topbar-badge"><?php echo $notifCount; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <div class="topbar-user">
                <?php if (!empty($_layoutProfilePhoto)): ?>
                <a href="<?php echo APP_URL; ?>profile.php" style="display:block;flex-shrink:0;">
                    <img src="<?php echo htmlspecialchars(APP_URL . $_layoutProfilePhoto); ?>"
                         alt="Profile"
                         style="width:34px;height:34px;border-radius:50%;object-fit:cover;
                                border:2px solid rgba(129,140,248,0.6);
                                transition:border-color 0.2s;"
                         onmouseover="this.style.borderColor='#818cf8'"
                         onmouseout="this.style.borderColor='rgba(129,140,248,0.6)'">
                </a>
                <?php else: ?>
                <div class="topbar-avatar">
                    <?php echo strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)); ?>
                </div>
                <?php endif; ?>
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
            </div>
        </div>
    </header>

    <!-- Page content -->
    <main class="app-main">
<?php endif; ?>
