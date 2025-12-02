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

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Location Header -->
            <div class="bg-white rounded-2xl shadow-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <h2 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($location['name']); ?></h2>
                            <p class="text-gray-600 flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
                                <?php echo htmlspecialchars($location['address']); ?>
                            </p>
                        </div>
                        <?php if (is_logged_in()): ?>
                            <button class="px-4 py-2 border-2 border-red-500 text-red-500 rounded-lg hover:bg-red-500 hover:text-white transition" id="bookmarkBtn"
                                data-location-id="<?php echo $location_id; ?>"
                                data-bookmarked="<?php echo $is_bookmarked ? '1' : '0'; ?>">
                                <i class="fas fa-heart<?php echo $is_bookmarked ? '' : '-broken'; ?>"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Rating Summary -->
                    <div class="flex items-center mb-4">
                        <?php if ($location['total_reviews'] > 0): ?>
                            <div class="mr-6">
                                <h3 class="text-4xl font-bold mb-1"><?php echo number_format($location['average_rating'], 1); ?></h3>
                                <div class="mb-1"><?php echo star_rating($location['average_rating']); ?></div>
                                <small class="text-gray-500"><?php echo $location['total_reviews']; ?> review</small>
                            </div>
                        <?php else: ?>
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded w-full">
                                <p class="text-blue-700">
                                    <i class="fas fa-info-circle mr-2"></i>Belum ada review. Jadilah yang pertama!
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo BASE_URL; ?>review/add/<?php echo $location_id; ?>"
                                class="px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                                <i class="fas fa-star mr-2"></i>Tulis Review
                            </a>
                            <a href="<?php echo BASE_URL; ?>kedai/claim/<?php echo $location_id; ?>"
                                class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                                <i class="fas fa-building mr-2"></i>Klaim Kedai Ini
                            </a>
                        <?php else: ?>
                            <button type="button" class="px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition"
                                data-require-login
                                data-action-text="menulis review">
                                <i class="fas fa-star mr-2"></i>Tulis Review
                            </button>
                            <button type="button" class="px-6 py-3 border border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition"
                                data-require-login
                                data-action-text="klaim kedai ini">
                                <i class="fas fa-building mr-2"></i>Klaim Kedai Ini
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <?php if ($location['latitude'] && $location['longitude']): ?>
                <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h5 class="text-lg font-bold"><i class="fas fa-map mr-2 text-blue-600"></i>Lokasi</h5>
                    </div>
                    <div class="p-0">
                        <div id="map" style="height: 300px;"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reviews Section -->
            <div class="bg-white rounded-2xl shadow-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h5 class="text-lg font-bold">
                        <i class="fas fa-comments mr-2 text-blue-600"></i>
                        Review (<?php echo count($reviews); ?>)
                    </h5>
                    <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" id="sortReviews">
                        <option value="newest">Terbaru</option>
                        <option value="highest">Rating Tertinggi</option>
                        <option value="helpful">Paling Membantu</option>
                    </select>
                </div>
                <div class="p-6" id="reviewsList">
                    <?php if (empty($reviews)): ?>
                        <p class="text-center text-gray-500 py-12">
                            Belum ada review. Jadilah yang pertama menulis review!
                        </p>
                    <?php else: ?>
                        <?php foreach ($reviews as $review):
                            $review_images = get_review_images($review['review_id']);
                            $user_vote = is_logged_in() ? get_user_vote($review['review_id'], get_current_user_id()) : null;
                        ?>
                            <div class="review-item border-b border-gray-200 pb-4 mb-4 last:border-b-0">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h6 class="font-bold text-gray-900 mb-1">
                                            <i class="fas fa-user-circle text-gray-400 mr-1"></i>
                                            <?php echo htmlspecialchars($review['username']); ?>
                                            <?php if ($review['role'] === 'verified_owner'): ?>
                                                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Verified Owner</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-gray-500"><?php echo time_ago($review['created_at']); ?></small>
                                    </div>
                                    <div>
                                        <?php echo star_rating($review['rating']); ?>
                                    </div>
                                </div>

                                <p class="mb-3 text-gray-700"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>

                                <!-- Review Images -->
                                <?php if (!empty($review_images)): ?>
                                    <div class="flex gap-2 mb-3 flex-wrap">
                                        <?php foreach ($review_images as $image): ?>
                                            <img src="<?php echo BASE_URL . 'get_image.php?path=' . urlencode($image['file_path']); ?>"
                                                class="rounded-lg cursor-pointer hover:opacity-90 transition" style="width: 100px; height: 100px; object-fit: cover;"
                                                onclick="window.open(this.src, '_blank')">
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Vote Buttons -->
                                <div class="flex gap-2 items-center">
                                    <?php if (is_logged_in()): ?>
                                        <button class="px-3 py-1 border border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition vote-btn"
                                            data-review-id="<?php echo $review['review_id']; ?>"
                                            data-vote-type="1"
                                            <?php echo ($user_vote && $user_vote['vote_type'] == 1) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-thumbs-up"></i>
                                            <span><?php echo $review['upvotes']; ?></span>
                                        </button>
                                        <button class="px-3 py-1 border border-red-500 text-red-600 rounded-lg hover:bg-red-50 transition vote-btn"
                                            data-review-id="<?php echo $review['review_id']; ?>"
                                            data-vote-type="-1"
                                            <?php echo ($user_vote && $user_vote['vote_type'] == -1) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-thumbs-down"></i>
                                            <span><?php echo $review['downvotes']; ?></span>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-sm">
                                            <i class="fas fa-thumbs-up mr-1"></i><?php echo $review['upvotes']; ?> helpful
                                        </span>
                                    <?php endif; ?>

                                    <?php if (is_logged_in() && (get_current_user_id() == $review['user_id'] || is_admin_or_moderator())): ?>
                                        <div class="ml-auto relative inline-block text-left" x-data="{ open: false }">
                                            <button @click="open = !open" class="px-3 py-1 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                                                <a href="<?php echo BASE_URL; ?>pages/reviews/edit.php?review_id=<?php echo (int)$review['review_id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Edit</a>
                                            </div>
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
        <div class="lg:col-span-1">
            <!-- Info Card -->
            <div class="bg-white rounded-2xl shadow-lg mb-6 overflow-hidden">
                <div class="px-6 py-4 gradient-primary">
                    <h6 class="font-bold text-white"><i class="fas fa-info-circle mr-2"></i>Informasi</h6>
                </div>
                <div class="p-6">
                    <p class="mb-4">
                        <strong class="block text-gray-700 mb-1">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>Rating:
                        </strong>
                        <span class="text-gray-600">
                            <?php if ($location['total_reviews'] > 0): ?>
                                <?php echo number_format($location['average_rating'], 1); ?> / 5.0
                            <?php else: ?>
                                Belum ada rating
                            <?php endif; ?>
                        </span>
                    </p>
                    <p class="mb-4">
                        <strong class="block text-gray-700 mb-1">
                            <i class="fas fa-comment text-blue-600 mr-2"></i>Total Review:
                        </strong>
                        <span class="text-gray-600"><?php echo $location['total_reviews']; ?> review</span>
                    </p>
                    <p class="mb-0">
                        <strong class="block text-gray-700 mb-1">
                            <i class="fas fa-calendar text-green-600 mr-2"></i>Ditambahkan:
                        </strong>
                        <span class="text-gray-600"><?php echo format_date_id($location['created_at']); ?></span>
                    </p>
                </div>
            </div>

            <!-- Share Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h6 class="font-bold text-gray-900"><i class="fas fa-share-alt mr-2 text-blue-600"></i>Bagikan</h6>
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        <button class="w-full px-4 py-2 border border-blue-500 text-blue-600 rounded-lg hover:bg-blue-50 transition" onclick="shareToFacebook()">
                            <i class="fab fa-facebook mr-2"></i>Facebook
                        </button>
                        <button class="w-full px-4 py-2 border border-sky-500 text-sky-600 rounded-lg hover:bg-sky-50 transition" onclick="shareToTwitter()">
                            <i class="fab fa-twitter mr-2"></i>Twitter
                        </button>
                        <button class="w-full px-4 py-2 border border-green-500 text-green-600 rounded-lg hover:bg-green-50 transition" onclick="shareToWhatsApp()">
                            <i class="fab fa-whatsapp mr-2"></i>WhatsApp
                        </button>
                        <button class="w-full px-4 py-2 border border-gray-400 text-gray-600 rounded-lg hover:bg-gray-50 transition" onclick="copyLink()">
                            <i class="fas fa-link mr-2"></i>Copy Link
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