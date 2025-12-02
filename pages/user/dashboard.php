<?php

if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$user = get_user_by_id($user_id);

// Get user stats
$my_reviews = get_reviews_by_user($user_id, 5);
$my_badges = get_user_badges($user_id);
$my_rank = get_user_rank($user_id);

// Get bookmarks
$bookmarks = db_fetch_all("
    SELECT l.* FROM bookmarks b
    JOIN locations l ON b.location_id = l.location_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
", [$user_id]);

// Get total upvotes received
$total_upvotes = db_fetch("
    SELECT SUM(upvotes) as total 
    FROM reviews 
    WHERE user_id = ?
", [$user_id]);

$page_title = 'Dashboard';
include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Welcome Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-4xl font-bold mb-2">
                <i class="fas fa-home text-blue-600 mr-2"></i>
                Selamat Datang, <?php echo htmlspecialchars($user['username']); ?>!
            </h2>
            <p class="text-gray-600">
                Kelola review dan lihat aktivitas Anda di sini
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <?php echo get_role_badge($user['role']); ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-2xl shadow-lg text-center p-6 hover-lift">
            <i class="fas fa-star text-5xl text-yellow-500 mb-4"></i>
            <h3 class="text-3xl font-bold mb-1"><?php echo $user['review_count']; ?></h3>
            <p class="text-gray-600">Total Review</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg text-center p-6 hover-lift">
            <i class="fas fa-coins text-5xl text-green-500 mb-4"></i>
            <h3 class="text-3xl font-bold mb-1"><?php echo number_format($user['points']); ?></h3>
            <p class="text-gray-600">Total Poin</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg text-center p-6 hover-lift">
            <i class="fas fa-trophy text-5xl text-blue-600 mb-4"></i>
            <h3 class="text-3xl font-bold mb-1">#<?php echo $my_rank['user_rank'] ?? 'N/A'; ?></h3>
            <p class="text-gray-600">Peringkat</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg text-center p-6 hover-lift">
            <i class="fas fa-thumbs-up text-5xl text-red-500 mb-4"></i>
            <h3 class="text-3xl font-bold mb-1"><?php echo $total_upvotes['total'] ?? 0; ?></h3>
            <p class="text-gray-600">Total Upvotes</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column -->
        <div class="lg:col-span-2">
            <!-- My Reviews -->
            <div class="bg-white rounded-2xl shadow-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-bold">
                        <i class="fas fa-comment-dots text-blue-600 mr-2"></i>
                        Review Saya Terbaru
                    </h5>
                    <a href="<?php echo BASE_URL; ?>profile" class="px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                        Lihat Semua
                    </a>
                </div>
                <div class="p-6">
                    <?php if (empty($my_reviews)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comment-slash text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600 mb-4">Anda belum menulis review</p>
                            <a href="<?php echo BASE_URL; ?>kedai" class="inline-block px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                                <i class="fas fa-search mr-2"></i>Cari Kedai
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($my_reviews as $review): ?>
                                <div class="py-4 first:pt-0 last:pb-0">
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h6 class="font-semibold mb-1">
                                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $review['location_id']; ?>"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline">
                                                    <?php echo htmlspecialchars($review['location_name']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-gray-500">
                                                <?php echo time_ago($review['created_at']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php echo star_rating($review['rating']); ?>
                                        </div>
                                    </div>
                                    <p class="mb-2 text-gray-600">
                                        <?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?>...
                                    </p>
                                    <div class="flex gap-4 text-sm text-gray-500">
                                        <span>
                                            <i class="fas fa-thumbs-up text-green-600 mr-1"></i>
                                            <?php echo $review['upvotes']; ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-thumbs-down text-red-600 mr-1"></i>
                                            <?php echo $review['downvotes']; ?>
                                        </span>
                                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $review['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $review['status']; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bookmarks -->
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-bold">
                        <i class="fas fa-heart text-red-500 mr-2"></i>
                        Bookmark Saya
                    </h5>
                </div>
                <div class="p-6">
                    <?php if (empty($bookmarks)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-heart-broken text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600">Belum ada bookmark</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($bookmarks as $location): ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <h6 class="font-semibold mb-2">
                                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                            class="text-blue-600 hover:text-blue-800 hover:underline">
                                            <?php echo htmlspecialchars($location['name']); ?>
                                        </a>
                                    </h6>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-600">
                                            <?php echo star_rating($location['average_rating']); ?>
                                        </span>
                                        <span class="text-gray-500">
                                            <?php echo $location['total_reviews']; ?> review
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="lg:col-span-1">
            <!-- Badges -->
            <div class="bg-white rounded-2xl shadow-lg mb-6">
                <div class="px-6 py-4 gradient-primary flex justify-between items-center">
                    <h6 class="font-bold text-white">
                        <i class="fas fa-award mr-2"></i>Lencana Saya
                    </h6>
                    <a href="<?php echo BASE_URL; ?>badges" class="px-3 py-1 border-2 border-white text-white rounded-lg hover:bg-white hover:text-blue-600 transition text-sm">
                        Semua
                    </a>
                </div>
                <div class="p-6 text-center">
                    <?php if (empty($my_badges)): ?>
                        <i class="fas fa-award text-5xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 mb-1">Belum ada lencana</p>
                        <small class="text-gray-500">Tulis review untuk mendapatkan lencana!</small>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-4">
                            <?php foreach (array_slice($my_badges, 0, 4) as $badge): ?>
                                <div class="badge-item" data-bs-toggle="tooltip"
                                    title="<?php echo htmlspecialchars($badge['badge_description']); ?>">
                                    <div class="badge-icon mb-2 text-3xl text-yellow-500">
                                        <i class="fas fa-award"></i>
                                    </div>
                                    <small class="font-semibold block text-gray-900"><?php echo htmlspecialchars($badge['badge_name']); ?></small>
                                    <small class="text-gray-500 text-xs">
                                        <?php echo format_date_id($badge['earned_at']); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($my_badges) > 4): ?>
                            <a href="<?php echo BASE_URL; ?>badges" class="inline-block px-6 py-2 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition mt-4">
                                Lihat Semua (<?php echo count($my_badges); ?>)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="px-6 py-4 gradient-success">
                    <h6 class="font-bold text-white">
                        <i class="fas fa-bolt mr-2"></i>Aksi Cepat
                    </h6>
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        <a href="<?php echo BASE_URL; ?>kedai/add" class="block px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition text-center">
                            <i class="fas fa-plus-circle mr-2"></i>Tambah Kedai Baru
                        </a>
                        <a href="<?php echo BASE_URL; ?>kedai" class="block px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition text-center">
                            <i class="fas fa-search mr-2"></i>Cari Kedai
                        </a>
                        <a href="<?php echo BASE_URL; ?>leaderboard" class="block px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition text-center">
                            <i class="fas fa-trophy mr-2"></i>Lihat Leaderboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile" class="block px-4 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition text-center">
                            <i class="fas fa-user mr-2"></i>Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>