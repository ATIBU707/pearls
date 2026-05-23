<?php
/**
 * NotificationService
 * Online Hostel Management System
 *
 * Central place to push in-app notifications to students/users
 * whenever an admin performs an action.
 */

class NotificationService
{
    /**
     * Insert a notification record for a user.
     *
     * @param int    $user_id   Recipient user ID
     * @param string $title     Short title (max ~255 chars)
     * @param string $message   Full notification body
     * @param string $type      Enum: booking|payment|maintenance|general|alert
     * @param int|null $booking_id  Optional related booking ID
     */
    public static function send(
        int $user_id,
        string $title,
        string $message,
        string $type = 'general',
        ?int $booking_id = null
    ): bool {
        $validTypes = ['booking', 'payment', 'maintenance', 'general', 'alert'];
        if (!in_array($type, $validTypes)) $type = 'general';

        $sql = "INSERT INTO notifications
                    (user_id, title, message, type, related_booking_id, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())";

        return (bool) executeQuery($sql, [
            $user_id,
            $title,
            $message,
            $type,
            $booking_id,
        ]);
    }

    // ── Booking ──────────────────────────────────────────────────────────

    /**
     * Notify student when their booking status changes.
     */
    public static function bookingStatusChanged(
        int $user_id,
        string $booking_code,
        string $room_number,
        string $new_status,
        ?int $booking_id = null
    ): bool {
        $labels = [
            'confirmed'   => '✅ Booking Confirmed',
            'cancelled'   => '❌ Booking Cancelled',
            'checked_in'  => '🏨 Checked In',
            'checked_out' => '👋 Checked Out',
            'pending'     => '⏳ Booking Pending Review',
        ];

        $messages = [
            'confirmed'   => "Your booking ({$booking_code}) for Room {$room_number} has been confirmed. Please proceed with payment if not done.",
            'cancelled'   => "Your booking ({$booking_code}) for Room {$room_number} has been cancelled by the admin.",
            'checked_in'  => "You have been checked in to Room {$room_number}. Welcome! Booking code: {$booking_code}.",
            'checked_out' => "You have been checked out of Room {$room_number}. Booking {$booking_code} is now closed.",
            'pending'     => "Your booking ({$booking_code}) for Room {$room_number} is under review.",
        ];

        $title   = $labels[$new_status]   ?? "Booking Update — {$booking_code}";
        $message = $messages[$new_status] ?? "Your booking {$booking_code} status has been updated to: " . ucfirst(str_replace('_', ' ', $new_status)) . '.';

        return self::send($user_id, $title, $message, 'booking', $booking_id);
    }

    // ── Maintenance ───────────────────────────────────────────────────────

    /**
     * Notify student when admin updates a maintenance request status.
     */
    public static function maintenanceStatusChanged(
        int $user_id,
        string $request_title,
        string $room_number,
        string $new_status
    ): bool {
        $labels = [
            'in_progress' => '🔧 Maintenance In Progress',
            'resolved'    => '✅ Maintenance Resolved',
            'closed'      => '📁 Maintenance Request Closed',
            'open'        => 'ℹ️ Maintenance Request Reopened',
        ];

        $messages = [
            'in_progress' => "Your maintenance request \"{$request_title}\" for Room {$room_number} is now being worked on.",
            'resolved'    => "Your maintenance request \"{$request_title}\" for Room {$room_number} has been resolved.",
            'closed'      => "Your maintenance request \"{$request_title}\" for Room {$room_number} has been closed.",
            'open'        => "Your maintenance request \"{$request_title}\" for Room {$room_number} has been reopened.",
        ];

        $title   = $labels[$new_status]   ?? 'Maintenance Update';
        $message = $messages[$new_status] ?? "Your maintenance request \"{$request_title}\" status changed to: " . ucfirst(str_replace('_', ' ', $new_status)) . '.';

        return self::send($user_id, $title, $message, 'maintenance');
    }

    // ── Room Added ────────────────────────────────────────────────────────

    /**
     * Notify all active students when a new room is added.
     */
    public static function newRoomAvailable(
        string $room_number,
        string $type_name,
        string $price
    ): void {
        $sql     = "SELECT user_id FROM users WHERE role = 'student' AND is_active = 1";
        $students = getRows($sql);

        $title   = "🏠 New Room Available — Room {$room_number}";
        $message = "A new {$type_name} room ({$room_number}) is now available for booking at {$price} per semester. Check it out!";

        foreach ($students as $s) {
            self::send((int)$s['user_id'], $title, $message, 'general');
        }
    }
}
?>
