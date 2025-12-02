<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$token = $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verify_csrf_token($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF token tidak valid']);
    exit;
}

// Rate limiting
if (is_logged_in()) {
    $user_id = get_current_user_id();
    if (!check_rate_limit('api_review_image_' . $user_id, 10, 60)) {
        $rate_info = get_rate_limit_info('api_review_image_' . $user_id, 10, 60);
        http_response_code(429);
        echo json_encode([
            'error' => 'Terlalu banyak permintaan. Coba lagi dalam ' . $rate_info['reset_in'] . ' detik.'
        ]);
        exit;
    }
}

$image_id = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
if ($image_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'image_id tidak valid']);
    exit;
}

$img = db_fetch('SELECT * FROM review_images WHERE image_id = ?', [$image_id]);
if (!$img) {
    http_response_code(404);
    echo json_encode(['error' => 'Gambar tidak ditemukan']);
    exit;
}

$review = db_fetch('SELECT * FROM reviews WHERE review_id = ?', [$img['review_id']]);
if (!$review) {
    http_response_code(404);
    echo json_encode(['error' => 'Review terkait tidak ditemukan']);
    exit;
}

$current = get_current_user_id();
if ($current !== (int)$review['user_id'] && !is_admin_or_moderator()) {
    http_response_code(403);
    echo json_encode(['error' => 'Tidak memiliki izin untuk menghapus gambar ini']);
    exit;
}

// Delete file
$file_path = UPLOAD_PATH . $img['file_path'];
if (file_exists($file_path)) {
    @unlink($file_path);
}

// Delete DB record
$ok = db_delete('review_images', 'image_id = ?', [$image_id]);
if (!$ok) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal menghapus data gambar']);
    exit;
}

echo json_encode(['success' => true]);
exit;
