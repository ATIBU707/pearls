<?php
/**
 * Logout Handler
 * Online Hostel Management System
 */

require_once '../../config/config.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/services/AuthService.php';

$authService = new AuthService();
$authService->logout();

redirectWithMessage(APP_URL . 'auth/login.php', 'You have been logged out successfully.', 'success');
?>
