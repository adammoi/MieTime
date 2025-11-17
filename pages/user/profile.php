<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

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

<div class="container my-5">
    <!-- Profile Header -->
    <div class="card shadow mb-4">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="position-relative d-inline-block">
                        <i class="fas fa-user-circle fa-5x text-primary"></i>
                        <?php if ($my_rank): ?>
                            <span class="position-absolute bottom-0 end-0 badge bg-warning text-dark"
                                style="font-size: 0.9rem;">
                                #<?php echo $my_rank['user_rank']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-7">
                    <h2 class="fw-bold mb-2">
                        <?php echo htmlspecialchars($user['username']); ?>
                        <?php echo get_role_badge($user['role']); ?>
                    </h2>
                    <p class="text-muted mb-3">
                        <i class="fas fa-calendar me-2"></i>
                        Bergabung sejak <?php echo format_date_id($user['created_at']); ?>
                    </p>

                    <div class="d-flex gap-4">
                        <div>
                            <strong><?php echo $user['review_count']; ?></strong>
                            <small class="text-muted d-block">Review</small>
                        </div>
                        <div>
                            <strong><?php echo number_format($user['points']); ?></strong>
                            <small class="text-muted d-block">Poin</small>
                        </div>
                        <div>
                            <strong><?php echo $total_upvotes['total'] ?? 0; ?></strong>
                            <small class="text-muted d-block">Upvotes</small>
                        </div>
                        <div>
                            <strong><?php echo count($my_badges); ?></strong>
                            <small class="text-muted d-block">Lencana</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 text-md-end">
                    <?php if ($is_own_profile): ?>
                        <a href="<?php echo BASE_URL; ?>dashboard" class="btn btn-primary mb-2 w-100">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="<?php echo BASE_URL; ?>badges" class="btn btn-outline-primary w-100">
                            <i class="fas fa-award me-2"></i>Lencana Saya
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Reviews -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star text-warning me-2"></i>
                        Semua Review (<?php echo count($my_reviews); ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($my_reviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Belum ada review</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($my_reviews as $review):
                            $review_images = get_review_images($review['review_id']);
                        ?>
                            <div class="review-item border-bottom pb-3 mb-3">
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

                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                                <!-- Review Images -->
                                <?php if (!empty($review_images)): ?>
                                    <div class="d-flex gap-2 mb-2 flex-wrap">
                                        <?php foreach ($review_images as $image): ?>
                                            <img src="<?php echo BASE_URL . 'get_image.php?path=' . urlencode($image['file_path']); ?>"
                                                class="rounded" style="width: 80px; height: 80px; object-fit: cover; cursor: pointer;"
                                                onclick="window.open(this.src, '_blank')">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex gap-3 align-items-center small">
                                    <span class="text-success">
                                        <i class="fas fa-thumbs-up me-1"></i><?php echo $review['upvotes']; ?>
                                    </span>
                                    <span class="text-danger">
                                        <i class="fas fa-thumbs-down me-1"></i><?php echo $review['downvotes']; ?>
                                    </span>
                                    <span class="badge bg-<?php echo $review['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                        <?php echo $review['status']; ?>
                                    </span>

                                    <?php if ($is_own_profile && $review['status'] === 'pending'): ?>
                                        <a href="<?php echo BASE_URL; ?>reviews/edit/<?php echo $review['review_id']; ?>"
                                            class="btn btn-sm btn-outline-primary ms-auto">
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
        <div class="col-lg-4">
            <!-- Rank Card -->
            <?php if ($my_rank): ?>
                <div class="card shadow-sm mb-4 bg-primary text-white">
                    <div class="card-body text-center">
                        <h3 class="mb-2">Peringkat</h3>
                        <div class="display-2 fw-bold mb-3">
                            #<?php echo $my_rank['user_rank']; ?>
                        </div>
                        <a href="<?php echo BASE_URL; ?>leaderboard" class="btn btn-light btn-sm">
                            <i class="fas fa-trophy me-2"></i>Lihat Leaderboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Badges -->
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-award me-2"></i>Lencana (<?php echo count($my_badges); ?>)
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($my_badges)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-award fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0 small">Belum ada lencana</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($my_badges as $badge): ?>
                                <div class="col-6 text-center">
                                    <div class="badge-item" data-bs-toggle="tooltip"
                                        title="<?php echo htmlspecialchars($badge['badge_description']); ?>">
                                        <div class="badge-icon mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                            <i class="fas fa-award"></i>
                                        </div>
                                        <small class="fw-bold d-block text-truncate">
                                            <?php echo htmlspecialchars($badge['badge_name']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($is_own_profile): ?>
                            <a href="<?php echo BASE_URL; ?>badges" class="btn btn-sm btn-warning w-100 mt-3">
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