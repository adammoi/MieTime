<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Search query
$search_query = isset($_GET['q']) ? clean_input($_GET['q']) : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Filter
$filter = $_GET['filter'] ?? 'rating'; // rating, newest, popular
$min_rating = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;

// Build query
$where = "status = 'active'";
$params = [];

// Search filter
if (!empty($search_query) && strlen($search_query) >= 3) {
    $where .= " AND (name LIKE ? OR address LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($min_rating > 0) {
    $where .= " AND average_rating >= ?";
    $params[] = $min_rating;
}

// Order by
switch ($filter) {
    case 'newest':
        $order = "created_at DESC";
        break;
    case 'popular':
        $order = "total_reviews DESC";
        break;
    case 'rating':
    default:
        $order = "average_rating DESC, total_reviews DESC";
        break;
}

// Get total count
$total_locations = db_count('locations', $where, $params);

// Get locations
$sql = "SELECT * FROM locations WHERE $where ORDER BY $order LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = $offset;
$locations = db_fetch_all($sql, $params);

$page_title = 'Daftar Kedai';
include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-8">
        <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-store text-blue-600 mr-2"></i>Jelajahi Kedai Mie Ayam
            </h2>
            <p class="text-gray-600">
                <?php if (!empty($search_query)): ?>
                    Hasil pencarian untuk "<span class="font-semibold"><?php echo htmlspecialchars($search_query); ?></span>"
                <?php else: ?>
                    Temukan <?php echo number_format($total_locations); ?> kedai mie ayam terbaik
                <?php endif; ?>
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo BASE_URL; ?>kedai/add" class="inline-flex items-center px-4 py-2 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Kedai Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form method="GET" action="" id="searchForm">
            <div class="flex flex-col md:flex-row gap-3 mb-4">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" name="q"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Cari nama kedai, lokasi, atau deskripsi..."
                        value="<?php echo htmlspecialchars($search_query); ?>">
                </div>
                <?php if (!empty($search_query)): ?>
                    <a href="<?php echo BASE_URL; ?>kedai" class="px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition inline-flex items-center justify-center">
                        <i class="fas fa-times mr-2"></i> Reset
                    </a>
                <?php endif; ?>
                <button type="submit" class="px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                    Cari
                </button>
            </div>

            <!-- Quick Filters -->
            <div class="flex flex-wrap gap-3 items-center">
                <span class="text-sm text-gray-600"><i class="fas fa-filter mr-1"></i> Filter Cepat:</span>

                <div class="inline-flex rounded-lg shadow-sm" role="group">
                    <input type="radio" class="sr-only" name="filter" value="rating"
                        id="filter_rating" <?php echo $filter === 'rating' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="filter_rating" class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-l-lg cursor-pointer <?php echo $filter === 'rating' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        <i class="fas fa-star mr-1"></i> Rating Tertinggi
                    </label>

                    <input type="radio" class="sr-only" name="filter" value="popular"
                        id="filter_popular" <?php echo $filter === 'popular' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="filter_popular" class="px-4 py-2 text-sm font-medium border-t border-b border-gray-300 cursor-pointer <?php echo $filter === 'popular' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        <i class="fas fa-fire mr-1"></i> Terpopuler
                    </label>

                    <input type="radio" class="sr-only" name="filter" value="newest"
                        id="filter_newest" <?php echo $filter === 'newest' ? 'checked' : ''; ?>
                        onchange="this.form.submit()">
                    <label for="filter_newest" class="px-4 py-2 text-sm font-medium border border-gray-300 rounded-r-lg cursor-pointer <?php echo $filter === 'newest' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        <i class="fas fa-clock mr-1"></i> Terbaru
                    </label>
                </div>

                <select name="min_rating" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    onchange="this.form.submit()">
                    <option value="0" <?php echo $min_rating === 0 ? 'selected' : ''; ?>>Semua Rating</option>
                    <option value="4" <?php echo $min_rating === 4 ? 'selected' : ''; ?>>⭐ 4+</option>
                    <option value="3" <?php echo $min_rating === 3 ? 'selected' : ''; ?>>⭐ 3+</option>
                    <option value="2" <?php echo $min_rating === 2 ? 'selected' : ''; ?>>⭐ 2+</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Results Count -->
    <div class="mb-6">
        <?php if ($total_locations > 0): ?>
            <p class="text-gray-600">
                <i class="fas fa-list-ul mr-2"></i>
                Menampilkan <span class="font-semibold"><?php echo number_format(min(ITEMS_PER_PAGE, $total_locations - $offset)); ?></span>
                dari <span class="font-semibold"><?php echo number_format($total_locations); ?></span> kedai
                <?php if (!empty($search_query)): ?>
                    untuk "<span class="font-semibold"><?php echo htmlspecialchars($search_query); ?></span>"
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Locations Grid -->
    <?php if (empty($locations)): ?>
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg text-center">
            <i class="fas fa-info-circle text-blue-500 text-3xl mb-3"></i>
            <p class="text-blue-700 font-medium">Tidak ada kedai yang ditemukan dengan filter ini.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($locations as $location): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover-lift">
                    <!-- Image -->
                    <div class="relative h-48">
                        <?php
                        // Try to fetch a review image for this location. Prefer images from the most-upvoted review.
                        $reviewImage = db_fetch(
                            "SELECT ri.* FROM review_images ri JOIN reviews r ON ri.review_id = r.review_id WHERE r.location_id = ? AND ri.file_path IS NOT NULL ORDER BY r.upvotes DESC, r.created_at DESC LIMIT 1",
                            [$location['location_id']]
                        );
                        if ($reviewImage && !empty($reviewImage['file_path'])) {
                            $imgSrc = BASE_URL . 'get_image.php?path=' . urlencode($reviewImage['file_path']);
                        } else {
                            // Local inline SVG placeholder to avoid external network calls
                            $placeholderText = htmlspecialchars($location['name']);
                            $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200">'
                                . '<rect width="100%" height="100%" fill="#6c757d" />'
                                . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"'
                                . ' font-family="Inter, Arial, sans-serif" font-size="18" fill="#ffffff">'
                                . $placeholderText
                                . '</text></svg>';
                            $imgSrc = 'data:image/svg+xml;utf8,' . rawurlencode($svg);
                        }
                        ?>
                        <img src="<?php echo $imgSrc; ?>"
                            class="w-full h-full object-cover"
                            alt="<?php echo htmlspecialchars($location['name']); ?>">

                        <!-- Rating Badge -->
                        <?php if ($location['total_reviews'] > 0): ?>
                            <span class="absolute top-3 right-3 bg-yellow-400 text-gray-900 px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                <i class="fas fa-star mr-1"></i>
                                <?php echo number_format($location['average_rating'], 1); ?>
                            </span>
                        <?php else: ?>
                            <span class="absolute top-3 right-3 bg-gray-500 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                Belum ada review
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <h5 class="text-lg font-bold text-gray-900 mb-2">
                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                class="hover:text-blue-600 transition">
                                <?php echo htmlspecialchars($location['name']); ?>
                            </a>
                        </h5>

                        <p class="text-gray-600 text-sm mb-4 flex items-start">
                            <i class="fas fa-map-marker-alt mr-2 mt-1 flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>
                                <?php if (strlen($location['address']) > 60) echo '...'; ?></span>
                        </p>

                        <!-- Stats -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <div>
                                <?php if ($location['total_reviews'] > 0): ?>
                                    <?php echo star_rating($location['average_rating']); ?>
                                <?php else: ?>
                                    <span class="text-gray-500 text-sm">Belum ada rating</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-gray-500 text-sm">
                                <i class="fas fa-comment mr-1"></i>
                                <?php echo $location['total_reviews']; ?> review
                            </span>
                        </div>
                    </div>

                    <!-- Card Footer -->
                    <div class="px-5 pb-5 flex gap-2">
                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                            class="flex-1 text-center px-4 py-2 border border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition">
                            <i class="fas fa-info-circle mr-1"></i>Detail
                        </a>
                        <?php if (is_logged_in()): ?>
                            <a href="<?php echo BASE_URL; ?>review/add/<?php echo $location['location_id']; ?>"
                                class="flex-1 text-center px-4 py-2 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition">
                                <i class="fas fa-star mr-1"></i>Review
                            </a>
                        <?php else: ?>
                            <button type="button" class="flex-1 px-4 py-2 gradient-primary text-white font-semibold rounded-lg hover:shadow-lg transition"
                                data-require-login
                                data-action-text="menulis review">
                                <i class="fas fa-star mr-1"></i>Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-8">
            <?php echo generate_pagination($total_locations, $page, BASE_URL . 'kedai?filter=' . $filter . '&min_rating=' . $min_rating . '&'); ?>
        </div>
    <?php endif; ?>
</div>

<style>
    /* Search suggestions dropdown */
    .suggestions-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-top: none;
        border-radius: 0 0 0.5rem 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        margin-top: -1px;
    }

    .suggestions-dropdown.show {
        display: block;
    }

    .suggestion-item {
        padding: 1rem;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background 0.2s;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-item:hover,
    .suggestion-item.active {
        background: #f9fafb;
    }

    .suggestion-name {
        font-weight: 600;
        color: #111827;
        margin-bottom: 0.25rem;
    }

    .suggestion-address {
        font-size: 0.875rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }

    .suggestion-meta {
        font-size: 0.75rem;
        color: #9ca3af;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="q"]');
        const searchForm = document.getElementById('searchForm');

        if (!searchInput) return;

        // Create suggestions dropdown
        const suggestionsDiv = document.createElement('div');
        suggestionsDiv.className = 'suggestions-dropdown';
        searchInput.parentElement.classList.add('relative');
        searchInput.parentElement.appendChild(suggestionsDiv);

        let debounceTimer;
        let selectedIndex = -1;

        // Search suggestions
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                suggestionsDiv.classList.remove('show');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`<?php echo BASE_URL; ?>api/search_suggestions.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.suggestions.length > 0) {
                            showSuggestions(data.suggestions);
                        } else {
                            suggestionsDiv.classList.remove('show');
                        }
                    })
                    .catch(err => {
                        console.error('Search suggestions error:', err);
                    });
            }, 300);
        });

        // Show suggestions
        function showSuggestions(suggestions) {
            selectedIndex = -1;
            suggestionsDiv.innerHTML = suggestions.map((s, idx) => `
            <div class="suggestion-item" data-index="${idx}" data-url="${s.url}">
                <div class="suggestion-name">${highlightText(s.name, searchInput.value)}</div>
                <div class="suggestion-address">
                    <i class="fas fa-map-marker-alt mr-2"></i>${s.address}
                </div>
                <div class="suggestion-meta">
                    <i class="fas fa-star text-yellow-500 mr-1"></i>${s.rating}
                    <span class="ml-3">
                        <i class="fas fa-comment mr-1"></i>${s.reviews} review
                    </span>
                </div>
            </div>
        `).join('');

            suggestionsDiv.classList.add('show');

            // Click handlers
            suggestionsDiv.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    window.location.href = this.dataset.url;
                });
            });
        }

        // Highlight matching text
        function highlightText(text, query) {
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<strong class="text-blue-600">$1</strong>');
        }

        // Keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            const items = suggestionsDiv.querySelectorAll('.suggestion-item');

            if (!suggestionsDiv.classList.contains('show') || items.length === 0) {
                return;
            }

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection(items);
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex].click();
            } else if (e.key === 'Escape') {
                suggestionsDiv.classList.remove('show');
            }
        });

        function updateSelection(items) {
            items.forEach((item, idx) => {
                if (idx === selectedIndex) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });

            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].scrollIntoView({
                    block: 'nearest'
                });
            }
        }

        // Close on click outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.classList.remove('show');
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>