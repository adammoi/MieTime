<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Get user ID from URL or current user
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (is_logged_in() ? get_current_user_id() : 0);

if ($user_id <= 0) {
    set_flash('error', 'User tidak ditemukan');
    redirect('');
}

$user = get_user_by_id($user_id);
if (!$user) {
    set_flash('error', 'User tidak ditemukan');
    redirect('');
}

$is_own_profile = is_logged_in() && get_current_user_id() == $user_id;

// Get user stats
$my_reviews = get_reviews_by_user($user_id);
$my_badges = get_user_badges($user_id);
$my_rank = get_user_rank($user_id);

// Get total upvotes
$total_upvotes = db_fetch("SELECT SUM(upvotes) as total FROM reviews WHERE user_id = ?", [$user_id]);

$page_title = $user['username'] . ' - Profil';
include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Profile Header -->
    <div class="bg-white rounded-2xl shadow-lg mb-6">
        <div class="p-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-2 text-center">
                    <div class="relative inline-block">
                        <i class="fas fa-user-circle text-blue-600" style="font-size: 6rem;"></i>
                        <?php if ($my_rank): ?>
                            <span class="absolute bottom-0 right-0 inline-flex items-center px-2 py-1 bg-yellow-400 text-gray-900 font-bold rounded-full text-sm">
                                #<?php echo $my_rank['user_rank']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="md:col-span-7">
                    <h2 class="text-3xl font-bold mb-2">
                        <?php echo htmlspecialchars($user['username']); ?>
                        <?php echo get_role_badge($user['role']); ?>
                    </h2>
                    <p class="text-gray-600 mb-4">
                        <i class="fas fa-calendar mr-2"></i>
                        Bergabung sejak <?php echo format_date_id($user['created_at']); ?>
                    </p>

                    <div class="flex gap-6">
                        <div>
                            <strong class="text-2xl font-bold text-gray-900"><?php echo $user['review_count']; ?></strong>
                            <small class="block text-gray-600">Review</small>
                        </div>
                        <div>
                            <strong class="text-2xl font-bold text-gray-900"><?php echo number_format($user['points']); ?></strong>
                            <small class="block text-gray-600">Poin</small>
                        </div>
                        <div>
                            <strong class="text-2xl font-bold text-gray-900"><?php echo $total_upvotes['total'] ?? 0; ?></strong>
                            <small class="block text-gray-600">Upvotes</small>
                        </div>
                        <div>
                            <strong class="text-2xl font-bold text-gray-900"><?php echo count($my_badges); ?></strong>
                            <small class="block text-gray-600">Lencana</small>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3">
                    <?php if ($is_own_profile): ?>
                        <a href="<?php echo BASE_URL; ?>dashboard" class="block w-full px-6 py-3 gradient-primary text-white font-semibold rounded-lg text-center hover:shadow-lg transition mb-2">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>badges" class="block w-full px-6 py-3 border border-blue-600 text-blue-600 font-semibold rounded-lg text-center hover:bg-blue-50 transition">
                            <i class="fas fa-award mr-2"></i>Lencana Saya
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Reviews -->
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h5 class="text-lg font-bold">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Semua Review (<?php echo count($my_reviews); ?>)
                    </h5>
                </div>
                <div class="p-6">
                    <?php if (empty($my_reviews)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-comment-slash text-5xl text-gray-400 mb-4"></i>
                            <p class="text-gray-600">Belum ada review</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($my_reviews as $review):
                            $review_images = get_review_images($review['review_id']);
                        ?>
                            <div class="review-item border-b border-gray-200 pb-4 mb-4 last:border-b-0">
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

                                <p class="mb-2 text-gray-700"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                                <!-- Review Images -->
                                <?php if (!empty($review_images)): ?>
                                    <div class="flex gap-2 mb-2 flex-wrap">
                                        <?php foreach ($review_images as $image): ?>
                                            <img src="<?php echo BASE_URL . 'get_image.php?path=' . urlencode($image['file_path']); ?>"
                                                class="rounded-lg cursor-pointer hover:opacity-90 transition" style="width: 80px; height: 80px; object-fit: cover;"
                                                onclick="window.open(this.src, '_blank')">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex gap-4 items-center text-sm">
                                    <span class="text-green-600">
                                        <i class="fas fa-thumbs-up mr-1"></i><?php echo $review['upvotes']; ?>
                                    </span>
                                    <span class="text-red-600">
                                        <i class="fas fa-thumbs-down mr-1"></i><?php echo $review['downvotes']; ?>
                                    </span>
                                    <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold <?php echo $review['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo $review['status']; ?>
                                    </span>

                                    <?php if ($is_own_profile && $review['status'] === 'pending'): ?>
                                        <a href="<?php echo BASE_URL; ?>reviews/edit/<?php echo $review['review_id']; ?>"
                                            class="ml-auto px-4 py-1 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Rank Card -->
            <?php if ($my_rank): ?>
                <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
                    <div class="p-6 gradient-primary text-white text-center">
                        <h3 class="text-xl font-bold mb-2">Peringkat</h3>
                        <div class="text-6xl font-bold mb-4">
                            #<?php echo $my_rank['user_rank']; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>leaderboard" class="inline-block px-6 py-2 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition">
                            <i class="fas fa-trophy mr-2"></i>Lihat Leaderboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Badges -->
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="px-6 py-4 bg-yellow-400">
                    <h6 class="font-bold text-gray-900">
                        <i class="fas fa-award mr-2"></i>Lencana (<?php echo count($my_badges); ?>)
                    </h6>
                </div>
                <div class="p-6">
                    <?php if (empty($my_badges)): ?>
                        <div class="text-center py-6">
                            <i class="fas fa-award text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-600 text-sm">Belum ada lencana</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($my_badges as $badge): ?>
                                <div class="text-center">
                                    <div class="badge-item" data-bs-toggle="tooltip"
                                        title="<?php echo htmlspecialchars($badge['badge_description']); ?>">
                                        <div class="badge-icon mx-auto mb-2 text-yellow-500" style="font-size: 2rem;">
                                            <i class="fas fa-award"></i>
                                        </div>
                                        <small class="font-semibold block text-gray-900 truncate">
                                            <?php echo htmlspecialchars($badge['badge_name']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($is_own_profile): ?>
                            <a href="<?php echo BASE_URL; ?>badges" class="block w-full px-4 py-2 bg-yellow-400 text-gray-900 font-semibold rounded-lg text-center hover:bg-yellow-500 transition mt-4">
                                Lihat Semua Lencana
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>