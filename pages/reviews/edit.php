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

$review_id = isset($_GET['review_id']) ? (int)$_GET['review_id'] : 0;

// Fetch review
$review = get_review_by_id($review_id);
if (!$review) {
    set_flash('error', 'Review tidak ditemukan');
    redirect('kedai');
}

// Authorization: owner or moderator/admin
$current_user = get_current_user_id();
if ($review['user_id'] !== $current_user && !is_admin_or_moderator()) {
    set_flash('error', 'Anda tidak memiliki izin untuk mengedit review ini');
    redirect('kedai/' . (int)$review['location_id']);
}

$location = get_location_by_id($review['location_id']);
if (!$location) {
    set_flash('error', 'Kedai terkait tidak ditemukan');
    redirect('kedai');
}

$errors = [];

// Process submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token CSRF tidak valid';
    }

    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text = clean_input($_POST['review_text'] ?? '');

    // Validation
    if ($rating < 1 || $rating > 5) {
        $errors[] = 'Rating harus antara 1-5 bintang';
    }
    if (empty($review_text)) {
        $errors[] = 'Review tidak boleh kosong';
    } elseif (strlen($review_text) < 20) {
        $errors[] = 'Review minimal 20 karakter';
    } elseif (strlen($review_text) > 1000) {
        $errors[] = 'Review maksimal 1000 karakter';
    }

    // moderation
    $moderation = auto_moderate_review($review_text);
    $status = $review['status']; // keep existing unless flagged
    $moderation_reason = $review['moderation_reason'];

    // If flagged, set to pending
    if ($moderation['flagged']) {
        $status = 'pending';
        $moderation_reason = implode(', ', $moderation['reasons']);
    }

    if (empty($errors)) {
        db_begin_transaction();
        try {
            $ok = db_update('reviews', [
                'rating' => $rating,
                'review_text' => $review_text,
                'status' => $status,
                'moderation_reason' => $moderation_reason
            ], 'review_id = :review_id', ['review_id' => $review_id]);

            if ($ok === false) throw new Exception('Gagal memperbarui review');

            // Handle additional image uploads (do not remove existing images)
            $existing_images = get_review_images($review_id);
            $existing_count = count($existing_images);
            $allowed_new = MAX_IMAGES_PER_REVIEW - $existing_count;

            if ($allowed_new > 0 && isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $upload_count = 0;
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($upload_count >= $allowed_new) break;
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'type' => $_FILES['images']['type'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];

                        $upload_result = upload_review_image($file, $review_id);
                        if (!empty($upload_result['success'])) {
                            $upload_count++;
                        }
                    }
                }
            }

            db_commit();

            set_flash('success', 'Review berhasil diperbarui');
            redirect('kedai/' . (int)$review['location_id']);
        } catch (Exception $e) {
            db_rollback();
            $errors[] = 'Terjadi kesalahan saat memperbarui review: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Review - ' . $location['name'];
include '../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary text-white rounded p-3 me-3">
                            <i class="fas fa-store fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($location['name']); ?></h5>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($location['address']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-edit me-2"></i>Edit Review
                </h2>
                <p class="text-muted">Perbarui rating atau isi review Anda.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data" id="editReviewForm">
                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-star text-warning me-2"></i>Rating
                                <span class="text-danger">*</span>
                            </label>
                            <div class="rating-stars mb-2" id="ratingStars">
                                <i class="far fa-star star" data-rating="1"></i>
                                <i class="far fa-star star" data-rating="2"></i>
                                <i class="far fa-star star" data-rating="3"></i>
                                <i class="far fa-star star" data-rating="4"></i>
                                <i class="far fa-star star" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="<?php echo (int)($review['rating'] ?? 0); ?>" required>
                            <small class="text-muted">Klik bintang untuk memberikan rating</small>
                            <div id="ratingText" class="mt-2 fw-bold text-primary"></div>
                        </div>

                        <div class="mb-4">
                            <label for="review_text" class="form-label fw-bold">
                                <i class="fas fa-comment text-primary me-2"></i>Review Anda
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="review_text" name="review_text" rows="6" placeholder="Ceritakan pengalaman Anda..." required minlength="20" maxlength="1000"><?php echo htmlspecialchars($review['review_text'] ?? ''); ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Minimal 20 karakter, maksimal 1000 karakter</small>
                                <small class="text-muted"><span id="charCount"><?php echo strlen($review['review_text'] ?? ''); ?></span>/1000</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="images" class="form-label fw-bold">
                                <i class="fas fa-camera text-success me-2"></i>Foto (Opsional)
                            </label>
                            <input type="file" class="form-control" id="images" name="images[]" accept="image/jpeg,image/jpg,image/png" multiple onchange="previewImages()">
                            <small class="text-muted">Anda dapat menambahkan foto baru. Maksimal <?php echo MAX_IMAGES_PER_REVIEW; ?> foto total.</small>

                            <div id="imagePreview" class="mt-3"></div>

                            <?php $existing_images = get_review_images($review_id);
                            if (!empty($existing_images)): ?>
                                <div class="mt-3">
                                    <label class="form-label">Foto saat ini</label>
                                    <div id="existingImages">
                                        <?php foreach ($existing_images as $img): ?>
                                            <div class="d-inline-block position-relative me-2 mb-2 review-image-item" data-image-id="<?php echo (int)$img['image_id']; ?>">
                                                <img src="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($img['file_path']); ?>" alt="" style="width:100px;height:100px;object-fit:cover;border-radius:6px;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 btn-delete-review-image" title="Hapus foto">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo (int)$review['location_id']; ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rating-stars .star {
        font-size: 2.5rem;
        cursor: pointer;
        color: #ddd;
        transition: all 0.2s ease;
    }

    .rating-stars .star:hover,
    .rating-stars .star.active {
        color: #ffc107;
        transform: scale(1.2);
    }

    #imagePreview img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Rating Stars
        const stars = document.querySelectorAll('.star');
        const ratingValue = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');

        const ratingLabels = {
            1: '⭐ Sangat Buruk',
            2: '⭐⭐ Kurang',
            3: '⭐⭐⭐ Cukup',
            4: '⭐⭐⭐⭐ Bagus',
            5: '⭐⭐⭐⭐⭐ Sangat Bagus!'
        };

        // Initialize star UI from existing value
        (function initStars() {
            const cur = parseInt(ratingValue.value) || 0;
            stars.forEach((s, i) => {
                if (i < cur) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'active');
                } else {
                    s.classList.remove('fas', 'active');
                    s.classList.add('far');
                }
            });
            if (cur) ratingText.textContent = ratingLabels[cur] || '';
        })();

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingValue.value = rating;
                ratingText.textContent = ratingLabels[rating];

                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'active');
                    } else {
                        s.classList.remove('fas', 'active');
                        s.classList.add('far');
                    }
                });
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    s.style.color = index < rating ? '#ffc107' : '#ddd';
                });
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            const currentRating = parseInt(ratingValue.value) || 0;
            stars.forEach((s, index) => {
                s.style.color = index < currentRating ? '#ffc107' : '#ddd';
            });
        });

        // Character counter
        const reviewText = document.getElementById('review_text');
        const charCount = document.getElementById('charCount');
        reviewText.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            if (this.value.length > 1000) charCount.classList.add('text-danger');
            else charCount.classList.remove('text-danger');
        });

        // Image preview
        function previewImages() {
            const preview = document.getElementById('imagePreview');
            const files = document.getElementById('images').files;
            preview.innerHTML = '';
            if (files.length > <?php echo MAX_IMAGES_PER_REVIEW; ?>) {
                alert(`Maksimal <?php echo MAX_IMAGES_PER_REVIEW; ?> foto`);
                document.getElementById('images').value = '';
                return;
            }
            Array.from(files).forEach((file) => {
                if (file.size > <?php echo MAX_FILE_SIZE; ?>) {
                    alert(`File ${file.name} terlalu besar (max <?php echo (MAX_FILE_SIZE / (1024 * 1024)); ?>MB)`);
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'position-relative d-inline-block me-2 mb-2';
                    div.innerHTML = `\n                <img src="${e.target.result}" class="shadow-sm">\n                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle" onclick="this.parentElement.remove()" style="width:30px;height:30px;padding:0;"><i class="fas fa-times"></i></button>\n            `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        // Expose previewImages to global scope (input onchange uses it)
        window.previewImages = previewImages;

        // Delete existing review image (AJAX)
        document.querySelectorAll('.btn-delete-review-image').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                if (!confirm('Hapus foto ini?')) return;
                var container = e.target.closest('.review-image-item');
                var imageId = container.getAttribute('data-image-id');
                var tokenInput = document.querySelector('input[name="' + '<?php echo CSRF_TOKEN_NAME; ?>' + '"]');
                var token = tokenInput ? tokenInput.value : '';

                var fd = new FormData();
                fd.append('image_id', imageId);
                fd.append('<?php echo CSRF_TOKEN_NAME; ?>', token);

                fetch('<?php echo BASE_URL; ?>api/review_image.php', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        if (json.error) {
                            alert(json.error);
                            return;
                        }
                        // Remove element
                        container.remove();
                    })
                    .catch(function(err) {
                        console.error(err);
                        alert('Gagal menghapus foto');
                    });
            });
        });

        // Simple form validation
        document.getElementById('editReviewForm').addEventListener('submit', function(e) {
            const rating = parseInt(document.getElementById('ratingValue').value);
            const text = document.getElementById('review_text').value.trim();
            if (rating < 1 || rating > 5) {
                e.preventDefault();
                alert('Silakan pilih rating terlebih dahulu');
                return false;
            }
            if (text.length < 20) {
                e.preventDefault();
                alert('Review minimal 20 karakter');
                return false;
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>