<?php
/**
 * Login Page
 * Online Hostel Management System - Pearls of Wisdom Hostel
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/AuthService.php';

redirectIfLoggedIn();

$page_title = 'Login';
$error   = '';
$success = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $authService = new AuthService();
    $result = $authService->login(
        trim($_POST['email']    ?? ''),
        trim($_POST['password'] ?? '')
    );

    if ($result['success']) {
        // Check if there is a redirect URL
        $redirect = $_GET['redirect'] ?? $result['redirect'];
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = $result['message'];
    }
}

// Flash messages
$alert = getAlertMessage();
if ($alert) {
    $success = $alert['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to Pearls of Wisdom Hostel Management System">
    <title>Login – Pearls of Wisdom Hostel</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS via CDN (no local copy needed for auth pages) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom Styles -->
    <link href="<?php echo APP_URL; ?>assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">

    <!-- Animated background blobs -->
    <div class="auth-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <!-- Top nav -->
    <nav class="auth-topnav">
        <a href="<?php echo APP_URL; ?>" class="auth-topnav-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <a href="register.php" class="auth-topnav-action">
            New here? Register <i class="fas fa-user-plus"></i>
        </a>
    </nav>

    <div class="auth-wrapper">

        <!-- Logo / Brand -->
        <div class="auth-brand animate-fade-down">
            <a href="<?php echo APP_URL; ?>" class="auth-logo-link">
                <div class="auth-logo-icon" style="overflow:hidden;padding:0;">
                    <img src="<?php echo APP_URL; ?>assets/images/hostel-logo.jpg"
                         alt="Pearls of Wisdom Hostel"
                         style="width:52px;height:52px;object-fit:cover;border-radius:14px;display:block;">
                </div>
                <div>
                    <div class="auth-logo-title">Pearls of Wisdom</div>
                    <div class="auth-logo-sub">Hostel Management System</div>
                </div>
            </a>
        </div>

        <!-- Card -->
        <div class="auth-card animate-fade-up">
            <div class="auth-card-header">
                <h1 class="auth-heading">Welcome back</h1>
                <p class="auth-subheading">Sign in to your account to continue</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
            <div class="auth-alert auth-alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="auth-alert auth-alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['timeout'])): ?>
            <div class="auth-alert auth-alert-warning animate-fade-in">
                <i class="fas fa-clock"></i>
                <span>Your session timed out. Please log in again.</span>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form id="loginForm" method="POST" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" novalidate>
                <?php echo getCSRFTokenInput(); ?>

                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email Address</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope auth-input-icon"></i>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="auth-input"
                            placeholder="your@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                            autocomplete="email"
                        >
                    </div>
                    <span class="auth-field-error" id="emailError"></span>
                </div>

                <div class="auth-form-group">
                    <div class="auth-label-row">
                        <label for="password" class="auth-label">Password</label>
                        <a href="forgot-password.php" class="auth-forgot-link">Forgot password?</a>
                    </div>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock auth-input-icon"></i>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="auth-input"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="auth-toggle-password" id="togglePassword" aria-label="Show password">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <span class="auth-field-error" id="passwordError"></span>
                </div>

                <div class="auth-form-group auth-remember">
                    <label class="auth-checkbox-label">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <span class="auth-checkbox-custom"></span>
                        Keep me signed in
                    </label>
                </div>

                <button type="submit" class="auth-btn-submit" id="loginBtn">
                    <span class="btn-text">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </span>
                    <span class="btn-loading" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Signing in…
                    </span>
                </button>
            </form>

            <!-- Demo credentials notice -->
            <div class="auth-demo-box">
                <p class="auth-demo-title"><i class="fas fa-info-circle"></i> Demo Credentials</p>
                <p>Admin: <strong>admin@pearlswisdom.com</strong> / <strong>password</strong></p>
            </div>

            <p class="auth-footer-link">
                Don't have an account?
                <a href="register.php">Create one now <i class="fas fa-arrow-right"></i></a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>assets/js/form-validation.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Password toggle
        const toggleBtn  = document.getElementById('togglePassword');
        const pwdField   = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        toggleBtn.addEventListener('click', () => {
            const show = pwdField.type === 'password';
            pwdField.type  = show ? 'text' : 'password';
            toggleIcon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
        });

        // Client-side validation
        const form      = document.getElementById('loginForm');
        const loginBtn  = document.getElementById('loginBtn');

        form.addEventListener('submit', (e) => {
            let valid = true;

            // Email
            const email = document.getElementById('email');
            clearError('emailError');
            if (!email.value.trim()) {
                showFieldError('emailError', 'Email is required.');
                valid = false;
            } else if (!isValidEmail(email.value.trim())) {
                showFieldError('emailError', 'Please enter a valid email.');
                valid = false;
            }

            // Password
            const pwd = document.getElementById('password');
            clearError('passwordError');
            if (!pwd.value) {
                showFieldError('passwordError', 'Password is required.');
                valid = false;
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            // Loading state
            loginBtn.querySelector('.btn-text').style.display = 'none';
            loginBtn.querySelector('.btn-loading').style.display = 'inline-flex';
            loginBtn.disabled = true;
        });
    });
    </script>
</body>
</html>
