<?php

if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Require admin or moderator
require_admin_or_moderator();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $review_id = (int)$_POST['review_id'];
    $action = $_POST['action'];

    $review = get_review_by_id($review_id);
    if ($review) {
        if ($action === 'approve') {
            db_update(
                'reviews',
                ['status' => 'approved', 'moderation_reason' => null],
                'review_id = :review_id',
                ['review_id' => $review_id]
            );

            create_notification(
                $review['user_id'],
                "Review Anda di <strong>{$review['location_name']}</strong> telah disetujui!",
                "warung/{$review['location_id']}"
            );

            set_flash('success', 'Review berhasil disetujui');
        } elseif ($action === 'reject') {
            $reason = clean_input($_POST['reason'] ?? 'Tidak memenuhi guidelines');

            db_update(
                'reviews',
                ['status' => 'rejected', 'moderation_reason' => $reason],
                'review_id = :review_id',
                ['review_id' => $review_id]
            );

            create_notification(
                $review['user_id'],
                "Review Anda di <strong>{$review['location_name']}</strong> ditolak: $reason",
                "warung/{$review['location_id']}"
            );

            set_flash('success', 'Review ditolak');
        }
    }

    redirect('admin/moderation');
}

// Get pending reviews
$pending_reviews = get_pending_reviews(100);

$page_title = 'Moderasi Review';
include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>Admin Menu
                    </h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?php echo BASE_URL; ?>admin" class="list-group-item list-group-item-action">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/moderation" class="list-group-item list-group-item-action active">
                        <i class="fas fa-clipboard-check me-2"></i>Moderasi
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/users" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Pengguna
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/locations" class="list-group-item list-group-item-action">
                        <i class="fas fa-store me-2"></i>Warung
                    </a>
                    <a href="<?php echo BASE_URL; ?>" class="list-group-item list-group-item-action text-primary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Situs
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">
                        <i class="fas fa-clipboard-check me-2"></i>Moderasi Review
                    </h2>
                    <p class="text-muted mb-0">
                        <?php echo count($pending_reviews); ?> review menunggu moderasi
                    </p>
                </div>
            </div>

            <!-- Guidelines Card -->
            <div class="card shadow-sm mb-4 bg-info bg-opacity-10 border-info">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">
                        <i class="fas fa-info-circle text-info me-2"></i>
                        Panduan Moderasi
                    </h6>
                    <ul class="mb-0 small">
                        <li><strong>Setujui</strong> jika review jujur, membantu, dan sesuai guidelines</li>
                        <li><strong>Tolak</strong> jika mengandung spam, kata kasar, promosi, atau informasi palsu</li>
                        <li>Perhatikan <strong>moderation_reason</strong> dari filter otomatis</li>
                        <li>Review dari user baru (<3 review) otomatis masuk moderasi</li>
                    </ul>
                </div>
            </div>

            <!-- Pending Reviews -->
            <?php if (empty($pending_reviews)): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="fw-bold mb-2">Semua Bersih!</h4>
                        <p class="text-muted">Tidak ada review yang perlu dimoderasi saat ini</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($pending_reviews as $review):
                        $review_images = get_review_images($review['review_id']);
                    ?>
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <!-- Review Header -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h5 class="mb-1">
                                                        <i class="fas fa-user-circle text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($review['username']); ?>
                                                    </h5>
                                                    <p class="text-muted mb-0">
                                                        <i class="fas fa-store me-1"></i>
                                                        <strong><?php echo htmlspecialchars($review['location_name']); ?></strong>
                                                    </p>
                                                    <small class="text-muted">
                                                        <?php echo time_ago($review['created_at']); ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?php echo star_rating($review['rating']); ?>
                                                </div>
                                            </div>

                                            <!-- Review Text -->
                                            <div class="mb-3">
                                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                            </div>

                                            <!-- Review Images -->
                                            <?php if (!empty($review_images)): ?>
                                                <div class="mb-3">
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <?php foreach ($review_images as $image): ?>
                                                            <img src="<?php echo BASE_URL . 'get_image.php?path=' . urlencode($image['file_path']); ?>"
                                                                class="rounded" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;"
                                                                onclick="window.open(this.src, '_blank')">
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Moderation Reason -->
                                            <?php if (!empty($review['moderation_reason'])): ?>
                                                <div class="alert alert-warning mb-0">
                                                    <strong><i class="fas fa-exclamation-triangle me-2"></i>Alasan Flagging:</strong><br>
                                                    <?php echo htmlspecialchars($review['moderation_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="col-md-4">
                                            <!-- Action Form -->
                                            <div class="card bg-light">
                                                <div class="card-body">
                                                    <h6 class="fw-bold mb-3">Aksi Moderasi</h6>

                                                    <!-- Approve -->
                                                    <form method="POST" class="mb-2">
                                                        <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                                        <input type="hidden" name="action" value="approve">
                                                        <button type="submit" class="btn btn-success w-100">
                                                            <i class="fas fa-check me-2"></i>Setujui
                                                        </button>
                                                    </form>

                                                    <!-- Reject -->
                                                    <button type="button" class="btn btn-danger w-100 mb-3"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#rejectModal<?php echo $review['review_id']; ?>">
                                                        <i class="fas fa-times me-2"></i>Tolak
                                                    </button>

                                                    <hr>

                                                    <!-- Quick Info -->
                                                    <div class="small">
                                                        <div class="mb-2">
                                                            <strong>Review ID:</strong> #<?php echo $review['review_id']; ?>
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>User ID:</strong> #<?php echo $review['user_id']; ?>
                                                        </div>
                                                        <div>
                                                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $review['location_id']; ?>"
                                                                target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                                                <i class="fas fa-external-link-alt me-2"></i>Lihat Warung
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?php echo $review['review_id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak Review</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="action" value="reject">

                                            <div class="mb-3">
                                                <label class="form-label">Alasan Penolakan</label>
                                                <select class="form-select mb-2" name="reason" required>
                                                    <option value="">Pilih alasan...</option>
                                                    <option value="Mengandung kata kasar">Mengandung kata kasar</option>
                                                    <option value="Spam atau promosi">Spam atau promosi</option>
                                                    <option value="Informasi tidak akurat">Informasi tidak akurat</option>
                                                    <option value="Tidak relevan dengan warung">Tidak relevan dengan warung</option>
                                                    <option value="Melanggar guidelines">Melanggar guidelines</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-danger">Tolak Review</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>