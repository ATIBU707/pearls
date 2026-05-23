<?php
/**
 * AuthMiddleware - Route protection helpers
 * Online Hostel Management System
 */

/**
 * Ensure user is logged in; redirect to login if not.
 * Also handles session timeout check.
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI']);
        header('Location: ' . APP_URL . 'auth/login.php?redirect=' . $redirect);
        exit;
    }
}

/**
 * Ensure user is an admin; redirect / deny if not.
 */
function requireAdminAuth() {
    requireAuth();
    if (getCurrentUserRole() !== 'admin') {
        http_response_code(403);
        // Show a friendly 403 page
        include VIEWS_PATH . '/layouts/header.php';
        echo '<div class="container mt-5 text-center">
                <div class="card p-5 shadow">
                    <i class="fas fa-lock fa-4x text-danger mb-4"></i>
                    <h2 class="text-danger">Access Denied</h2>
                    <p class="lead">You do not have permission to access this page.</p>
                    <a href="' . APP_URL . '" class="btn btn-primary mt-3">
                        <i class="fas fa-home"></i> Go Home
                    </a>
                </div>
              </div>';
        include VIEWS_PATH . '/layouts/footer.php';
        exit;
    }
}

/**
 * Ensure user is a student; deny admin access.
 */
function requireStudentAuth() {
    requireAuth();
    if (getCurrentUserRole() !== 'student') {
        header('Location: ' . APP_URL . 'admin/index.php');
        exit;
    }
}

/**
 * Redirect already-logged-in users away from auth pages.
 * Call this at the top of login.php / register.php.
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $redirect = (getCurrentUserRole() === 'admin')
            ? APP_URL . 'admin/index.php'
            : APP_URL . 'dashboard.php';
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * Verify CSRF token from POST and die with error if invalid.
 */
function verifyCsrfOrDie() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCSRFToken($token)) {
            http_response_code(403);
            die('Invalid CSRF token. Please go back and try again.');
        }
    }
}
?>
