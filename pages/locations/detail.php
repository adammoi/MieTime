<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$location_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get location
$location = get_location_by_id($location_id);

if (!$location) {
    set_flash('error', 'Kedai tidak ditemukan');
    redirect('kedai');
}

// Get reviews
$reviews = get_reviews_by_location($location_id, 'approved');

// Check if user has bookmarked
$is_bookmarked = false;
if (is_logged_in()) {
    $is_bookmarked = db_exists(
        'bookmarks',
        'user_id = :user_id AND location_id = :location_id',
        ['user_id' => get_current_user_id(), 'location_id' => $location_id]
    );
}

$page_title = $location['name'];
$page_description = substr($location['address'], 0, 150);

include '../../includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Location Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($location['name']); ?></h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($location['address']); ?>
                            </p>
                        </div>
                        <?php if (is_logged_in()): ?>
                            <button class="btn btn-outline-danger" id="bookmarkBtn"
                                data-location-id="<?php echo $location_id; ?>"
                                data-bookmarked="<?php echo $is_bookmarked ? '1' : '0'; ?>">
                                <i class="fas fa-heart<?php echo $is_bookmarked ? '' : '-broken'; ?>"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Rating Summary -->
                    <div class="d-flex align-items-center mb-3">
                        <?php if ($location['total_reviews'] > 0): ?>
                            <div class="me-4">
                                <h3 class="mb-0 fw-bold"><?php echo number_format($location['average_rating'], 1); ?></h3>
                                <div><?php echo star_rating($location['average_rating']); ?></div>
                                <small class="text-muted"><?php echo $location['total_reviews']; ?> review</small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>Belum ada review. Jadilah yang pertama!
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo BASE_URL; ?>review/add/<?php echo $location_id; ?>"
                                class="btn btn-primary">
                                <i class="fas fa-star me-2"></i>Tulis Review
                            </a>
                            <a href="<?php echo BASE_URL; ?>kedai/claim/<?php echo $location_id; ?>"
                                class="btn btn-outline-secondary">
                                <i class="fas fa-building me-2"></i>Klaim Kedai Ini
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-primary"
                                data-require-login
                                data-action-text="menulis review">
                                <i class="fas fa-star me-2"></i>Tulis Review
                            </button>
                            <button type="button" class="btn btn-outline-secondary"
                                data-require-login
                                data-action-text="klaim kedai ini">
                                <i class="fas fa-building me-2"></i>Klaim Kedai Ini
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <?php if ($location['latitude'] && $location['longitude']): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map me-2"></i>Lokasi</h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="map" style="height: 300px;"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-comments me-2"></i>
                        Review (<?php echo count($reviews); ?>)
                    </h5>
                    <select class="form-select form-select-sm" style="width: auto;" id="sortReviews">
                        <option value="newest">Terbaru</option>
                        <option value="highest">Rating Tertinggi</option>
                        <option value="helpful">Paling Membantu</option>
                    </select>
                </div>
                <div class="card-body" id="reviewsList">
                    <?php if (empty($reviews)): ?>
                        <p class="text-center text-muted py-5">
                            Belum ada review. Jadilah yang pertama menulis review!
                        </p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review):
                            $review_images = get_review_images($review['review_id']);
                            $user_vote = is_logged_in() ? get_user_vote($review['review_id'], get_current_user_id()) : null;
                        ?>
                            <div class="review-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold">
                                            <i class="fas fa-user-circle text-muted me-1"></i>
                                            <?php echo htmlspecialchars($review['username']); ?>
                                            <?php if ($review['role'] === 'verified_owner'): ?>
                                                <span class="badge bg-success">Verified Owner</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted"><?php echo time_ago($review['created_at']); ?></small>
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
                                                class="rounded" style="width: 100px; height: 100px; object-fit: cover; cursor: pointer;"
                                                onclick="window.open(this.src, '_blank')">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Vote Buttons -->
                                <div class="d-flex gap-2 align-items-center">
                                    <?php if (is_logged_in()): ?>
                                        <button class="btn btn-sm btn-outline-success vote-btn"
                                            data-review-id="<?php echo $review['review_id']; ?>"
                                            data-vote-type="1"
                                            <?php echo ($user_vote && $user_vote['vote_type'] == 1) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-thumbs-up"></i>
                                            <span><?php echo $review['upvotes']; ?></span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger vote-btn"
                                            data-review-id="<?php echo $review['review_id']; ?>"
                                            data-vote-type="-1"
                                            <?php echo ($user_vote && $user_vote['vote_type'] == -1) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-thumbs-down"></i>
                                            <span><?php echo $review['downvotes']; ?></span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small">
                                            <i class="fas fa-thumbs-up me-1"></i><?php echo $review['upvotes']; ?> helpful
                                        </span>
                                    <?php endif; ?>

                                    <?php if (is_logged_in() && (get_current_user_id() == $review['user_id'] || is_admin_or_moderator())): ?>
                                        <div class="dropdown ms-auto">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="reviewMenu<?php echo (int)$review['review_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="reviewMenu<?php echo (int)$review['review_id']; ?>">
                                                <li>
                                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>pages/reviews/edit.php?review_id=<?php echo (int)$review['review_id']; ?>">Edit</a>
                                                </li>
                                            </ul>
                                        </div>
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
            <!-- Info Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><i class="fas fa-star text-warning me-2"></i>Rating:</strong><br>
                        <?php if ($location['total_reviews'] > 0): ?>
                            <?php echo number_format($location['average_rating'], 1); ?> / 5.0
                        <?php else: ?>
                            Belum ada rating
                        <?php endif; ?>
                    </p>
                    <p class="mb-2">
                        <strong><i class="fas fa-comment text-primary me-2"></i>Total Review:</strong><br>
                        <?php echo $location['total_reviews']; ?> review
                    </p>
                    <p class="mb-0">
                        <strong><i class="fas fa-calendar text-success me-2"></i>Ditambahkan:</strong><br>
                        <?php echo format_date_id($location['created_at']); ?>
                    </p>
                </div>
            </div>

            <!-- Share Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Bagikan</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary btn-sm" onclick="shareToFacebook()">
                            <i class="fab fa-facebook me-2"></i>Facebook
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="shareToTwitter()">
                            <i class="fab fa-twitter me-2"></i>Twitter
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="shareToWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>WhatsApp
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">
                            <i class="fas fa-link me-2"></i>Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($location['latitude'] && $location['longitude']): ?>
    <script>
        function initDetailMap() {
            const lat = <?php echo $location['latitude']; ?>;
            const lng = <?php echo $location['longitude']; ?>;

            const map = L.map('map').setView([lat, lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // FontAwesome divIcon marker helper (keeps marker visible without image assets)
            const faIconHtml = '<i class="fas fa-map-marker-alt" style="color:#d00;font-size:28px;line-height:28px;"></i>';

            function createMapMarker(lat, lng) {
                const icon = L.divIcon({
                    className: 'fa-map-marker-divicon',
                    html: faIconHtml,
                    iconSize: [28, 28],
                    iconAnchor: [14, 28]
                });
                return L.marker([lat, lng], {
                    icon: icon
                }).addTo(map);
            }

            const marker = createMapMarker(lat, lng);
            marker.bindPopup('<strong><?php echo addslashes(htmlspecialchars($location['name'])); ?></strong><br><?php echo addslashes(htmlspecialchars($location['address'])); ?>').openPopup();
        }

        window.addEventListener('load', function() {
            if (typeof L === 'undefined') {
                setTimeout(function() {
                    if (typeof L !== 'undefined') {
                        initDetailMap();
                    } else {
                        console.error('Leaflet not available for detail map');
                    }
                }, 250);
            } else {
                initDetailMap();
            }
        });
    </script>
<?php endif; ?>

<script>
    // Vote functionality
    document.querySelectorAll('.vote-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reviewId = this.dataset.reviewId;
            const voteType = this.dataset.voteType;

            fetch('<?php echo BASE_URL; ?>api/vote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        review_id: reviewId,
                        vote_type: voteType
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Gagal memberikan vote');
                    }
                });
        });
    });

    // Bookmark functionality
    document.getElementById('bookmarkBtn')?.addEventListener('click', function() {
        const locationId = this.dataset.locationId;
        const isBookmarked = this.dataset.bookmarked === '1';

        fetch('<?php echo BASE_URL; ?>api/bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    location_id: locationId,
                    action: isBookmarked ? 'remove' : 'add'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    });

    // Share functions
    function shareToFacebook() {
        window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank');
    }

    function shareToTwitter() {
        window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href) + '&text=' + encodeURIComponent('<?php echo $location['name']; ?>'), '_blank');
    }

    function shareToWhatsApp() {
        window.open('https://wa.me/?text=' + encodeURIComponent('<?php echo $location['name']; ?> - ' + window.location.href), '_blank');
    }

    function copyLink() {
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link berhasil disalin!');
        });
    }
</script>

<?php include '../../includes/footer.php'; ?>