<?php
/**
 * Student Registration Page
 * Online Hostel Management System - Pearls of Wisdom Hostel
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/AuthService.php';

redirectIfLoggedIn();

$page_title = 'Register';
$error   = '';
$success = '';

// Preserve form values on error
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $formData = [
        'first_name'            => trim($_POST['first_name']            ?? ''),
        'last_name'             => trim($_POST['last_name']             ?? ''),
        'email'                 => trim($_POST['email']                 ?? ''),
        'phone_number'          => trim($_POST['phone_number']          ?? ''),
        'student_id'            => trim($_POST['student_id']            ?? ''),
        'identification_type'   => trim($_POST['identification_type']   ?? 'student_id'),
        'identification_number' => trim($_POST['identification_number'] ?? ''),
        'password'              => $_POST['password']                   ?? '',
        'confirm_password'      => $_POST['confirm_password']           ?? '',
    ];

    $authService = new AuthService();
    $result = $authService->register($formData);

    if ($result['success']) {
        redirectWithMessage(APP_URL . 'auth/login.php', $result['message'], 'success');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Register for Pearls of Wisdom Hostel Management System">
    <title>Register – Pearls of Wisdom Hostel</title>

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

    <!-- Top nav -->
    <nav class="auth-topnav">
        <a href="<?php echo APP_URL; ?>" class="auth-topnav-home">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
        <a href="login.php" class="auth-topnav-action">
            Already registered? Sign in <i class="fas fa-sign-in-alt"></i>
        </a>
    </nav>

    <div class="auth-wrapper auth-wrapper--wide">

        <!-- Brand -->
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
                <h1 class="auth-heading">Create your account</h1>
                <p class="auth-subheading">Join hundreds of students at Pearls of Wisdom Hostel</p>
            </div>

            <!-- Step indicators -->
            <div class="auth-steps">
                <div class="auth-step active" id="step-indicator-1">
                    <div class="step-dot">1</div>
                    <span>Personal Info</span>
                </div>
                <div class="auth-step-line"></div>
                <div class="auth-step" id="step-indicator-2">
                    <div class="step-dot">2</div>
                    <span>Contact</span>
                </div>
                <div class="auth-step-line"></div>
                <div class="auth-step" id="step-indicator-3">
                    <div class="step-dot">3</div>
                    <span>Security</span>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="auth-alert auth-alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="register.php" novalidate>
                <?php echo getCSRFTokenInput(); ?>

                <!-- ======= STEP 1: Personal Info ======= -->
                <div class="form-step" id="step-1">
                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="first_name" class="auth-label">First Name <span class="required">*</span></label>
                            <div class="auth-input-wrap">
                                <i class="fas fa-user auth-input-icon"></i>
                                <input type="text" id="first_name" name="first_name" class="auth-input"
                                    placeholder="John"
                                    value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>"
                                    required>
                            </div>
                            <span class="auth-field-error" id="firstNameError"></span>
                        </div>

                        <div class="auth-form-group">
                            <label for="last_name" class="auth-label">Last Name <span class="required">*</span></label>
                            <div class="auth-input-wrap">
                                <i class="fas fa-user auth-input-icon"></i>
                                <input type="text" id="last_name" name="last_name" class="auth-input"
                                    placeholder="Doe"
                                    value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>"
                                    required>
                            </div>
                            <span class="auth-field-error" id="lastNameError"></span>
                        </div>
                    </div>

                    <div class="auth-form-group">
                        <label for="student_id" class="auth-label">Student ID</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-id-card auth-input-icon"></i>
                            <input type="text" id="student_id" name="student_id" class="auth-input"
                                placeholder="e.g. BCS/2204/001"
                                value="<?php echo htmlspecialchars($formData['student_id'] ?? ''); ?>">
                        </div>
                        <span class="auth-field-error" id="studentIdError"></span>
                    </div>

                    <div class="auth-form-row">
                        <div class="auth-form-group">
                            <label for="identification_type" class="auth-label">ID Type</label>
                            <div class="auth-input-wrap">
                                <i class="fas fa-address-card auth-input-icon"></i>
                                <select id="identification_type" name="identification_type" class="auth-input auth-select">
                                    <option value="student_id"  <?php echo ($formData['identification_type'] ?? 'student_id') === 'student_id'  ? 'selected' : ''; ?>>Student ID</option>
                                    <option value="national_id" <?php echo ($formData['identification_type'] ?? '') === 'national_id' ? 'selected' : ''; ?>>National ID</option>
                                    <option value="passport"    <?php echo ($formData['identification_type'] ?? '') === 'passport'    ? 'selected' : ''; ?>>Passport</option>
                                </select>
                            </div>
                        </div>

                        <div class="auth-form-group">
                            <label for="identification_number" class="auth-label">ID Number</label>
                            <div class="auth-input-wrap">
                                <i class="fas fa-hashtag auth-input-icon"></i>
                                <input type="text" id="identification_number" name="identification_number" class="auth-input"
                                    placeholder="Enter ID number"
                                    value="<?php echo htmlspecialchars($formData['identification_number'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <button type="button" class="auth-btn-submit" id="nextStep1Btn">
                        Next: Contact Info <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>

                <!-- ======= STEP 2: Contact ======= -->
                <div class="form-step" id="step-2" style="display:none;">
                    <div class="auth-form-group">
                        <label for="email" class="auth-label">Email Address <span class="required">*</span></label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-envelope auth-input-icon"></i>
                            <input type="email" id="email" name="email" class="auth-input"
                                placeholder="your@email.com"
                                value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                required autocomplete="email">
                        </div>
                        <span class="auth-field-error" id="emailError"></span>
                    </div>

                    <div class="auth-form-group">
                        <label for="phone_number" class="auth-label">Phone Number</label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-phone auth-input-icon"></i>
                            <input type="tel" id="phone_number" name="phone_number" class="auth-input"
                                placeholder="e.g. 0712345678"
                                value="<?php echo htmlspecialchars($formData['phone_number'] ?? ''); ?>">
                        </div>
                        <span class="auth-field-error" id="phoneError"></span>
                        <span class="auth-field-hint">Uganda format: 07XXXXXXXX or +256XXXXXXXXX</span>
                    </div>

                    <div class="auth-btn-row">
                        <button type="button" class="auth-btn-back" id="backStep1Btn">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="button" class="auth-btn-submit" id="nextStep2Btn">
                            Next: Security <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </div>

                <!-- ======= STEP 3: Security ======= -->
                <div class="form-step" id="step-3" style="display:none;">
                    <div class="auth-form-group">
                        <label for="password" class="auth-label">Password <span class="required">*</span></label>
                        <div class="auth-input-wrap">
                            <i class="fas fa-lock auth-input-icon"></i>
                            <input type="password" id="password" name="password" class="auth-input"
                                placeholder="Create a strong password"
                                required autocomplete="new-password">
                            <button type="button" class="auth-toggle-password" id="togglePassword" aria-label="Show password">
                                <i class="fas fa-eye" id="toggleIconPwd"></i>
                            </button>
                        </div>
                        <!-- Password strength bar -->
                        <div class="pwd-strength-bar">
                            <div class="pwd-strength-fill" id="pwdStrengthFill"></div>
                        </div>
                        <span class="pwd-strength-label" id="pwdStrengthLabel"></span>
                        <span class="auth-field-error" id="passwordError"></span>
                    </div>

                    <!-- Requirements list -->
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
                                placeholder="Repeat your password"
                                required autocomplete="new-password">
                            <button type="button" class="auth-toggle-password" id="toggleConfirm" aria-label="Show confirm password">
                                <i class="fas fa-eye" id="toggleIconConfirm"></i>
                            </button>
                        </div>
                        <span class="auth-field-error" id="confirmPasswordError"></span>
                    </div>

                    <!-- Terms -->
                    <div class="auth-form-group auth-terms">
                        <label class="auth-checkbox-label">
                            <input type="checkbox" name="agree_terms" id="agreeTerms" required>
                            <span class="auth-checkbox-custom"></span>
                            I agree to the <a href="#" class="auth-link">Terms &amp; Conditions</a>
                            and <a href="#" class="auth-link">Privacy Policy</a>
                        </label>
                        <span class="auth-field-error" id="termsError"></span>
                    </div>

                    <div class="auth-btn-row">
                        <button type="button" class="auth-btn-back" id="backStep2Btn">
                            <i class="fas fa-arrow-left me-2"></i> Back
                        </button>
                        <button type="submit" class="auth-btn-submit" id="registerBtn">
                            <span class="btn-text">
                                <i class="fas fa-user-plus"></i> Create Account
                            </span>
                            <span class="btn-loading" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i> Creating…
                            </span>
                        </button>
                    </div>
                </div>
            </form>

            <p class="auth-footer-link">
                Already have an account?
                <a href="login.php">Sign in <i class="fas fa-arrow-right"></i></a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo APP_URL; ?>assets/js/form-validation.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {

        // ── Step navigation ──
        let currentStep = 1;

        function showStep(step) {
            document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
            document.getElementById('step-' + step).style.display = 'block';
            document.querySelectorAll('.auth-step').forEach((el, i) => {
                el.classList.toggle('active', i + 1 <= step);
                el.classList.toggle('done',   i + 1 < step);
            });
            currentStep = step;
        }

        // Step 1 → 2
        document.getElementById('nextStep1Btn').addEventListener('click', () => {
            let valid = true;
            clearError('firstNameError'); clearError('lastNameError');

            if (!document.getElementById('first_name').value.trim()) {
                showFieldError('firstNameError', 'First name is required.'); valid = false;
            }
            if (!document.getElementById('last_name').value.trim()) {
                showFieldError('lastNameError', 'Last name is required.');  valid = false;
            }
            if (valid) showStep(2);
        });

        document.getElementById('backStep1Btn').addEventListener('click', () => showStep(1));

        // Step 2 → 3
        document.getElementById('nextStep2Btn').addEventListener('click', () => {
            let valid = true;
            clearError('emailError'); clearError('phoneError');

            const email = document.getElementById('email').value.trim();
            if (!email) {
                showFieldError('emailError', 'Email is required.'); valid = false;
            } else if (!isValidEmail(email)) {
                showFieldError('emailError', 'Please enter a valid email.'); valid = false;
            }

            const phone = document.getElementById('phone_number').value.trim();
            if (phone && !isValidUgandaPhone(phone)) {
                showFieldError('phoneError', 'Please enter a valid Uganda phone number.'); valid = false;
            }

            if (valid) showStep(3);
        });

        document.getElementById('backStep2Btn').addEventListener('click', () => showStep(2));

        // ── Password toggle ──
        setupToggle('togglePassword', 'password', 'toggleIconPwd');
        setupToggle('toggleConfirm',  'confirm_password', 'toggleIconConfirm');

        function setupToggle(btnId, inputId, iconId) {
            document.getElementById(btnId).addEventListener('click', () => {
                const inp  = document.getElementById(inputId);
                const icon = document.getElementById(iconId);
                const show = inp.type === 'password';
                inp.type   = show ? 'text' : 'password';
                icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            });
        }

        // ── Password strength ──
        document.getElementById('password').addEventListener('input', function () {
            const pwd = this.value;
            const {score, checks} = getPasswordStrength(pwd);

            const fill  = document.getElementById('pwdStrengthFill');
            const label = document.getElementById('pwdStrengthLabel');

            const pct    = ['0%', '25%', '50%', '75%', '100%'][score];
            const colors = ['', '#e74c3c', '#f39c12', '#f1c40f', '#27ae60'];
            const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];

            fill.style.width      = pct;
            fill.style.background = colors[score];
            label.textContent     = score > 0 ? labels[score] : '';
            label.style.color     = colors[score];

            // Requirements
            updateReq('req-length', checks.length);
            updateReq('req-upper',  checks.upper);
            updateReq('req-lower',  checks.lower);
            updateReq('req-number', checks.number);
        });

        function updateReq(id, met) {
            const el   = document.getElementById(id);
            const icon = el.querySelector('i');
            if (met) {
                icon.className = 'fas fa-circle-check';
                el.classList.add('met');
            } else {
                icon.className = 'fas fa-circle-xmark';
                el.classList.remove('met');
            }
        }

        // ── Final submission ──
        document.getElementById('registerForm').addEventListener('submit', (e) => {
            let valid = true;
            clearError('passwordError');
            clearError('confirmPasswordError');
            clearError('termsError');

            const pwd     = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const terms   = document.getElementById('agreeTerms').checked;

            const {score} = getPasswordStrength(pwd);
            if (score < 2) {
                showFieldError('passwordError', 'Password is too weak.'); valid = false;
            }
            if (pwd !== confirm) {
                showFieldError('confirmPasswordError', 'Passwords do not match.'); valid = false;
            }
            if (!terms) {
                showFieldError('termsError', 'You must agree to the terms.'); valid = false;
            }

            if (!valid) { e.preventDefault(); return; }

            const btn = document.getElementById('registerBtn');
            btn.querySelector('.btn-text').style.display    = 'none';
            btn.querySelector('.btn-loading').style.display = 'inline-flex';
            btn.disabled = true;
        });
    });
    </script>
</body>
</html>
