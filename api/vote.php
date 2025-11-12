<?php

/**
 * Mie Time - Vote API
 * Handle upvote/downvote untuk review
 */

define('MIE_TIME', true);
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda harus login terlebih dahulu'
    ]);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$review_id = isset($input['review_id']) ? (int)$input['review_id'] : 0;
$vote_type = isset($input['vote_type']) ? (int)$input['vote_type'] : 0;

// Validate input
if ($review_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Review ID tidak valid'
    ]);
    exit;
}

if (!in_array($vote_type, [1, -1])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vote type harus 1 (upvote) atau -1 (downvote)'
    ]);
    exit;
}

// Check if review exists
$review = get_review_by_id($review_id);
if (!$review) {
    echo json_encode([
        'success' => false,
        'message' => 'Review tidak ditemukan'
    ]);
    exit;
}

// Can't vote own review
if ($review['user_id'] == get_current_user_id()) {
    echo json_encode([
        'success' => false,
        'message' => 'Anda tidak bisa vote review sendiri'
    ]);
    exit;
}

try {
    $db = get_db();
    $user_id = get_current_user_id();

    // Check if user already voted
    $existing_vote = get_user_vote($review_id, $user_id);

    if ($existing_vote) {
        // Update existing vote using INSERT ON DUPLICATE KEY UPDATE
        $sql = "INSERT INTO review_votes (review_id, user_id, vote_type) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)";

        $stmt = $db->prepare($sql);
        $stmt->execute([$review_id, $user_id, $vote_type]);

        $message = 'Vote berhasil diubah';
    } else {
        // Insert new vote
        db_insert('review_votes', [
            'review_id' => $review_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type
        ]);

        $message = 'Vote berhasil';

        // Award points to review author
        $review_author = get_user_by_id($review['user_id']);
        $new_points = $review_author['points'] + POINTS_PER_UPVOTE;
        update_user_points($review['user_id'], $new_points);

        // Create notification for review author
        if ($vote_type == 1) {
            create_notification(
                $review['user_id'],
                "Review Anda mendapat upvote! +" . POINTS_PER_UPVOTE . " poin",
                "warung/{$review['location_id']}"
            );
        }

        // Check for "Ahli Pangsit" badge (10 upvotes on single review)
        $review_updated = get_review_by_id($review_id);
        if ($review_updated['upvotes'] >= 10 && !has_badge($review['user_id'], 5)) {
            award_badge($review['user_id'], 5); // Badge ID 5
        }

        // Check for "Kritikus Terpercaya" badge (100 total upvotes)
        $total_upvotes = db_fetch("
            SELECT SUM(upvotes) as total 
            FROM reviews 
            WHERE user_id = ?
        ", [$review['user_id']]);

        if ($total_upvotes['total'] >= 100 && !has_badge($review['user_id'], 6)) {
            award_badge($review['user_id'], 6); // Badge ID 6
        }
    }

    // Get updated vote counts
    $updated_review = get_review_by_id($review_id);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'upvotes' => $updated_review['upvotes'],
        'downvotes' => $updated_review['downvotes']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ]);
}
