Mie Time - Form Tambah Review<?php
                                /**
                                 * Mie Time - Form Tambah Review
                                 */

                                define('MIE_TIME', true);
                                require_once '../../config.php';
                                require_once '../../includes/db.php';
                                require_once '../../includes/functions.php';
                                require_once '../../includes/auth.php';

                                // Require login
                                require_login();

                                $location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

                                // Get location
                                $location = get_location_by_id($location_id);
                                if (!$location) {
                                    set_flash('error', 'Warung tidak ditemukan');
                                    redirect('warung');
                                }

                                $errors = [];

                                // Process form submission
                                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

                                    // Check if user already reviewed
                                    $existing = db_fetch(
                                        "SELECT review_id FROM reviews WHERE location_id = ? AND user_id = ?",
                                        [$location_id, get_current_user_id()]
                                    );
                                    if ($existing) {
                                        $errors[] = 'Anda sudah pernah me-review warung ini';
                                    }

                                    // Auto moderation
                                    $moderation = auto_moderate_review($review_text);
                                    $status = 'approved';
                                    $moderation_reason = null;

                                    // Check new user (trial period)
                                    $user = get_user_by_id(get_current_user_id());
                                    if ($user['review_count'] < NEW_USER_REVIEW_THRESHOLD) {
                                        $status = 'pending';
                                        $moderation_reason = 'New user - under review';
                                    }

                                    // Check moderation flags
                                    if ($moderation['flagged']) {
                                        $status = 'pending';
                                        $moderation_reason = implode(', ', $moderation['reasons']);
                                    }

                                    // Insert if no errors
                                    if (empty($errors)) {
                                        db_begin_transaction();

                                        try {
                                            // Insert review
                                            $review_id = db_insert('reviews', [
                                                'location_id' => $location_id,
                                                'user_id' => get_current_user_id(),
                                                'rating' => $rating,
                                                'review_text' => $review_text,
                                                'status' => $status,
                                                'moderation_reason' => $moderation_reason
                                            ]);

                                            if (!$review_id) {
                                                throw new Exception('Failed to insert review');
                                            }

                                            // Process image uploads
                                            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                                                $upload_count = 0;

                                                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                                                    if ($upload_count >= MAX_IMAGES_PER_REVIEW) break;

                                                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                                                        $file = [
                                                            'name' => $_FILES['images']['name'][$key],
                                                            'type' => $_FILES['images']['type'][$key],
                                                            'tmp_name' => $tmp_name,
                                                            'error' => $_FILES['images']['error'][$key],
                                                            'size' => $_FILES['images']['size'][$key]
                                                        ];

                                                        $upload_result = upload_review_image($file, $review_id);
                                                        if ($upload_result['success']) {
                                                            $upload_count++;
                                                        }
                                                    }
                                                }

                                                // Award badge for first photo
                                                if ($upload_count > 0 && $user['review_count'] == 0) {
                                                    award_badge(get_current_user_id(), 2); // Fotografer Mie badge
                                                }
                                            }

                                            // Update user review count
                                            increment_review_count(get_current_user_id());

                                            // Award points
                                            $new_points = $user['points'] + POINTS_PER_REVIEW;
                                            update_user_points(get_current_user_id(), $new_points);

                                            // Check and award badges
                                            check_and_award_badges(get_current_user_id());

                                            // Create notification
                                            $notif_msg = $status === 'approved'
                                                ? "Review Anda di <strong>{$location['name']}</strong> telah dipublikasikan! +" . POINTS_PER_REVIEW . " poin"
                                                : "Review Anda di <strong>{$location['name']}</strong> sedang ditinjau moderator";

                                            create_notification(get_current_user_id(), $notif_msg, "warung/$location_id");

                                            db_commit();

                                            set_flash('success', $status === 'approved'
                                                ? 'Review berhasil dipublikasikan! Terima kasih atas kontribusinya.'
                                                : 'Review Anda sedang ditinjau oleh moderator. Terima kasih!');

                                            redirect('warung/' . $location_id);
                                        } catch (Exception $e) {
                                            db_rollback();
                                            $errors[] = 'Terjadi kesalahan saat menyimpan review: ' . $e->getMessage();
                                        }
                                    }
                                }

                                $page_title = 'Tulis Review - ' . $location['name'];
                                include '../../includes/header.php';
                                ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Location Info -->
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

            <!-- Header -->
            <div class="mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-star text-warning me-2"></i>Tulis Review
                </h2>
                <p class="text-muted">
                    Bagikan pengalaman Anda dan bantu orang lain!
                    Anda akan mendapatkan <strong><?php echo POINTS_PER_REVIEW; ?> poin</strong>.
                </p>
            </div>

            <!-- Error Alerts -->
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

            <!-- Form -->
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data" id="reviewForm">
                        <!-- Rating -->
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
                            <input type="hidden" name="rating" id="ratingValue" value="0" required>
                            <small class="text-muted">Klik bintang untuk memberikan rating</small>
                            <div id="ratingText" class="mt-2 fw-bold text-primary"></div>
                        </div>

                        <!-- Review Text -->
                        <div class="mb-4">
                            <label for="review_text" class="form-label fw-bold">
                                <i class="fas fa-comment text-primary me-2"></i>Review Anda
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="review_text" name="review_text"
                                rows="6" placeholder="Ceritakan pengalaman Anda..."
                                required minlength="20" maxlength="1000"><?php echo htmlspecialchars($review_text ?? ''); ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Minimal 20 karakter, maksimal 1000 karakter</small>
                                <small class="text-muted">
                                    <span id="charCount">0</span>/1000
                                </small>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label for="images" class="form-label fw-bold">
                                <i class="fas fa-camera text-success me-2"></i>Foto (Opsional)
                            </label>
                            <input type="file" class="form-control" id="images" name="images[]"
                                accept="image/jpeg,image/jpg,image/png"
                                multiple onchange="previewImages()">
                            <small class="text-muted">
                                Maksimal <?php echo MAX_IMAGES_PER_REVIEW; ?> foto, ukuran maksimal 5MB per foto
                            </small>

                            <!-- Image Preview -->
                            <div id="imagePreview" class="mt-3"></div>
                        </div>

                        <!-- Guidelines -->
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-2">
                                <i class="fas fa-info-circle me-2"></i>Panduan Menulis Review
                            </h6>
                            <ul class="mb-0 small">
                                <li>Tulis review yang jujur dan membantu</li>
                                <li>Fokus pada rasa, porsi, pelayanan, dan kebersihan</li>
                                <li>Hindari kata-kata kasar atau tidak pantas</li>
                                <li>Jangan promosikan warung lain atau cantumkan kontak pribadi</li>
                            </ul>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Review
                            </button>
                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $location_id; ?>"
                                class="btn btn-outline-secondary btn-lg">
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
                if (index < rating) {
                    s.style.color = '#ffc107';
                } else {
                    s.style.color = '#ddd';
                }
            });
        });
    });

    document.getElementById('ratingStars').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingValue.value);
        stars.forEach((s, index) => {
            if (index < currentRating) {
                s.style.color = '#ffc107';
            } else {
                s.style.color = '#ddd';
            }
        });
    });

    // Character counter
    const reviewText = document.getElementById('review_text');
    const charCount = document.getElementById('charCount');

    reviewText.addEventListener('input', function() {
        charCount.textContent = this.value.length;

        if (this.value.length > 1000) {
            charCount.classList.add('text-danger');
        } else {
            charCount.classList.remove('text-danger');
        }
    });

    // Image preview
    function previewImages() {
        const preview = document.getElementById('imagePreview');
        const files = document.getElementById('images').files;

        preview.innerHTML = '';

        if (files.length > MAX_IMAGES_PER_REVIEW) {
            alert(`Maksimal ${MAX_IMAGES_PER_REVIEW} foto`);
            document.getElementById('images').value = '';
            return;
        }

        Array.from(files).forEach((file, index) => {
            // Check file size
            if (file.size > <?php echo MAX_FILE_SIZE; ?>) {
                alert(`File ${file.name} terlalu besar (max 5MB)`);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'position-relative d-inline-block me-2 mb-2';
                div.innerHTML = `
                <img src="${e.target.result}" class="shadow-sm">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle"
                        onclick="this.parentElement.remove()" style="width: 30px; height: 30px; padding: 0;">
                    <i class="fas fa-times"></i>
                </button>
            `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    // Form validation
    document.getElementById('reviewForm').addEventListener('submit', function(e) {
        const rating = parseInt(ratingValue.value);
        const text = reviewText.value.trim();

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
</script>

<?php include '../../includes/footer.php'; ?>