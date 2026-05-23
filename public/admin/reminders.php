<?php
/**
 * Admin — Email Reminders
 * Send payment reminders, custom messages, or bulk emails to students.
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/middleware/AuthMiddleware.php';
require_once APP_PATH . '/services/EmailService.php';
require_once APP_PATH . '/services/NotificationService.php';

requireAdminAuth();

$success = '';
$error   = '';
$sent    = 0;

// ── Handle form submissions ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();
    $action = $_POST['action'] ?? '';

    $emailService = new EmailService();

    // ── 1. Send payment reminders to all unpaid bookings ──────────────────
    if ($action === 'bulk_payment_reminder') {
        $unpaid = getRows(
            "SELECT b.booking_id, b.booking_code, b.semester,
                    r.room_number, r.price_per_semester,
                    u.user_id, u.email, u.first_name, u.last_name,
                    COALESCE(SUM(p.amount),0) AS paid_amount
             FROM bookings b
             JOIN rooms r  ON b.room_id  = r.room_id
             JOIN users u  ON b.user_id  = u.user_id
             LEFT JOIN payments p ON p.booking_id = b.booking_id AND p.status = 'completed'
             WHERE b.status IN ('pending','confirmed')
             GROUP BY b.booking_id
             HAVING (r.price_per_semester - paid_amount) > 0
             ORDER BY b.created_at ASC"
        );

        foreach ($unpaid as $row) {
            $balance = $row['price_per_semester'] - $row['paid_amount'];
            $ok = $emailService->sendPaymentReminder(
                $row['email'],
                $row['first_name'],
                $row['booking_code'],
                $row['room_number'],
                formatCurrency($balance),
                (int)$row['booking_id']
            );
            if ($ok) {
                // Also push in-app notification
                NotificationService::send(
                    (int)$row['user_id'],
                    '⏰ Payment Reminder',
                    "Reminder: Your booking {$row['booking_code']} for Room {$row['room_number']} has an outstanding balance of " . formatCurrency($balance) . '. Please pay to secure your room.',
                    'payment',
                    (int)$row['booking_id']
                );
                $sent++;
            }
        }
        $success = "Payment reminders sent to {$sent} student(s).";
    }

    // ── 2. Send reminder to a single booking ──────────────────────────────
    if ($action === 'single_payment_reminder') {
        $bid = (int)($_POST['booking_id'] ?? 0);
        if ($bid) {
            $row = getRow(
                "SELECT b.booking_id, b.booking_code, b.semester,
                        r.room_number, r.price_per_semester,
                        u.user_id, u.email, u.first_name,
                        COALESCE((SELECT SUM(amount) FROM payments WHERE booking_id = b.booking_id AND status='completed'),0) AS paid_amount
                 FROM bookings b
                 JOIN rooms r ON b.room_id = r.room_id
                 JOIN users u ON b.user_id = u.user_id
                 WHERE b.booking_id = ?",
                [$bid]
            );
            if ($row) {
                $balance = $row['price_per_semester'] - $row['paid_amount'];
                $ok = $emailService->sendPaymentReminder(
                    $row['email'],
                    $row['first_name'],
                    $row['booking_code'],
                    $row['room_number'],
                    formatCurrency($balance),
                    $bid
                );
                if ($ok) {
                    NotificationService::send(
                        (int)$row['user_id'],
                        '⏰ Payment Reminder',
                        "Reminder: Your booking {$row['booking_code']} has an outstanding balance of " . formatCurrency($balance) . '.',
                        'payment',
                        $bid
                    );
                    $success = "Payment reminder sent to {$row['first_name']} ({$row['email']}).";
                } else {
                    $error = 'Failed to send email. Check email configuration in config.php.';
                }
            } else {
                $error = 'Booking not found.';
            }
        }
    }

    // ── 3. Send custom email to one or all students ───────────────────────
    if ($action === 'custom_email') {
        $recipient = $_POST['recipient'] ?? 'all'; // 'all' or a user_id
        $subject   = trim($_POST['subject'] ?? '');
        $message   = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $error = 'Subject and message are required.';
        } else {
            if ($recipient === 'all') {
                $students = getRows(
                    "SELECT user_id, email, first_name FROM users WHERE role = 'student' AND is_active = 1"
                );
            } else {
                $students = getRows(
                    "SELECT user_id, email, first_name FROM users WHERE user_id = ? AND role = 'student'",
                    [(int)$recipient]
                );
            }

            foreach ($students as $s) {
                $html = (new EmailService())->buildCustomHtml($s['first_name'], $subject, nl2br(htmlspecialchars($message)));
                $ok   = $emailService->send($s['email'], $s['first_name'], $subject, $html);
                if ($ok) {
                    NotificationService::send(
                        (int)$s['user_id'],
                        $subject,
                        $message,
                        'general'
                    );
                    $sent++;
                }
            }
            $success = "Custom email sent to {$sent} student(s).";
        }
    }
}

// ── Data for the page ─────────────────────────────────────────────────────

// Unpaid bookings list
$unpaidBookings = getRows(
    "SELECT b.booking_id, b.booking_code, b.semester, b.status,
            r.room_number, r.price_per_semester,
            u.user_id, u.email, u.first_name, u.last_name,
            COALESCE((SELECT SUM(amount) FROM payments WHERE booking_id = b.booking_id AND status='completed'),0) AS paid_amount
     FROM bookings b
     JOIN rooms r ON b.room_id = r.room_id
     JOIN users u ON b.user_id = u.user_id
     WHERE b.status IN ('pending','confirmed')
     HAVING (r.price_per_semester - paid_amount) > 0
     ORDER BY b.created_at ASC"
);

// All active students for custom email dropdown
$allStudents = getRows(
    "SELECT user_id, first_name, last_name, email FROM users WHERE role = 'student' AND is_active = 1 ORDER BY first_name"
);

$page_title = 'Email Reminders';
include BASE_PATH . '/views/layouts/header.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Email Reminders</h1>
            <p class="text-muted mb-0">Send payment reminders and custom messages to students</p>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ── LEFT: Unpaid bookings ── -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2 text-warning"></i>
                        Unpaid Bookings
                        <span class="badge bg-warning text-dark ms-2"><?php echo count($unpaidBookings); ?></span>
                    </h5>
                    <?php if (!empty($unpaidBookings)): ?>
                    <form method="POST">
                        <?php echo getCSRFTokenInput(); ?>
                        <input type="hidden" name="action" value="bulk_payment_reminder">
                        <button type="submit"
                                class="btn btn-warning btn-sm fw-bold"
                                onclick="return confirm('Send payment reminder to all <?php echo count($unpaidBookings); ?> unpaid students?')">
                            <i class="fas fa-paper-plane me-1"></i>
                            Send All Reminders (<?php echo count($unpaidBookings); ?>)
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($unpaidBookings)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-check-double fa-2x mb-2 d-block text-success opacity-50"></i>
                        All bookings are paid up!
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">Student</th>
                                    <th>Booking</th>
                                    <th>Room</th>
                                    <th>Balance Due</th>
                                    <th class="text-end pe-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($unpaidBookings as $b):
                                    $balance = $b['price_per_semester'] - $b['paid_amount'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($b['email']); ?></div>
                                    </td>
                                    <td>
                                        <code class="text-primary"><?php echo htmlspecialchars($b['booking_code']); ?></code>
                                        <div class="small text-muted"><?php echo htmlspecialchars($b['semester']); ?></div>
                                    </td>
                                    <td>Room <?php echo htmlspecialchars($b['room_number']); ?></td>
                                    <td>
                                        <span class="fw-bold text-danger"><?php echo formatCurrency($balance); ?></span>
                                        <?php if ($b['paid_amount'] > 0): ?>
                                        <div class="small text-success">Paid: <?php echo formatCurrency($b['paid_amount']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline">
                                            <?php echo getCSRFTokenInput(); ?>
                                            <input type="hidden" name="action"     value="single_payment_reminder">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning fw-bold"
                                                    title="Send reminder to <?php echo htmlspecialchars($b['first_name']); ?>">
                                                <i class="fas fa-envelope me-1"></i>Remind
                                            </button>
                                        </form>
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

        <!-- ── RIGHT: Custom email composer ── -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0">
                        <i class="fas fa-pen-to-square me-2 text-primary"></i>
                        Compose Email
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php echo getCSRFTokenInput(); ?>
                        <input type="hidden" name="action" value="custom_email">

                        <div class="mb-3">
                            <label class="form-label">Send To</label>
                            <select name="recipient" class="form-select">
                                <option value="all">📢 All Active Students (<?php echo count($allStudents); ?>)</option>
                                <optgroup label="Individual Student">
                                    <?php foreach ($allStudents as $s): ?>
                                    <option value="<?php echo $s['user_id']; ?>">
                                        <?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?>
                                        (<?php echo htmlspecialchars($s['email']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control"
                                   placeholder="e.g. Important Notice from Pearls of Wisdom Hostel"
                                   required maxlength="200">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="7"
                                      placeholder="Type your message here…" required></textarea>
                            <div class="form-text">Plain text. Line breaks are preserved.</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold"
                                    onclick="return confirm('Send this email?')">
                                <i class="fas fa-paper-plane me-2"></i>Send Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email config status -->
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="fas fa-cog me-2 text-muted"></i>Email Configuration</h6>
                    <?php
                    $configured = defined('MAIL_USERNAME') && MAIL_USERNAME !== 'your_email@gmail.com' && !empty(MAIL_USERNAME);
                    ?>
                    <?php if ($configured): ?>
                    <div class="d-flex align-items-center gap-2 text-success">
                        <i class="fas fa-check-circle"></i>
                        <span class="small fw-bold">Email configured</span>
                    </div>
                    <div class="small text-muted mt-1">Sending as: <strong><?php echo htmlspecialchars(MAIL_USERNAME); ?></strong></div>
                    <?php else: ?>
                    <div class="d-flex align-items-center gap-2 text-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="small fw-bold">Email not configured</span>
                    </div>
                    <div class="small text-muted mt-2">
                        Update <code>MAIL_USERNAME</code> and <code>MAIL_PASSWORD</code> in
                        <code>config/config.php</code> to enable email sending.
                    </div>
                    <a href="https://myaccount.google.com/apppasswords" target="_blank"
                       class="btn btn-sm btn-outline-primary mt-2 w-100">
                        <i class="fab fa-google me-1"></i>Get Gmail App Password
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include BASE_PATH . '/views/layouts/footer.php'; ?>
