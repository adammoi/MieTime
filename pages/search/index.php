<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$results = [];
$total_results = 0;

if (!empty($query) && strlen($query) >= 3) {
    $results = search_locations($query, 50);
    $total_results = count($results);
}

$page_title = 'Pencarian' . ($query ? ': ' . $query : '');
include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <!-- Search Header -->
    <div class="text-center mb-8">
        <h2 class="font-bold text-4xl text-gray-900 mb-4">
            <i class="fas fa-search text-blue-600 mr-3"></i>Cari Kedai Mie Ayam
        </h2>
        <p class="text-gray-600">Temukan kedai mie ayam favorit Anda di seluruh Indonesia</p>
    </div>

    <!-- Search Form -->
    <div class="max-w-3xl mx-auto mb-8">
        <form action="" method="GET" class="bg-white rounded-2xl shadow-lg">
            <div class="p-6">
                <div class="flex">
                    <span class="gradient-primary text-white px-6 py-4 rounded-l-lg flex items-center">
                        <i class="fas fa-search text-xl"></i>
                    </span>
                    <input type="text" name="q" class="flex-1 px-6 py-4 border-2 border-l-0 border-gray-300 focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                        placeholder="Nama kedai atau lokasi..."
                        value="<?php echo htmlspecialchars($query); ?>"
                        autofocus>
                    <button type="submit" class="gradient-primary text-white font-bold px-8 py-4 rounded-r-lg hover-lift transition">
                        Cari
                    </button>
                </div>
                <small class="text-gray-600 block mt-3">
                    <i class="fas fa-lightbulb mr-1"></i>
                    Contoh: "Mie Ayam Pak Sastro", "mie ayam Surabaya", "mie pangsit Jakarta"
                </small>
            </div>
        </form>
    </div>

    <!-- Results -->
    <?php if (!empty($query)): ?>
        <?php if ($total_results > 0): ?>
            <div class="mb-6">
                <h5 class="font-bold text-2xl text-gray-900">
                    <i class="fas fa-list text-green-600 mr-2"></i>
                    Ditemukan <?php echo $total_results; ?> hasil untuk "<?php echo htmlspecialchars($query); ?>"
                </h5>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($results as $location): ?>
                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover-lift transition h-full">
                        <!-- Image -->
                        <div class="relative h-48">
                            <?php
                            // Try to use an existing review image; else inline SVG (local)
                            $reviewImage = db_fetch(
                                "SELECT ri.* FROM review_images ri JOIN reviews r ON ri.review_id = r.review_id WHERE r.location_id = ? AND ri.file_path IS NOT NULL ORDER BY r.upvotes DESC, r.created_at DESC LIMIT 1",
                                [$location['location_id']]
                            );
                            if ($reviewImage && !empty($reviewImage['file_path'])) {
                                $imgSrc = BASE_URL . 'get_image.php?path=' . urlencode($reviewImage['file_path']);
                            } else {
                                $placeholderText = htmlspecialchars($location['name']);
                                // Search cards were 400x180; keep consistent
                                $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="180">'
                                    . '<rect width="100%" height="100%" fill="#6c757d" />'
                                    . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"'
                                    . ' font-family="Inter, Arial, sans-serif" font-size="18" fill="#ffffff">'
                                    . $placeholderText
                                    . '</text></svg>';
                                $imgSrc = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                            }
                            ?>
                            <img src="<?php echo $imgSrc; ?>"
                                class="w-full h-full object-cover"
                                alt="<?php echo htmlspecialchars($location['name']); ?>">

                            <?php if ($location['total_reviews'] > 0): ?>
                                <span class="absolute top-2 right-2 inline-flex items-center px-3 py-1 bg-yellow-400 text-gray-900 rounded-full text-sm font-bold">
                                    <i class="fas fa-star mr-1"></i>
                                    <?php echo number_format($location['average_rating'], 1); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <h5 class="font-bold text-xl mb-3">
                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                    class="text-gray-900 hover:text-blue-600 transition">
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </a>
                            </h5>

                            <p class="text-gray-600 text-sm mb-4">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                <?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>
                                <?php if (strlen($location['address']) > 60) echo '...'; ?>
                            </p>

                            <div class="flex justify-between items-center">
                                <div>
                                    <?php if ($location['total_reviews'] > 0): ?>
                                        <?php echo star_rating($location['average_rating']); ?>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-sm">Belum ada rating</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-gray-600 text-sm">
                                    <i class="fas fa-comment mr-1"></i>
                                    <?php echo $location['total_reviews']; ?>
                                </span>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 pb-6">
                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                class="block w-full text-center px-4 py-3 gradient-primary text-white font-bold rounded-lg hover-lift transition">
                                <i class="fas fa-info-circle mr-1"></i>Lihat Detail
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- No Results -->
            <div class="text-center py-12">
                <i class="fas fa-search fa-4x text-gray-400 mb-4"></i>
                <h4 class="font-bold text-2xl text-gray-900 mb-4">Tidak ada hasil untuk "<?php echo htmlspecialchars($query); ?>"</h4>
                <p class="text-gray-600 mb-6">
                    Coba kata kunci yang berbeda atau lebih umum
                </p>
                <a href="<?php echo BASE_URL; ?>kedai" class="inline-block px-6 py-3 gradient-primary text-white font-bold rounded-lg hover-lift transition">
                    <i class="fas fa-store mr-2"></i>Lihat Semua Kedai
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State / Popular Locations -->
        <div class="text-center mb-8">
            <i class="fas fa-fire text-yellow-500 text-5xl mb-4"></i>
            <h4 class="font-bold text-2xl text-gray-900 mb-3">Kedai Populer</h4>
            <p class="text-gray-600">Belum tahu mau cari apa? Lihat kedai-kedai populer di bawah ini</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $popular = get_top_locations(6);
            foreach ($popular as $location):
            ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover-lift transition h-full">
                    <div class="relative h-48">
                        <?php
                        $reviewImage = db_fetch(
                            "SELECT ri.* FROM review_images ri JOIN reviews r ON ri.review_id = r.review_id WHERE r.location_id = ? AND ri.file_path IS NOT NULL ORDER BY r.upvotes DESC, r.created_at DESC LIMIT 1",
                            [$location['location_id']]
                        );
                        if ($reviewImage && !empty($reviewImage['file_path'])) {
                            $imgSrc = BASE_URL . 'get_image.php?path=' . urlencode($reviewImage['file_path']);
                        } else {
                            $placeholderText = htmlspecialchars($location['name']);
                            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="180">'
                                . '<rect width="100%" height="100%" fill="#6c757d" />'
                                . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"'
                                . ' font-family="Inter, Arial, sans-serif" font-size="18" fill="#ffffff">'
                                . $placeholderText
                                . '</text></svg>';
                            $imgSrc = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                        }
                        ?>
                        <img src="<?php echo $imgSrc; ?>"
                            class="w-full h-full object-cover"
                            alt="<?php echo htmlspecialchars($location['name']); ?>">

                        <span class="absolute top-2 right-2 inline-flex items-center px-3 py-1 bg-yellow-400 text-gray-900 rounded-full text-sm font-bold">
                            <i class="fas fa-star mr-1"></i>
                            <?php echo number_format($location['average_rating'], 1); ?>
                        </span>
                    </div>

                    <div class="p-6">
                        <h5 class="font-bold text-xl mb-3">
                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                class="text-gray-900 hover:text-blue-600 transition">
                                <?php echo htmlspecialchars($location['name']); ?>
                            </a>
                        </h5>

                        <p class="text-gray-600 text-sm mb-4">
                            <i class="fas fa-map-marker-alt mr-1"></i>
                            <?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>...
                        </p>

                        <div class="flex justify-between items-center">
                            <div><?php echo star_rating($location['average_rating']); ?></div>
                            <span class="text-gray-600 text-sm">
                                <i class="fas fa-comment mr-1"></i>
                                <?php echo $location['total_reviews']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                            class="block w-full text-center px-4 py-3 gradient-primary text-white font-bold rounded-lg hover-lift transition">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>