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
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Rate limiting
$user_id = get_current_user_id();
if (!check_rate_limit('api_bookmark_' . $user_id, 30, 60)) {
    $rate_info = get_rate_limit_info('api_bookmark_' . $user_id, 30, 60);
    echo json_encode([
        'success' => false,
        'message' => 'Terlalu banyak permintaan. Coba lagi dalam ' . $rate_info['reset_in'] . ' detik.'
    ]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$location_id = isset($data['location_id']) ? (int)$data['location_id'] : 0;
$action = isset($data['action']) ? $data['action'] : '';

if ($location_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid location ID']);
    exit;
}

// Check if location exists
$location = get_location_by_id($location_id);
if (!$location) {
    echo json_encode(['success' => false, 'message' => 'Location not found']);
    exit;
}

try {
    if ($action === 'add') {
        // Check if already bookmarked
        $exists = db_exists('bookmarks', 'user_id = ? AND location_id = ?', [$user_id, $location_id]);

        if ($exists) {
            echo json_encode(['success' => false, 'message' => 'Already bookmarked']);
            exit;
        }

        // Add bookmark
        $result = db_insert('bookmarks', [
            'user_id' => $user_id,
            'location_id' => $location_id
        ]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Bookmark added',
                'action' => 'added'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add bookmark']);
        }
    } elseif ($action === 'remove') {
        // Remove bookmark
        $result = db_delete('bookmarks', 'user_id = ? AND location_id = ?', [$user_id, $location_id]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Bookmark removed',
                'action' => 'removed'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
