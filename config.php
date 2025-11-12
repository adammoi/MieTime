<?php

/**
 * Mie Time - Konfigurasi Global
 * File ini berisi semua konfigurasi aplikasi
 */

// Mencegah akses langsung
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

// ==================== DATABASE CONFIG ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'mie_time');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ==================== PATH CONFIG ====================
define('BASE_URL', 'http://localhost/workshop/mie-time/');
define('BASE_PATH', __DIR__ . '/');
define('UPLOAD_PATH', BASE_PATH . 'uploads/');
define('ASSETS_URL', BASE_URL . 'assets/');

// ==================== SECURITY CONFIG ====================
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 10);
define('SESSION_LIFETIME', 3600 * 24); // 24 jam
define('CSRF_TOKEN_NAME', '_csrf_token');

// ==================== UPLOAD CONFIG ====================
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png']);
define('MAX_IMAGES_PER_REVIEW', 5);

// ==================== PAGINATION CONFIG ====================
define('ITEMS_PER_PAGE', 12);
define('LEADERBOARD_TOP', 10);

// ==================== GAMIFICATION CONFIG ====================
define('POINTS_PER_REVIEW', 10);
define('POINTS_PER_UPVOTE', 2);
define('POINTS_PER_LOCATION_ADD', 25);
define('NEW_USER_REVIEW_THRESHOLD', 3); // Review pertama otomatis pending

// ==================== MODERATION CONFIG ====================
define('AUTO_APPROVE_REVIEWS', false); // Set true untuk skip moderasi
define('PROFANITY_CHECK', true);
define('SPAM_CHECK', true);

// ==================== TIMEZONE ====================
date_default_timezone_set('Asia/Jakarta');

// ==================== ERROR REPORTING ====================
// Set FALSE di production
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// ==================== SESSION CONFIG ====================
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set 1 jika menggunakan HTTPS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Generate CSRF Token
 */
function generate_csrf_token()
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verify_csrf_token($token)
{
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirect helper
 */
function redirect($url)
{
    header("Location: " . BASE_URL . ltrim($url, '/'));
    exit;
}

/**
 * Flash message helper
 */
function set_flash($type, $message)
{
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type)
{
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

/**
 * Clean input data
 */
function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_current_user_id()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Check user role
 */
function has_role($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin or moderator
 */
function is_admin_or_moderator()
{
    return has_role('admin') || has_role('moderator');
}

/**
 * Format date to Indonesian
 */
function format_date_id($date)
{
    $timestamp = strtotime($date);
    $months = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);

    return "$day $month $year";
}

/**
 * Time ago helper
 */
function time_ago($datetime)
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' menit lalu';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' jam lalu';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' hari lalu';
    } else {
        return format_date_id($datetime);
    }
}

/**
 * Generate star rating HTML
 */
function star_rating($rating, $max = 5)
{
    $stars = '';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }
    }
    return $stars;
}

/**
 * Sanitize filename
 */
function sanitize_filename($filename)
{
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    return $filename;
}

/**
 * Generate random hash for filename
 */
function generate_file_hash($file)
{
    return hash_file('sha256', $file);
}
