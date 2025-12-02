<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

require_admin_or_moderator();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'get';

        // Search users
        if ($action === 'search') {
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                echo json_encode(['users' => []]);
                exit;
            }

            $search = '%' . $query . '%';
            $users = db_fetch_all(
                'SELECT user_id, username, email, role 
                 FROM users 
                 WHERE username LIKE ? OR email LIKE ? 
                 ORDER BY username ASC 
                 LIMIT 20',
                [$search, $search]
            );

            echo json_encode(['users' => $users]);
            exit;
        }

        // Get single user
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id required']);
            exit;
        }

        $user = db_fetch('SELECT user_id, username, email, role, is_banned, review_count, points, created_at FROM users WHERE user_id = ?', [$user_id]);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        echo json_encode(['user' => $user]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Expect multipart/form-data or application/x-www-form-urlencoded
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }

        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'user_id required']);
            exit;
        }

        // Prevent editing yourself via this endpoint (optional)
        if ($user_id === get_current_user_id()) {
            http_response_code(403);
            echo json_encode(['error' => 'Cannot edit yourself here']);
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $is_banned = isset($_POST['is_banned']) && ($_POST['is_banned'] == '1' || $_POST['is_banned'] === 'on') ? 1 : 0;

        $allowed = ['admin', 'moderator', 'contributor', 'verified_owner'];
        if ($role !== '' && !in_array($role, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role']);
            exit;
        }

        $update = [];
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email']);
                exit;
            }
            $update['email'] = $email;
        }
        if ($role !== '') {
            $update['role'] = $role;
        }
        $update['is_banned'] = $is_banned;

        if (!empty($update)) {
            db_update('users', $update, 'user_id = :user_id', ['user_id' => $user_id]);
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
