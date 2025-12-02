<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Require login
if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = get_current_user_id();

// Rate limiting
if (!check_rate_limit('api_notifications_' . $user_id, 60, 60)) {
    $rate_info = get_rate_limit_info('api_notifications_' . $user_id, 60, 60);
    echo json_encode([
        'error' => 'Terlalu banyak permintaan. Coba lagi dalam ' . $rate_info['reset_in'] . ' detik.'
    ]);
    exit;
}

// Get notifications
$notifications = get_user_notifications($user_id, 10);
$unread_count = get_unread_notifications_count($user_id);

// Mark as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
    if ($notification_id > 0) {
        mark_notification_read($notification_id);
    } elseif (isset($_POST['mark_all'])) {
        mark_all_notifications_read($user_id);
    }

    // Refresh counts
    $unread_count = get_unread_notifications_count($user_id);
}

// Return JSON response
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count
]);
