<?php
/**
 * Forgot Password Page
 * Online Hostel Management System - Pearls of Wisdom Hostel
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/AuthService.php';

redirectIfLoggedIn();

$page_title = 'Forgot Password';
$error   = '';
$success = '';
$step    = 'request'; // 'request' | 'reset'

// Check if a reset token is in the URL (from emailed link or demo redirect)
if (isset($_GET['token'])) {
    $step = 'reset';
    $token = htmlspecialchars($_GET['token']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $authService = new AuthService();

    if (isset($_POST['action']) && $_POST['action'] === 'reset') {
        // Reset password step
        $result = $authService->resetPassword(
            $_POST['token']            ?? '',
            $_POST['password']         ?? '',
            $_POST['confirm_password'] ?? ''
        );

        if ($result['success']) {
            redirectWithMessage(APP_URL . 'auth/login.php', $result['message'], 'success');
        } else {
            $step  = 'reset';
            $token = htmlspecialchars($_POST['token'] ?? '');
            $error = $result['message'];
        }

    } else {
        // Forgot password – send token
        $result = $authService->forgotPassword(trim($_POST['email'] ?? ''));

        if ($result['success']) {
            $success = $result['message'];
            // In a real app the token would be emailed. For demo, redirect to reset form.
            if (isset($result['token'])) {
                $step  = 'reset';
                $token = $result['token'];
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Reset your Pearls of Wisdom Hostel account password">
    <title>Forgot Password – Pearls of Wisdom Hostel</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>assets/css/auth.css" rel="stylesheet">
</head>
<body class="auth-body">

    <div class="auth-bg">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="auth-wrapper">

        <!-- Brand -->
        <div class="auth-brand animate-fade-down">
            <a href="<?php echo APP_URL; ?>" class="auth-logo-link">
                <div class="auth-logo-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div>
                    <div class="auth-logo-title">Pearls of Wisdom</div>
                    <div class="auth-logo-sub">Hostel Management System</div>
                </div>
            </a>
        </div>

        <div class="auth-card animate-fade-up">

            <?php if ($step === 'request'): ?>
            <!-- ── STEP 1: Request Reset ── -->
            <div class="auth-card-header">
                <div class="auth-icon-circle auth-icon-warning">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="auth-heading">Forgot your password?</h1>
                <p class="auth-subheading">
                    Enter your registered email and we'll send you reset instructions.
                </p>
            </div>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="auth-alert auth-alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
            <?php endif; ?>

            <form id="forgotForm" method="POST" action="forgot-password.php" novalidate>
                <?php echo getCSRFTokenInput(); ?>
                <input type="hidden" name="action" value="request">

                <div class="auth-form-group">
                    <label for="email" class="auth-label">Email Address</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-envelope auth-input-icon"></i>
                        <input type="email" id="email" name="email" class="auth-input"
                            placeholder="your@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required autocomplete="email">
                    </div>
                    <span class="auth-field-error" id="emailError"></span>
                </div>

                <button type="submit" class="auth-btn-submit" id="requestBtn">
                    <span class="btn-text">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </span>
                    <span class="btn-loading" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Sending…
                    </span>
                </button>
            </form>

            <?php else: ?>
            <!-- ── STEP 2: Reset Password ── -->
            <div class="auth-card-header">
                <div class="auth-icon-circle auth-icon-success">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h1 class="auth-heading">Set new password</h1>
                <p class="auth-subheading">
                    Choose a strong password to secure your account.
                </p>
            </div>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form id="resetForm" method="POST" action="forgot-password.php" novalidate>
                <?php echo getCSRFTokenInput(); ?>
                <input type="hidden" name="action" value="reset">
                <input type="hidden" name="token"  value="<?php echo $token ?? ''; ?>">

                <div class="auth-form-group">
                    <label for="password" class="auth-label">New Password <span class="required">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock auth-input-icon"></i>
                        <input type="password" id="password" name="password" class="auth-input"
                            placeholder="Create a strong password" required autocomplete="new-password">
                        <button type="button" class="auth-toggle-password" id="togglePassword">
                            <i class="fas fa-eye" id="toggleIconPwd"></i>
                        </button>
                    </div>
                    <div class="pwd-strength-bar">
                        <div class="pwd-strength-fill" id="pwdStrengthFill"></div>
                    </div>
                    <span class="pwd-strength-label" id="pwdStrengthLabel"></span>
                    <span class="auth-field-error" id="passwordError"></span>
                </div>

                <ul class="pwd-requirements" id="pwdRequirements">
                    <li id="req-length">  <i class="fas fa-circle-xmark"></i> At least 8 characters</li>
                    <li id="req-upper">   <i class="fas fa-circle-xmark"></i> One uppercase letter</li>
                    <li id="req-lower">   <i class="fas fa-circle-xmark"></i> One lowercase letter</li>
                    <li id="req-number">  <i class="fas fa-circle-xmark"></i> One number</li>
                </ul>

                <div class="auth-form-group">
                    <label for="confirm_password" class="auth-label">Confirm Password <span class="required">*</span></label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock auth-input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="auth-input"
                            placeholder="Repeat your password" required autocomplete="new-password">
                        <button type="button" class="auth-toggle-password" id="toggleConfirm">
                            <i class="fas fa-eye" id="toggleIconConfirm"></i>
                        </button>
                    </div>
                    <span class="auth-field-error" id="confirmPasswordError"></span>
                </div>

                <button type="submit" class="auth-btn-submit" id="resetBtn">
                    <span class="btn-text">
                        <i class="fas fa-save"></i> Save New Password
                    </span>
                    <span class="btn-loading" style="display:none;">
                        <i class="fas fa-spinner fa-spin"></i> Saving…
                    </span>
                </button>
            </form>

            <?php endif; ?>

            <p class="auth-footer-link">
                Remember your password?
                <a href="login.php">Back to Sign In <i class="fas fa-arrow-right"></i></a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>assets/js/form-validation.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ── Forgot form ──
        const forgotForm = document.getElementById('forgotForm');
        if (forgotForm) {
            forgotForm.addEventListener('submit', e => {
                clearError('emailError');
                const email = document.getElementById('email').value.trim();
                if (!email) {
                    showFieldError('emailError', 'Email is required.'); e.preventDefault(); return;
                }
                if (!isValidEmail(email)) {
                    showFieldError('emailError', 'Please enter a valid email.'); e.preventDefault(); return;
                }
                const btn = document.getElementById('requestBtn');
                btn.querySelector('.btn-text').style.display    = 'none';
                btn.querySelector('.btn-loading').style.display = 'inline-flex';
                btn.disabled = true;
            });
        }

        // ── Reset form ──
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            // Toggles
            setupToggle('togglePassword', 'password',         'toggleIconPwd');
            setupToggle('toggleConfirm',  'confirm_password', 'toggleIconConfirm');

            function setupToggle(btnId, inputId, iconId) {
                const btn  = document.getElementById(btnId);
                if (!btn) return;
                btn.addEventListener('click', () => {
                    const inp  = document.getElementById(inputId);
                    const icon = document.getElementById(iconId);
                    const show = inp.type === 'password';
                    inp.type   = show ? 'text' : 'password';
                    icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
                });
            }

            // Strength
            document.getElementById('password').addEventListener('input', function () {
                const {score, checks} = getPasswordStrength(this.value);
                const fill  = document.getElementById('pwdStrengthFill');
                const label = document.getElementById('pwdStrengthLabel');
                const pct    = ['0%', '25%', '50%', '75%', '100%'][score];
                const colors = ['', '#e74c3c', '#f39c12', '#f1c40f', '#27ae60'];
                const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
                fill.style.width      = pct;
                fill.style.background = colors[score];
                label.textContent     = score > 0 ? labels[score] : '';
                label.style.color     = colors[score];
                updateReq('req-length', checks.length);
                updateReq('req-upper',  checks.upper);
                updateReq('req-lower',  checks.lower);
                updateReq('req-number', checks.number);
            });

            function updateReq(id, met) {
                const el   = document.getElementById(id);
                if (!el) return;
                const icon = el.querySelector('i');
                if (met) { icon.className = 'fas fa-circle-check'; el.classList.add('met'); }
                else     { icon.className = 'fas fa-circle-xmark'; el.classList.remove('met'); }
            }

            // Submit
            resetForm.addEventListener('submit', e => {
                let valid = true;
                clearError('passwordError'); clearError('confirmPasswordError');
                const pwd     = document.getElementById('password').value;
                const confirm = document.getElementById('confirm_password').value;
                const {score} = getPasswordStrength(pwd);
                if (score < 2) { showFieldError('passwordError', 'Password is too weak.'); valid = false; }
                if (pwd !== confirm) { showFieldError('confirmPasswordError', 'Passwords do not match.'); valid = false; }
                if (!valid) { e.preventDefault(); return; }
                const btn = document.getElementById('resetBtn');
                btn.querySelector('.btn-text').style.display    = 'none';
                btn.querySelector('.btn-loading').style.display = 'inline-flex';
                btn.disabled = true;
            });
        }
    });
    </script>
</body>
</html>
