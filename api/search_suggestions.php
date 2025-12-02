<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Rate limiting
rate_limit('api_search_suggestions', 60, 60); // 60 requests per minute

$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';

if (strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

try {
    // Search in location names and addresses
    $sql = "SELECT 
                location_id,
                name,
                address,
                average_rating,
                total_reviews
            FROM locations 
            WHERE status = 'active' 
            AND (name LIKE ? OR address LIKE ?)
            ORDER BY total_reviews DESC, average_rating DESC
            LIMIT 8";

    $search_param = '%' . $query . '%';
    $suggestions = db_fetch_all($sql, [$search_param, $search_param]);

    // Format suggestions
    $formatted = array_map(function ($loc) {
        return [
            'id' => $loc['location_id'],
            'name' => $loc['name'],
            'address' => $loc['address'],
            'rating' => $loc['average_rating'] ? number_format($loc['average_rating'], 1) : 'Belum ada rating',
            'reviews' => $loc['total_reviews'],
            'url' => BASE_URL . 'kedai/' . $loc['location_id']
        ];
    }, $suggestions);

    echo json_encode([
        'success' => true,
        'suggestions' => $formatted
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil saran pencarian'
    ]);
}
