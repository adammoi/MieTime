<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Beranda';
$page_description = 'Temukan kedai mie ayam terbaik di sekitar Anda';

// Get featured locations
$top_locations = get_top_locations(6);
$recent_reviews = db_fetch_all("
SELECT r.*, u.username, l.name as location_name, l.location_id
FROM reviews r
JOIN users u ON r.user_id = u.user_id
JOIN locations l ON r.location_id = l.location_id
WHERE r.status = 'approved'
ORDER BY r.created_at DESC
LIMIT 6
");


include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="gradient-bg text-white py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                <i class="fas fa-bowl-food mr-3"></i>Mie Time
            </h1>
            <p class="text-xl md:text-2xl mb-8 text-blue-100">
                Platform komunitas untuk menemukan dan berbagi review kedai mie ayam terbaik di Indonesia
            </p>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="<?php echo BASE_URL; ?>kedai" class="inline-flex items-center justify-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg">
                    <i class="fas fa-store mr-2"></i>Jelajahi Kedai
                </a>
                <?php if (!is_logged_in()): ?>
                    <a href="<?php echo BASE_URL; ?>register" class="inline-flex items-center justify-center px-6 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white hover:text-blue-600 transition">
                        <i class="fas fa-user-plus mr-2"></i>Daftar Sekarang
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Search Bar -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 mb-12">
    <div class="max-w-3xl mx-auto">
        <form action="<?php echo BASE_URL; ?>kedai" method="GET" class="bg-white rounded-lg shadow-2xl p-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="q"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Cari nama kedai, lokasi, atau menu..." required>
                </div>
                <button type="submit" class="px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                    Cari
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-3">
                <i class="fas fa-lightbulb mr-1"></i>
                Contoh: "Mie Ayam Jakarta Pusat", "bakso urat", "pangsit goreng"
            </p>
        </form>
    </div>
</div>

<!-- Stats Section -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
        <div class="bg-white rounded-lg shadow-md p-6 text-center hover-lift">
            <i class="fas fa-store text-5xl text-blue-600 mb-4"></i>
            <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format(db_count('locations', 'status = "active"')); ?></h3>
            <p class="text-gray-600 text-sm mt-1">Kedai Terdaftar</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 text-center hover-lift">
            <i class="fas fa-star text-5xl text-yellow-500 mb-4"></i>
            <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format(db_count('reviews', 'status = "approved"')); ?></h3>
            <p class="text-gray-600 text-sm mt-1">Review Ditulis</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 text-center hover-lift">
            <i class="fas fa-users text-5xl text-green-600 mb-4"></i>
            <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format(db_count('users')); ?></h3>
            <p class="text-gray-600 text-sm mt-1">Pengguna Aktif</p>
        </div>
        <div class="bg-white rounded-lg shadow-md p-6 text-center hover-lift">
            <i class="fas fa-images text-5xl text-red-600 mb-4"></i>
            <h3 class="text-3xl font-bold text-gray-900"><?php echo number_format(db_count('review_images')); ?></h3>
            <p class="text-gray-600 text-sm mt-1">Foto Dibagikan</p>
        </div>
    </div>
</div>

<!-- Top Locations -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="flex justify-between items-center mb-8">
        <h2 class="text-3xl font-bold text-gray-900">
            <i class="fas fa-trophy text-yellow-500 mr-2"></i>Kedai Terbaik
        </h2>
        <a href="<?php echo BASE_URL; ?>kedai" class="text-blue-600 hover:text-blue-700 font-semibold flex items-center">
            Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($top_locations as $location): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden hover-lift">
                <div class="relative h-48 bg-gray-200">
                    <?php
                    // Prefer a real review image if available; fall back to local inline SVG
                    $reviewImage = db_fetch(
                        "SELECT ri.* FROM review_images ri JOIN reviews r ON ri.review_id = r.review_id WHERE r.location_id = ? AND ri.file_path IS NOT NULL ORDER BY r.upvotes DESC, r.created_at DESC LIMIT 1",
                        [$location['location_id']]
                    );
                    if ($reviewImage && !empty($reviewImage['file_path'])) {
                        $imgSrc = BASE_URL . 'get_image.php?path=' . urlencode($reviewImage['file_path']);
                    } else {
                        $placeholderText = htmlspecialchars($location['name']);
                        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200">'
                            . '<rect width="100%" height="100%" fill="#6c757d" />'
                            . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"'
                            . ' font-family="Inter, Arial, sans-serif" font-size="18" fill="#ffffff">'
                            . $placeholderText
                            . '</text></svg>';
                        $imgSrc = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                    }
                    ?>
                    <img src="<?php echo $imgSrc; ?>"
                        class="w-full h-full object-cover" alt="<?php echo htmlspecialchars($location['name']); ?>">
                    <span class="absolute top-3 right-3 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                        <i class="fas fa-star mr-1"></i><?php echo number_format($location['average_rating'], 1); ?>
                    </span>
                </div>
                <div class="p-5">
                    <h5 class="text-lg font-bold text-gray-900 mb-2">
                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                            class="hover:text-blue-600 transition">
                            <?php echo htmlspecialchars($location['name']); ?>
                        </a>
                    </h5>
                    <p class="text-gray-600 text-sm mb-4 flex items-start">
                        <i class="fas fa-map-marker-alt mr-2 mt-1 flex-shrink-0"></i>
                        <span><?php echo htmlspecialchars(substr($location['address'], 0, 50)); ?>...</span>
                    </p>
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <span class="text-sm text-gray-600">
                            <?php echo star_rating($location['average_rating']); ?>
                        </span>
                        <span class="text-sm text-gray-500">
                            <?php echo $location['total_reviews']; ?> review
                        </span>
                    </div>
                </div>
                <div class="px-5 pb-5">
                    <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                        class="block w-full text-center px-4 py-2 border border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition">
                        Lihat Detail
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Reviews -->
<div class="bg-gray-100 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-comment-dots text-blue-600 mr-2"></i>Review Terbaru
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($recent_reviews as $review): ?>
                <div class="bg-white rounded-lg shadow-md p-6 hover-lift">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h6 class="font-semibold text-gray-900 flex items-center">
                                <i class="fas fa-user-circle text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($review['username']); ?>
                            </h6>
                            <p class="text-sm text-gray-500"><?php echo time_ago($review['created_at']); ?></p>
                        </div>
                        <span class="bg-yellow-400 text-gray-900 px-2 py-1 rounded text-sm font-bold">
                            <?php echo star_rating($review['rating']); ?>
                        </span>
                    </div>
                    <h6 class="font-semibold text-gray-900 mb-2">
                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $review['location_id']; ?>"
                            class="hover:text-blue-600 transition">
                            <?php echo htmlspecialchars($review['location_name']); ?>
                        </a>
                    </h6>
                    <p class="text-gray-600 mb-4">
                        <?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?>...
                    </p>
                    <div class="flex justify-between items-center text-sm pt-3 border-t border-gray-200">
                        <span class="text-gray-500">
                            <i class="fas fa-thumbs-up mr-1"></i><?php echo $review['upvotes']; ?>
                        </span>
                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $review['location_id']; ?>"
                            class="text-blue-600 hover:text-blue-700 font-medium">
                            Baca Selengkapnya →
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="gradient-bg text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Yuk gabung ke Komunitas Mie Time !</h2>
        <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Bagikan pengalaman kuliner dan bantu orang lain menemukan mie ayam terbaik
        </p>
        <?php if (!is_logged_in()): ?>
            <a href="<?php echo BASE_URL; ?>register" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-gray-100 transition shadow-2xl text-lg">
                <i class="fas fa-user-plus mr-2"></i>Daftar Gratis
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>kedai/add" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-gray-100 transition shadow-2xl text-lg">
                <i class="fas fa-plus-circle mr-2"></i>Tambah Kedai Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>