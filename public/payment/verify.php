<?php
/**
 * Payment Verify — Legacy redirect handler
 * PesaPal now redirects to status.php directly.
 * This file handles any old links that still point here.
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';

requireLogin();

$payment_id  = (int)($_GET['payment_id']  ?? 0);
$tracking_id = trim($_GET['ref']           ?? $_GET['OrderTrackingId'] ?? '');

// If we have a PesaPal OrderTrackingId redirect (card payments)
if (isset($_GET['OrderTrackingId'])) {
    $tracking_id = $_GET['OrderTrackingId'];
    // Find payment by tracking id
    $pay = getRow("SELECT payment_id FROM payments WHERE transaction_reference = ?", [$tracking_id]);
    if ($pay) $payment_id = $pay['payment_id'];
}

if ($payment_id) {
    header("Location: status.php?payment_id={$payment_id}&tracking_id=" . urlencode($tracking_id));
} else {
    header("Location: ../payments.php");
}
exit;
