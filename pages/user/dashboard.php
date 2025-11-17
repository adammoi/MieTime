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

<div class="container my-5">
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="fw-bold mb-2">
                <i class="fas fa-home text-primary me-2"></i>
                Selamat Datang, <?php echo htmlspecialchars($user['username']); ?>!
            </h2>
            <p class="text-muted">
                Kelola review dan lihat aktivitas Anda di sini
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <?php echo get_role_badge($user['role']); ?>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <h3 class="fw-bold mb-1"><?php echo $user['review_count']; ?></h3>
                    <p class="text-muted mb-0">Total Review</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-coins fa-3x text-success mb-3"></i>
                    <h3 class="fw-bold mb-1"><?php echo number_format($user['points']); ?></h3>
                    <p class="text-muted mb-0">Total Poin</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-trophy fa-3x text-primary mb-3"></i>
                    <h3 class="fw-bold mb-1">#<?php echo $my_rank['user_rank'] ?? 'N/A'; ?></h3>
                    <p class="text-muted mb-0">Peringkat</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm text-center h-100">
                <div class="card-body">
                    <i class="fas fa-thumbs-up fa-3x text-danger mb-3"></i>
                    <h3 class="fw-bold mb-1"><?php echo $total_upvotes['total'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Upvotes</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8 mb-4">
            <!-- My Reviews -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comment-dots text-primary me-2"></i>
                        Review Saya Terbaru
                    </h5>
                    <a href="<?php echo BASE_URL; ?>profile" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($my_reviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-3">Anda belum menulis review</p>
                            <a href="<?php echo BASE_URL; ?>kedai" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Cari Kedai
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($my_reviews as $review): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $review['location_id']; ?>"
                                                    class="text-decoration-none">
                                                    <?php echo htmlspecialchars($review['location_name']); ?>
                                                </a>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo time_ago($review['created_at']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php echo star_rating($review['rating']); ?>
                                        </div>
                                    </div>
                                    <p class="mb-2 text-muted">
                                        <?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?>...
                                    </p>
                                    <div class="d-flex gap-3 small text-muted">
                                        <span>
                                            <i class="fas fa-thumbs-up text-success me-1"></i>
                                            <?php echo $review['upvotes']; ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-thumbs-down text-danger me-1"></i>
                                            <?php echo $review['downvotes']; ?>
                                        </span>
                                        <span class="badge bg-<?php echo $review['status'] === 'approved' ? 'success' : 'warning'; ?>">
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
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-heart text-danger me-2"></i>
                        Bookmark Saya
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($bookmarks)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-heart-broken fa-2x text-muted mb-3"></i>
                            <p class="text-muted mb-0">Belum ada bookmark</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($bookmarks as $location): ?>
                                <div class="col-md-6">
                                    <div class="card border">
                                        <div class="card-body p-3">
                                            <h6 class="mb-1">
                                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                                    class="text-decoration-none">
                                                    <?php echo htmlspecialchars($location['name']); ?>
                                                </a>
                                            </h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <?php echo star_rating($location['average_rating']); ?>
                                                </small>
                                                <small class="text-muted">
                                                    <?php echo $location['total_reviews']; ?> review
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Badges -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-award me-2"></i>Lencana Saya
                    </h6>
                    <a href="<?php echo BASE_URL; ?>badges" class="btn btn-sm btn-outline-light">
                        Semua
                    </a>
                </div>
                <div class="card-body text-center">
                    <?php if (empty($my_badges)): ?>
                        <i class="fas fa-award fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Belum ada lencana</p>
                        <small class="text-muted">Tulis review untuk mendapatkan lencana!</small>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach (array_slice($my_badges, 0, 4) as $badge): ?>
                                <div class="col-6">
                                    <div class="badge-item" data-bs-toggle="tooltip"
                                        title="<?php echo htmlspecialchars($badge['badge_description']); ?>">
                                        <div class="badge-icon mb-2">
                                            <i class="fas fa-award"></i>
                                        </div>
                                        <small class="fw-bold d-block"><?php echo htmlspecialchars($badge['badge_name']); ?></small>
                                        <small class="text-muted">
                                            <?php echo format_date_id($badge['earned_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($my_badges) > 4): ?>
                            <a href="<?php echo BASE_URL; ?>badges" class="btn btn-sm btn-primary mt-3">
                                Lihat Semua (<?php echo count($my_badges); ?>)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Aksi Cepat
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>kedai/add" class="btn btn-outline-primary">
                            <i class="fas fa-plus-circle me-2"></i>Tambah Kedai Baru
                        </a>
                        <a href="<?php echo BASE_URL; ?>kedai" class="btn btn-outline-primary">
                            <i class="fas fa-search me-2"></i>Cari Kedai
                        </a>
                        <a href="<?php echo BASE_URL; ?>leaderboard" class="btn btn-outline-primary">
                            <i class="fas fa-trophy me-2"></i>Lihat Leaderboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>profile" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>Edit Profil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>