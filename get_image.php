<?php

/**
 * Mie Time - Secure Image Serving
 * Serve images dari folder upload yang di luar web root
 */

define('MIE_TIME', true);
require_once 'config.php';

// Get requested image path
$path = $_GET['path'] ?? '';

if (empty($path)) {
    http_response_code(400);
    die('Invalid image path');
}

// Security: prevent directory traversal
$path = str_replace(['../', '..\\', '..'], '', $path);

// Full file path
$file_path = UPLOAD_PATH . $path;

// Check if file exists
if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    die('Image not found');
}

// Check if it's actually an image
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($mime_type, $allowed_types)) {
    http_response_code(403);
    die('Invalid file type');
}

// Set headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');

// Output image
readfile($file_path);
exit;
