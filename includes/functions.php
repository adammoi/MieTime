<?php

/**
 * Mie Time - Helper Functions
 * Fungsi-fungsi umum yang digunakan di seluruh aplikasi
 */

if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}

// ==================== USER FUNCTIONS ====================

/**
 * Get user data by ID
 */
function get_user_by_id($user_id)
{
    return db_fetch("SELECT * FROM users WHERE user_id = ?", [$user_id]);
}

/**
 * Get user data by email
 */
function get_user_by_email($email)
{
    return db_fetch("SELECT * FROM users WHERE email = ?", [$email]);
}

/**
 * Get user data by username
 */
function get_user_by_username($username)
{
    return db_fetch("SELECT * FROM users WHERE username = ?", [$username]);
}

/**
 * Update user points
 */
function update_user_points($user_id, $points)
{
    return db_update(
        'users',
        ['points' => $points],
        'user_id = :user_id',
        ['user_id' => $user_id]
    );
}

/**
 * Increment user review count
 */
function increment_review_count($user_id)
{
    $sql = "UPDATE users SET review_count = review_count + 1 WHERE user_id = ?";
    return db_query($sql, [$user_id]);
}

// ==================== LOCATION FUNCTIONS ====================

/**
 * Get location by ID
 */
function get_location_by_id($location_id)
{
    return db_fetch("SELECT * FROM locations WHERE location_id = ?", [$location_id]);
}

/**
 * Get all active locations
 */
function get_all_locations($limit = null, $offset = 0)
{
    $sql = "SELECT * FROM locations WHERE status = 'active' ORDER BY average_rating DESC";
    if ($limit) {
        $sql .= " LIMIT $limit OFFSET $offset";
    }
    return db_fetch_all($sql);
}

/**
 * Get locations by search query
 */
function search_locations($query, $limit = null)
{
    $sql = "SELECT * FROM locations 
            WHERE status = 'active' 
            AND (name LIKE ? OR address LIKE ?)
            ORDER BY average_rating DESC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    $search = "%$query%";
    return db_fetch_all($sql, [$search, $search]);
}

/**
 * Get top rated locations
 */
function get_top_locations($limit = 10)
{
    $sql = "SELECT * FROM locations 
            WHERE status = 'active' AND total_reviews > 0
            ORDER BY average_rating DESC, total_reviews DESC 
            LIMIT ?";
    return db_fetch_all($sql, [$limit]);
}

// ==================== REVIEW FUNCTIONS ====================

/**
 * Get review by ID
 */
function get_review_by_id($review_id)
{
    $sql = "SELECT r.*, u.username, u.role, l.name as location_name
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            JOIN locations l ON r.location_id = l.location_id
            WHERE r.review_id = ?";
    return db_fetch($sql, [$review_id]);
}

/**
 * Get reviews by location
 */
function get_reviews_by_location($location_id, $status = 'approved', $limit = null)
{
    $sql = "SELECT r.*, u.username, u.role
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.location_id = ? AND r.status = ?
            ORDER BY r.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return db_fetch_all($sql, [$location_id, $status]);
}

/**
 * Get reviews by user
 */
function get_reviews_by_user($user_id, $limit = null)
{
    $sql = "SELECT r.*, l.name as location_name
            FROM reviews r
            JOIN locations l ON r.location_id = l.location_id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return db_fetch_all($sql, [$user_id]);
}

/**
 * Get pending reviews (for moderation)
 */
function get_pending_reviews($limit = null)
{
    $sql = "SELECT r.*, u.username, l.name as location_name
            FROM reviews r
            JOIN users u ON r.user_id = u.user_id
            JOIN locations l ON r.location_id = l.location_id
            WHERE r.status = 'pending'
            ORDER BY r.created_at ASC";
    if ($limit) {
        $sql .= " LIMIT $limit";
    }
    return db_fetch_all($sql);
}

/**
 * Check if user has voted on review
 */
function has_voted($review_id, $user_id)
{
    return db_exists(
        'review_votes',
        'review_id = :review_id AND user_id = :user_id',
        ['review_id' => $review_id, 'user_id' => $user_id]
    );
}

/**
 * Get user's vote on review
 */
function get_user_vote($review_id, $user_id)
{
    return db_fetch(
        "SELECT vote_type FROM review_votes WHERE review_id = ? AND user_id = ?",
        [$review_id, $user_id]
    );
}

/**
 * Get review images
 */
function get_review_images($review_id)
{
    return db_fetch_all("SELECT * FROM review_images WHERE review_id = ?", [$review_id]);
}

// ==================== GAMIFICATION FUNCTIONS ====================

/**
 * Get user badges
 */
function get_user_badges($user_id)
{
    $sql = "SELECT b.*, ub.earned_at
            FROM user_badges ub
            JOIN badges b ON ub.badge_id = b.badge_id
            WHERE ub.user_id = ?
            ORDER BY ub.earned_at DESC";
    return db_fetch_all($sql, [$user_id]);
}

/**
 * Check and award badges to user
 */
function check_and_award_badges($user_id)
{
    $user = get_user_by_id($user_id);
    if (!$user) return;

    $badges_to_award = [];

    // Badge: Cicipan Pertama (review_count >= 1)
    if ($user['review_count'] >= 1 && !has_badge($user_id, 1)) {
        $badges_to_award[] = 1;
    }

    // Badge: Juru Cicip (review_count >= 10)
    if ($user['review_count'] >= 10 && !has_badge($user_id, 3)) {
        $badges_to_award[] = 3;
    }

    // Badge: Pakar Mie (review_count >= 50)
    if ($user['review_count'] >= 50 && !has_badge($user_id, 4)) {
        $badges_to_award[] = 4;
    }

    // Award badges
    foreach ($badges_to_award as $badge_id) {
        award_badge($user_id, $badge_id);
    }
}

/**
 * Check if user has badge
 */
function has_badge($user_id, $badge_id)
{
    return db_exists(
        'user_badges',
        'user_id = :user_id AND badge_id = :badge_id',
        ['user_id' => $user_id, 'badge_id' => $badge_id]
    );
}

/**
 * Award badge to user
 */
function award_badge($user_id, $badge_id)
{
    if (has_badge($user_id, $badge_id)) {
        return false;
    }

    $result = db_insert('user_badges', [
        'user_id' => $user_id,
        'badge_id' => $badge_id
    ]);

    if ($result) {
        // Create notification
        $badge = db_fetch("SELECT badge_name FROM badges WHERE badge_id = ?", [$badge_id]);
        create_notification(
            $user_id,
            "Selamat! Anda mendapatkan badge: <strong>{$badge['badge_name']}</strong>",
            "badges"
        );
    }

    return $result;
}

/**
 * Get leaderboard
 */
function get_leaderboard($limit = 10)
{
    $sql = "SELECT user_id, username, review_count, points,
            DENSE_RANK() OVER (ORDER BY review_count DESC, points DESC) as user_rank
            FROM users
            WHERE role != 'admin'
            ORDER BY review_count DESC, points DESC
            LIMIT ?";
    return db_fetch_all($sql, [$limit]);
}

/**
 * Get user rank
 */
function get_user_rank($user_id)
{
    $sql = "SELECT * FROM (
                SELECT user_id, review_count, points,
                DENSE_RANK() OVER (ORDER BY review_count DESC, points DESC) as user_rank
                FROM users
                WHERE role != 'admin'
            ) as rankings
            WHERE user_id = ?";
    return db_fetch($sql, [$user_id]);
}

// ==================== NOTIFICATION FUNCTIONS ====================

/**
 * Create notification
 */
function create_notification($user_id, $message, $link_url = null)
{
    return db_insert('notifications', [
        'user_id' => $user_id,
        'message' => $message,
        'link_url' => $link_url
    ]);
}

/**
 * Get unread notifications count
 */
function get_unread_notifications_count($user_id)
{
    return db_count(
        'notifications',
        'user_id = :user_id AND is_read = 0',
        ['user_id' => $user_id]
    );
}

/**
 * Get user notifications
 */
function get_user_notifications($user_id, $limit = 10)
{
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?";
    return db_fetch_all($sql, [$user_id, $limit]);
}

/**
 * Mark notification as read
 */
function mark_notification_read($notification_id)
{
    return db_update(
        'notifications',
        ['is_read' => 1],
        'notification_id = :notification_id',
        ['notification_id' => $notification_id]
    );
}

/**
 * Mark all notifications as read
 */
function mark_all_notifications_read($user_id)
{
    return db_update(
        'notifications',
        ['is_read' => 1],
        'user_id = :user_id AND is_read = 0',
        ['user_id' => $user_id]
    );
}

// ==================== MODERATION FUNCTIONS ====================

/**
 * Check profanity in text
 */
function contains_profanity($text)
{
    if (!PROFANITY_CHECK) return false;

    // Daftar kata kasar bahasa Indonesia
    $bad_words = [
        'anjing',
        'bangsat',
        'bajingan',
        'kampret',
        'kontol',
        'memek',
        'ngentot',
        'tolol',
        'goblok',
        'bego'
    ];

    $text_lower = strtolower($text);

    foreach ($bad_words as $word) {
        // Word boundary check untuk hindari "Scunthorpe problem"
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $text_lower)) {
            return true;
        }
    }

    return false;
}

/**
 * Check spam in text
 */
function contains_spam($text)
{
    if (!SPAM_CHECK) return false;

    // Check untuk URL
    if (preg_match('/(http|https|www\.|wa\.me)/i', $text)) {
        return true;
    }

    // Check untuk nomor telepon
    if (preg_match('/(\+62|08)\d{8,12}/', $text)) {
        return true;
    }

    // Check untuk email
    if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text)) {
        return true;
    }

    // Spam keywords
    $spam_keywords = ['poker', 'slot', 'judi', 'togel', 'casino'];
    $text_lower = strtolower($text);

    foreach ($spam_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Auto-moderate review content
 */
function auto_moderate_review($review_text)
{
    $reasons = [];

    if (contains_profanity($review_text)) {
        $reasons[] = 'Potensi kata kasar terdeteksi';
    }

    if (contains_spam($review_text)) {
        $reasons[] = 'Potensi spam terdeteksi';
    }

    return [
        'flagged' => !empty($reasons),
        'reasons' => $reasons
    ];
}

// ==================== IMAGE FUNCTIONS ====================

/**
 * Upload and process image
 */
function upload_review_image($file, $review_id)
{
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload gagal'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File terlalu besar (max 5MB)'];
    }

    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'error' => 'Tipe file tidak diizinkan'];
    }

    // Generate hash filename
    $hash = hash_file('sha256', $file['tmp_name']);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = $hash . '.' . $ext;

    // Create upload directory if not exists
    $upload_dir = UPLOAD_PATH . 'reviews/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $destination = $upload_dir . $new_filename;

    // Image laundering with GD if available, otherwise fall back to moving the uploaded file
    $saved = false;
    $ext = strtolower($ext);

    // Try GD processing when functions are available
    if ((($file['type'] === 'image/jpeg' || $file['type'] === 'image/jpg') && function_exists('imagecreatefromjpeg'))
        || ($file['type'] === 'image/png' && function_exists('imagecreatefrompng'))
    ) {
        $image = null;
        if ($file['type'] === 'image/jpeg' || $file['type'] === 'image/jpg') {
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($file['tmp_name']);
            }
        } elseif ($file['type'] === 'image/png') {
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($file['tmp_name']);
            }
        }

        if ($image) {
            // Resize if too large
            $width = imagesx($image);
            $height = imagesy($image);
            $max_width = 1200;

            if ($width > $max_width) {
                $new_width = $max_width;
                $new_height = ($height / $width) * $new_width;
                $resized = imagecreatetruecolor($new_width, $new_height);
                imagecopyresampled($resized, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                imagedestroy($image);
                $image = $resized;
            }

            // Save laundered image as JPEG for consistent quality
            $new_filename = $hash . '.jpg';
            $destination = $upload_dir . $new_filename;
            imagejpeg($image, $destination, 90);
            imagedestroy($image);
            $saved = true;
        }
    }

    // Fallback: move the original uploaded file if GD not available or processing failed
    if (!$saved) {
        if (is_uploaded_file($file['tmp_name'])) {
            if (@move_uploaded_file($file['tmp_name'], $destination)) {
                $saved = true;
            }
        } else {
            // Try a direct copy as a last resort
            if (@copy($file['tmp_name'], $destination)) {
                $saved = true;
            }
        }
    }

    if (!$saved) {
        return ['success' => false, 'error' => 'Gagal memproses gambar'];
    }

    // Save to database
    $result = db_insert('review_images', [
        'review_id' => $review_id,
        'file_path' => 'reviews/' . $new_filename,
        'file_name_hash' => $hash
    ]);

    return [
        'success' => true,
        'image_id' => $result,
        'path' => 'reviews/' . $new_filename
    ];
}

// ==================== PAGINATION FUNCTIONS ====================

/**
 * Generate pagination HTML
 */
function generate_pagination($total_items, $current_page, $base_url, $items_per_page = ITEMS_PER_PAGE)
{
    $total_pages = ceil($total_items / $items_per_page);

    if ($total_pages <= 1) return '';

    $html = '<nav><ul class="pagination justify-content-center">';

    // Previous button
    if ($current_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}
