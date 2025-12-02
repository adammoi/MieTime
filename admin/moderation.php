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
                "kedai/{$review['location_id']}"
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
                "kedai/{$review['location_id']}"
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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-6">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="md:col-span-3 lg:col-span-3 admin-container">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-clipboard-check mr-2"></i>Moderasi Review
                </h2>
                <p class="text-gray-600">
                    <?php echo count($pending_reviews); ?> review menunggu moderasi
                </p>
            </div>

            <!-- Guidelines Card -->
            <div class="bg-blue-50 border-l-4 border-blue-400 rounded-lg p-6 mb-6">
                <h6 class="font-bold mb-3 text-gray-900">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    Panduan Moderasi
                </h6>
                <ul class="text-sm text-gray-700 space-y-1 ml-5 list-disc">
                    <li><strong>Setujui</strong> jika review jujur, membantu, dan sesuai guidelines</li>
                    <li><strong>Tolak</strong> jika mengandung spam, kata kasar, promosi, atau informasi palsu</li>
                    <li>Perhatikan <strong>moderation_reason</strong> dari filter otomatis</li>
                    <li>Review dari user baru (<3 review) otomatis masuk moderasi</li>
                </ul>
            </div>

            <!-- Pending Reviews -->
            <?php if (empty($pending_reviews)): ?>
                <div class="bg-white rounded-2xl shadow-lg">
                    <div class="text-center py-12">
                        <i class="fas fa-check-circle text-green-600 mb-4" style="font-size: 4rem;"></i>
                        <h4 class="font-bold text-2xl text-gray-900 mb-2">Semua Bersih!</h4>
                        <p class="text-gray-600">Tidak ada review yang perlu dimoderasi saat ini</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pending_reviews as $review):
                        $review_images = get_review_images($review['review_id']);
                    ?>
                        <div class="bg-white rounded-2xl shadow-lg p-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-2">
                                    <!-- Review Header -->
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h5 class="mb-1 text-xl font-bold">
                                                <i class="fas fa-user-circle text-blue-600 mr-2"></i>
                                                <?php echo htmlspecialchars($review['username']); ?>
                                            </h5>
                                            <p class="text-gray-600 mb-0">
                                                <i class="fas fa-store mr-1"></i>
                                                <strong><?php echo htmlspecialchars($review['location_name']); ?></strong>
                                            </p>
                                            <small class="text-gray-500">
                                                <?php echo time_ago($review['created_at']); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php echo star_rating($review['rating']); ?>
                                        </div>
                                    </div>

                                    <!-- Review Text -->
                                    <div class="mb-4">
                                        <p class="mb-2 text-gray-700"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                    </div>

                                    <!-- Review Images -->
                                    <?php if (!empty($review_images)): ?>
                                        <div class="mb-4">
                                            <div class="flex gap-2 flex-wrap">
                                                <?php foreach ($review_images as $image): ?>
                                                    <img src="<?php echo BASE_URL . 'get_image.php?path=' . urlencode($image['file_path']); ?>"
                                                        class="rounded-lg w-24 h-24 object-cover cursor-pointer hover:opacity-90 transition"
                                                        onclick="window.open(this.src, '_blank')">
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Moderation Reason -->
                                    <?php if (!empty($review['moderation_reason'])): ?>
                                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg">
                                            <strong class="text-gray-900"><i class="fas fa-exclamation-triangle mr-2 text-yellow-600"></i>Alasan Flagging:</strong><br>
                                            <span class="text-gray-700"><?php echo htmlspecialchars($review['moderation_reason']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div>
                                    <!-- Action Form -->
                                    <div class="bg-gray-50 rounded-xl p-6">
                                        <h6 class="font-bold mb-4 text-gray-900">Aksi Moderasi</h6>

                                        <!-- Approve -->
                                        <form method="POST" class="mb-2">
                                            <input type="hidden" name="review_id" value="<?php echo $review['review_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white font-bold rounded-lg hover:bg-green-700 transition">
                                                <i class="fas fa-check mr-2"></i>Setujui
                                            </button>
                                        </form>

                                        <!-- Reject -->
                                        <button type="button" class="w-full px-4 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition mb-4"
                                            data-bs-toggle="modal"
                                            data-bs-target="#rejectModal<?php echo $review['review_id']; ?>">
                                            <i class="fas fa-times mr-2"></i>Tolak
                                        </button>

                                        <hr class="my-4 border-gray-300">

                                        <!-- Quick Info -->
                                        <div class="text-sm text-gray-600 space-y-2">
                                            <div>
                                                <strong>Review ID:</strong> #<?php echo $review['review_id']; ?>
                                            </div>
                                            <div>
                                                <strong>User ID:</strong> #<?php echo $review['user_id']; ?>
                                            </div>
                                            <div>
                                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $review['location_id']; ?>"
                                                    target="_blank" class="block w-full px-4 py-2 border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition text-center">
                                                    <i class="fas fa-external-link-alt mr-2"></i>Lihat Kedai
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- Reject Modal -->
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
                                                    <option value="Tidak relevan dengan kedai">Tidak relevan dengan kedai</option>
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