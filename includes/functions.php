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
        'badge_id' => $badge_id,
        'earned_at' => date('Y-m-d H:i:s')
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
 * Auto-assign role-based badges
 * Call this when user role changes
 */
function assign_role_badge($user_id)
{
    $user = get_user_by_id($user_id);
    if (!$user) return;

    // Get role badge based on role
    $role_badges = [
        'admin' => 'Admin Badge',
        'moderator' => 'Moderator Badge',
        'verified_owner' => 'Verified Owner'
    ];

    if (isset($role_badges[$user['role']])) {
        $badge = db_fetch("SELECT badge_id FROM badges WHERE badge_name = ? LIMIT 1", [$role_badges[$user['role']]]);
        if ($badge && !has_badge($user_id, $badge['badge_id'])) {
            award_badge($user_id, $badge['badge_id']);
        }
    }
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

    $html = '<nav class="flex justify-center"><ul class="flex space-x-2">';

    // Previous button
    if ($current_page > 1) {
        $html .= '<li><a href="' . $base_url . 'page=' . ($current_page - 1) . '" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-gray-700 font-medium">Previous</a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active_class = ($i == $current_page) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50';
        $html .= '<li><a href="' . $base_url . 'page=' . $i . '" class="px-4 py-2 border rounded-lg transition font-medium ' . $active_class . '">' . $i . '</a></li>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $html .= '<li><a href="' . $base_url . 'page=' . ($current_page + 1) . '" class="px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-gray-700 font-medium">Next</a></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}

// ==================== RATE LIMITING ====================

/**
 * Check rate limit for API requests
 * @param string $key Unique identifier (e.g., 'api_vote_' . $user_id)
 * @param int $max_requests Maximum requests allowed
 * @param int $time_window Time window in seconds
 * @return bool True if allowed, false if rate limit exceeded
 */
function check_rate_limit($key, $max_requests = 10, $time_window = 60)
{
    // Use session for simple rate limiting (production should use Redis/Memcached)
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }

    $now = time();
    $rate_key = 'rl_' . $key;

    // Clean old entries
    if (isset($_SESSION['rate_limit'][$rate_key])) {
        $_SESSION['rate_limit'][$rate_key] = array_filter(
            $_SESSION['rate_limit'][$rate_key],
            function ($timestamp) use ($now, $time_window) {
                return ($now - $timestamp) < $time_window;
            }
        );
    } else {
        $_SESSION['rate_limit'][$rate_key] = [];
    }

    // Check if limit exceeded
    if (count($_SESSION['rate_limit'][$rate_key]) >= $max_requests) {
        return false;
    }

    // Add current request
    $_SESSION['rate_limit'][$rate_key][] = $now;
    return true;
}

/**
 * Get remaining rate limit info
 */
function get_rate_limit_info($key, $max_requests = 10, $time_window = 60)
{
    if (!isset($_SESSION['rate_limit'])) {
        return ['remaining' => $max_requests, 'reset_in' => 0];
    }

    $rate_key = 'rl_' . $key;
    $requests = $_SESSION['rate_limit'][$rate_key] ?? [];
    $now = time();

    // Filter valid requests
    $valid_requests = array_filter($requests, function ($timestamp) use ($now, $time_window) {
        return ($now - $timestamp) < $time_window;
    });

    $remaining = max(0, $max_requests - count($valid_requests));
    $oldest = !empty($valid_requests) ? min($valid_requests) : $now;
    $reset_in = max(0, $time_window - ($now - $oldest));

    return [
        'remaining' => $remaining,
        'reset_in' => $reset_in,
        'limit' => $max_requests
    ];
}

// ==================== EMAIL NOTIFICATIONS ====================

/**
 * Send email notification
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email body (HTML)
 * @param string $from_email From email (optional)
 * @param string $from_name From name (optional)
 * @return bool Success status
 */
function send_email($to, $subject, $message, $from_email = null, $from_name = null)
{
    $from_email = $from_email ?? (defined('SITE_EMAIL') ? SITE_EMAIL : 'noreply@mietime.com');
    $from_name = $from_name ?? (defined('SITE_NAME') ? SITE_NAME : 'Mie Time');

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Reply-To: ' . $from_email,
        'X-Mailer: PHP/' . phpversion()
    ];

    // Wrap message in email template
    $html_message = email_template($message, $subject);

    // Send email
    return mail($to, $subject, $html_message, implode("\r\n", $headers));
}

/**
 * Email template wrapper
 */
function email_template($content, $title = '')
{
    $site_name = defined('SITE_NAME') ? SITE_NAME : 'Mie Time';
    $site_url = defined('BASE_URL') ? BASE_URL : 'http://localhost/';

    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Arial,sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4;padding:20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr>
                            <td style="background-color:#0d6efd;padding:30px;text-align:center;">
                                <h1 style="color:#ffffff;margin:0;font-size:28px;">🍜 ' . $site_name . '</h1>
                            </td>
                        </tr>
                        <!-- Content -->
                        <tr>
                            <td style="padding:40px 30px;">
                                ' . $content . '
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background-color:#f8f9fa;padding:20px;text-align:center;border-top:1px solid #dee2e6;">
                                <p style="margin:0;color:#6c757d;font-size:14px;">
                                    © ' . date('Y') . ' ' . $site_name . '. All rights reserved.
                                </p>
                                <p style="margin:10px 0 0;font-size:12px;">
                                    <a href="' . $site_url . '" style="color:#0d6efd;text-decoration:none;">Visit Website</a>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

/**
 * Send verification email to new verified owner
 */
function send_verified_owner_email($user_id, $location_id)
{
    $user = get_user_by_id($user_id);
    $location = get_location_by_id($location_id);

    if (!$user || !$location) {
        return false;
    }

    $subject = '🎉 Selamat! Anda Sekarang Verified Owner';
    $message = '
        <h2 style="color:#28a745;margin-top:0;">Selamat, ' . htmlspecialchars($user['username']) . '!</h2>
        <p style="font-size:16px;line-height:1.6;color:#333;">
            Klaim kepemilikan Anda untuk <strong>' . htmlspecialchars($location['name']) . '</strong> telah disetujui.
        </p>
        <p style="font-size:16px;line-height:1.6;color:#333;">
            Sebagai Verified Owner, Anda sekarang memiliki akses ke fitur-fitur berikut:
        </p>
        <ul style="font-size:16px;line-height:1.8;color:#333;">
            <li>Badge "Verified Owner" di profil dan review Anda</li>
            <li>Prioritas dalam moderasi review</li>
            <li>Kemampuan membalas review pelanggan</li>
            <li>Akses statistik dan analytics kedai Anda</li>
        </ul>
        <p style="text-align:center;margin:30px 0;">
            <a href="' . BASE_URL . 'kedai/' . $location_id . '" 
               style="display:inline-block;padding:15px 30px;background-color:#0d6efd;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;">
                Lihat Kedai Anda
            </a>
        </p>
        <p style="font-size:14px;color:#6c757d;border-top:1px solid #dee2e6;padding-top:20px;margin-top:30px;">
            Terima kasih telah bergabung dengan komunitas Mie Time!
        </p>
    ';

    return send_email($user['email'], $subject, $message);
}
