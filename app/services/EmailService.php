<?php
/**
 * EmailService
 * Pearls of Wisdom Hostel Management System
 *
 * Sends HTML emails using PHP's mail() with SMTP via socket.
 * No Composer/PHPMailer required — works on WAMP out of the box
 * once php.ini SMTP settings are configured.
 *
 * For Gmail SMTP, set in php.ini (C:\wamp64\bin\php\phpX.X.X\php.ini):
 *   SMTP = smtp.gmail.com
 *   smtp_port = 587
 *   sendmail_from = your_email@gmail.com
 *
 * Or use the socket-based sender below which bypasses php.ini entirely.
 */

class EmailService
{
    private string $host;
    private int    $port;
    private string $username;
    private string $password;
    private string $fromEmail;
    private string $fromName;
    private string $encryption; // tls | ssl | none

    public function __construct()
    {
        $this->host       = defined('MAIL_HOST')      ? MAIL_HOST      : 'smtp.gmail.com';
        $this->port       = defined('MAIL_PORT')      ? (int)MAIL_PORT : 587;
        $this->username   = defined('MAIL_USERNAME')  ? MAIL_USERNAME  : '';
        $this->password   = defined('MAIL_PASSWORD')  ? MAIL_PASSWORD  : '';
        $this->fromEmail  = defined('MAIL_FROM')      ? MAIL_FROM      : 'noreply@pearlswisdom.com';
        $this->fromName   = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Pearls of Wisdom Hostel';
        $this->encryption = defined('MAIL_ENCRYPTION')? MAIL_ENCRYPTION: 'tls';
    }

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Send a plain-text + HTML email.
     *
     * @param string $toEmail   Recipient email
     * @param string $toName    Recipient display name
     * @param string $subject   Email subject
     * @param string $htmlBody  HTML content
     * @param string $textBody  Plain-text fallback (auto-generated if empty)
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody = ''
    ): bool {
        if (empty($this->username) || $this->username === 'your_email@gmail.com') {
            // Email not configured — log and skip silently
            logMessage("EmailService: not configured, skipping email to {$toEmail}", 'activity');
            return false;
        }

        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            logMessage("EmailService: invalid recipient email: {$toEmail}", 'error');
            return false;
        }

        if (empty($textBody)) {
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>'], "\n", $htmlBody));
        }

        try {
            return $this->sendViaSMTP($toEmail, $toName, $subject, $htmlBody, $textBody);
        } catch (\Throwable $e) {
            logMessage("EmailService error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Build a custom HTML email body (used by admin compose).
     */
    public function buildCustomHtml(string $firstName, string $subject, string $bodyHtml): string
    {
        return $this->wrap($firstName, $subject, "<p>Hi <strong>{$firstName}</strong>,</p><div>{$bodyHtml}</div>");
    }

    // ── Pre-built email templates ─────────────────────────────────────────

    /**
     * Welcome email after registration.
     */
    public function sendWelcome(string $email, string $firstName): bool
    {
        $subject = 'Welcome to Pearls of Wisdom Hostel!';
        $html    = $this->wrap(
            $firstName,
            'Welcome to Pearls of Wisdom Hostel! 🎉',
            "<p>Hi <strong>{$firstName}</strong>,</p>
             <p>Your account has been created successfully. You can now log in and browse available rooms.</p>
             <p>To get started, visit the portal and book your room for the upcoming semester.</p>",
            APP_URL . 'rooms.php',
            'Browse Available Rooms'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    /**
     * Booking confirmation email.
     */
    public function sendBookingConfirmation(
        string $email,
        string $firstName,
        string $bookingCode,
        string $roomNumber,
        string $semester,
        string $amountDue,
        int    $bookingId
    ): bool {
        $subject = "Booking Confirmed — {$bookingCode}";
        $html    = $this->wrap(
            $firstName,
            '✅ Booking Received',
            "<p>Hi <strong>{$firstName}</strong>,</p>
             <p>Your booking has been received. Here are the details:</p>
             <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Booking Code</td><td style='padding:8px 0;font-weight:700;color:#4f46e5;'>{$bookingCode}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Room</td><td style='padding:8px 0;font-weight:600;'>Room {$roomNumber}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Semester</td><td style='padding:8px 0;'>{$semester}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Amount Due</td><td style='padding:8px 0;font-weight:700;color:#d97706;'>{$amountDue}</td></tr>
             </table>
             <p style='color:#dc2626;font-size:0.875rem;'>⚠️ Please complete your payment within 24 hours to secure your room.</p>",
            APP_URL . "payment/initiate.php?booking_id={$bookingId}",
            'Pay Now'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    /**
     * Payment confirmation email with receipt link.
     */
    public function sendPaymentConfirmation(
        string $email,
        string $firstName,
        string $bookingCode,
        string $roomNumber,
        string $amountPaid,
        string $transactionRef,
        int    $paymentId
    ): bool {
        $subject = "Payment Confirmed — {$bookingCode}";
        $html    = $this->wrap(
            $firstName,
            '💳 Payment Received',
            "<p>Hi <strong>{$firstName}</strong>,</p>
             <p>Your payment has been received and confirmed. Your room is now secured.</p>
             <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Booking Code</td><td style='padding:8px 0;font-weight:700;color:#4f46e5;'>{$bookingCode}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Room</td><td style='padding:8px 0;font-weight:600;'>Room {$roomNumber}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Amount Paid</td><td style='padding:8px 0;font-weight:700;color:#16a34a;'>{$amountPaid}</td></tr>
               <tr><td style='padding:8px 0;color:#64748b;font-size:0.875rem;'>Transaction Ref</td><td style='padding:8px 0;'>{$transactionRef}</td></tr>
             </table>
             <p>You can download your official e-receipt using the button below.</p>",
            APP_URL . "payment/receipt.php?id={$paymentId}",
            'Download Receipt'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    /**
     * Booking status change email (confirmed, cancelled, checked_in, etc.)
     */
    public function sendBookingStatusUpdate(
        string $email,
        string $firstName,
        string $bookingCode,
        string $roomNumber,
        string $newStatus,
        int    $bookingId
    ): bool {
        $labels = [
            'confirmed'   => ['✅ Booking Confirmed',    '#16a34a'],
            'cancelled'   => ['❌ Booking Cancelled',    '#dc2626'],
            'checked_in'  => ['🏨 You\'re Checked In!', '#4f46e5'],
            'checked_out' => ['👋 Checked Out',          '#64748b'],
        ];
        [$label, $color] = $labels[$newStatus] ?? ["Booking Update — " . ucfirst($newStatus), '#4f46e5'];

        $messages = [
            'confirmed'   => "Your booking for Room {$roomNumber} has been confirmed by the admin.",
            'cancelled'   => "Your booking ({$bookingCode}) for Room {$roomNumber} has been cancelled. Please contact us if this is unexpected.",
            'checked_in'  => "Welcome! You have been checked in to Room {$roomNumber}. Enjoy your stay.",
            'checked_out' => "You have been checked out of Room {$roomNumber}. Thank you for staying with us.",
        ];
        $body = $messages[$newStatus] ?? "Your booking {$bookingCode} status has been updated to: " . ucfirst(str_replace('_', ' ', $newStatus)) . '.';

        $subject = "{$label} — {$bookingCode}";
        $html    = $this->wrap(
            $firstName,
            "<span style='color:{$color};'>{$label}</span>",
            "<p>Hi <strong>{$firstName}</strong>,</p><p>{$body}</p>
             <p style='color:#64748b;font-size:0.875rem;'>Booking Code: <strong style='color:#4f46e5;'>{$bookingCode}</strong></p>",
            APP_URL . "booking-confirmation.php?id={$bookingId}",
            'View Booking'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    /**
     * Maintenance request status update email.
     */
    public function sendMaintenanceUpdate(
        string $email,
        string $firstName,
        string $requestTitle,
        string $roomNumber,
        string $newStatus
    ): bool {
        $labels = [
            'in_progress' => ['🔧 Maintenance In Progress', '#d97706'],
            'resolved'    => ['✅ Maintenance Resolved',     '#16a34a'],
            'closed'      => ['📁 Request Closed',           '#64748b'],
        ];
        [$label, $color] = $labels[$newStatus] ?? ['Maintenance Update', '#4f46e5'];

        $messages = [
            'in_progress' => "Our team is now working on your request \"{$requestTitle}\" for Room {$roomNumber}.",
            'resolved'    => "Your maintenance request \"{$requestTitle}\" for Room {$roomNumber} has been resolved.",
            'closed'      => "Your maintenance request \"{$requestTitle}\" for Room {$roomNumber} has been closed.",
        ];
        $body = $messages[$newStatus] ?? "Your maintenance request status has been updated to: " . ucfirst(str_replace('_', ' ', $newStatus)) . '.';

        $subject = "{$label} — {$requestTitle}";
        $html    = $this->wrap(
            $firstName,
            "<span style='color:{$color};'>{$label}</span>",
            "<p>Hi <strong>{$firstName}</strong>,</p><p>{$body}</p>",
            APP_URL . 'maintenance.php',
            'View My Requests'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    /**
     * Payment reminder email (unpaid bookings).
     */
    public function sendPaymentReminder(
        string $email,
        string $firstName,
        string $bookingCode,
        string $roomNumber,
        string $amountDue,
        int    $bookingId
    ): bool {
        $subject = "⏰ Payment Reminder — {$bookingCode}";
        $html    = $this->wrap(
            $firstName,
            '⏰ Payment Reminder',
            "<p>Hi <strong>{$firstName}</strong>,</p>
             <p>This is a friendly reminder that your booking <strong style='color:#4f46e5;'>{$bookingCode}</strong> for Room {$roomNumber} is still awaiting payment.</p>
             <p>Amount due: <strong style='color:#d97706;font-size:1.1rem;'>{$amountDue}</strong></p>
             <p style='color:#dc2626;font-size:0.875rem;'>Please complete your payment to avoid losing your room reservation.</p>",
            APP_URL . "payment/initiate.php?booking_id={$bookingId}",
            'Pay Now'
        );
        return $this->send($email, $firstName, $subject, $html);
    }

    // ── HTML Template wrapper ─────────────────────────────────────────────

    private function wrap(
        string $recipientName,
        string $heading,
        string $bodyHtml,
        string $ctaUrl   = '',
        string $ctaLabel = ''
    ): string {
        $cta = '';
        if ($ctaUrl && $ctaLabel) {
            $cta = "<div style='text-align:center;margin:28px 0;'>
                      <a href='{$ctaUrl}' style='display:inline-block;padding:14px 36px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:white;text-decoration:none;border-radius:999px;font-weight:700;font-size:0.95rem;box-shadow:0 4px 16px rgba(79,70,229,0.35);'>
                        {$ctaLabel}
                      </a>
                    </div>";
        }

        $year    = date('Y');
        $appName = defined('APP_NAME') ? APP_NAME : 'Pearls of Wisdom Hostel';
        $appUrl  = defined('APP_URL')  ? APP_URL  : '#';

        return "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f5f6fa;font-family:Inter,Segoe UI,sans-serif;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f5f6fa;padding:40px 16px;'>
    <tr><td align='center'>
      <table width='100%' style='max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);'>

        <!-- Header -->
        <tr>
          <td style='background:linear-gradient(135deg,#1e1b4b,#4f46e5);padding:28px 36px;text-align:center;'>
            <div style='font-size:1.2rem;font-weight:800;color:white;letter-spacing:-0.01em;'>{$appName}</div>
            <div style='font-size:0.75rem;color:rgba(255,255,255,0.6);margin-top:4px;'>Kasindikwa Village, Fort Portal, Uganda</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style='padding:32px 36px;'>
            <h2 style='font-size:1.3rem;font-weight:800;color:#1e1b4b;margin:0 0 20px;'>{$heading}</h2>
            <div style='color:#374151;font-size:0.9rem;line-height:1.7;'>
              {$bodyHtml}
            </div>
            {$cta}
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style='background:#f8f9ff;padding:20px 36px;border-top:1px solid #e8edf5;text-align:center;'>
            <p style='font-size:0.75rem;color:#94a3b8;margin:0 0 4px;'>
              &copy; {$year} {$appName}. All rights reserved.
            </p>
            <p style='font-size:0.72rem;color:#b0bec5;margin:0;'>
              <a href='{$appUrl}' style='color:#4f46e5;text-decoration:none;'>Visit Portal</a>
              &nbsp;&bull;&nbsp; +256 765 536 881
              &nbsp;&bull;&nbsp; admin@pearlswisdom.com
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>";
    }

    // ── SMTP socket sender ────────────────────────────────────────────────

    private function sendViaSMTP(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $textBody
    ): bool {
        // Use PHPMailer for reliable Gmail SMTP
        $libPath = APP_PATH . '/lib/phpmailer/';
        require_once $libPath . 'Exception.php';
        require_once $libPath . 'PHPMailer.php';
        require_once $libPath . 'SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = $this->host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->username;
            $mail->Password   = $this->password;
            $mail->SMTPSecure = $this->encryption === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $this->port;
            $mail->CharSet    = 'UTF-8';
            $mail->Timeout    = 15;

            $mail->setFrom($this->fromEmail, $this->fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($this->fromEmail, $this->fromName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            logMessage("EmailService: sent '{$subject}' to {$toEmail}", 'activity');
            return true;

        } catch (\Exception $e) {
            logMessage("EmailService error: " . $mail->ErrorInfo, 'error');
            return false;
        }
    }
}
