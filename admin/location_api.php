<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

require_admin_or_moderator();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;
        if ($location_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'location_id required']);
            exit;
        }

        $loc = db_fetch('SELECT location_id, name, address, latitude, longitude, owner_user_id, status FROM locations WHERE location_id = ?', [$location_id]);
        if (!$loc) {
            http_response_code(404);
            echo json_encode(['error' => 'Location not found']);
            exit;
        }

        echo json_encode(['location' => $loc]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
        if ($location_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'location_id required']);
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
        $longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
        $status = $_POST['status'] ?? '';

        $allowed = ['active', 'pending_approval'];
        if ($status !== '' && !in_array($status, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid status']);
            exit;
        }

        $update = [];
        if ($name !== '') $update['name'] = $name;
        if ($address !== '') $update['address'] = $address;
        if ($latitude !== null) $update['latitude'] = $latitude;
        if ($longitude !== null) $update['longitude'] = $longitude;
        if ($status !== '') $update['status'] = $status;

        if (!empty($update)) {
            db_update('locations', $update, 'location_id = :location_id', ['location_id' => $location_id]);
        }

        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo json_encode(['error' => $e->getMessage()]);
    } else {
        echo json_encode(['error' => 'Server error']);
    }
    exit;
}
