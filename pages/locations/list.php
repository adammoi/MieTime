<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

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

<div class="container my-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="fw-bold">
                <i class="fas fa-store text-primary me-2"></i>Daftar Kedai Mie Ayam
            </h2>
            <p class="text-muted">Temukan <?php echo number_format($total_locations); ?> kedai mie ayam terbaik</p>
        </div>
        <div class="col-md-6 text-md-end">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo BASE_URL; ?>kedai/add" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Tambah Kedai Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter & Sort -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Urutkan</label>
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="rating" <?php echo $filter === 'rating' ? 'selected' : ''; ?>>
                            Rating Tertinggi
                        </option>
                        <option value="popular" <?php echo $filter === 'popular' ? 'selected' : ''; ?>>
                            Paling Populer
                        </option>
                        <option value="newest" <?php echo $filter === 'newest' ? 'selected' : ''; ?>>
                            Terbaru
                        </option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Rating Minimal</label>
                    <select name="min_rating" class="form-select" onchange="this.form.submit()">
                        <option value="0" <?php echo $min_rating === 0 ? 'selected' : ''; ?>>Semua Rating</option>
                        <option value="4" <?php echo $min_rating === 4 ? 'selected' : ''; ?>>4+ ⭐</option>
                        <option value="3" <?php echo $min_rating === 3 ? 'selected' : ''; ?>>3+ ⭐</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <a href="<?php echo BASE_URL; ?>kedai" class="btn btn-outline-secondary d-block">
                        <i class="fas fa-redo me-2"></i>Reset Filter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Locations Grid -->
    <?php if (empty($locations)): ?>
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            Tidak ada kedai yang ditemukan dengan filter ini.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($locations as $location): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm hover-card">
                        <!-- Image -->
                        <div class="position-relative" style="height: 200px; overflow: hidden;">
                            <img src="https://via.placeholder.com/400x200/6c757d/ffffff?text=<?php echo urlencode($location['name']); ?>"
                                class="card-img-top w-100 h-100" style="object-fit: cover;"
                                alt="<?php echo htmlspecialchars($location['name']); ?>">

                            <!-- Rating Badge -->
                            <?php if ($location['total_reviews'] > 0): ?>
                                <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                                    <i class="fas fa-star me-1"></i>
                                    <?php echo number_format($location['average_rating'], 1); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary position-absolute top-0 end-0 m-2">
                                    Belum ada review
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                    class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </a>
                            </h5>

                            <p class="card-text text-muted small mb-3">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>
                                <?php if (strlen($location['address']) > 60) echo '...'; ?>
                            </p>

                            <!-- Stats -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($location['total_reviews'] > 0): ?>
                                        <?php echo star_rating($location['average_rating']); ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Belum ada rating</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-muted small">
                                    <i class="fas fa-comment me-1"></i>
                                    <?php echo $location['total_reviews']; ?> review
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="card-footer bg-white border-0 d-flex justify-content-between">
                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location['location_id']; ?>"
                                class="btn btn-outline-primary btn-sm flex-grow-1 me-2">
                                <i class="fas fa-info-circle me-1"></i>Detail
                            </a>
                            <?php if (is_logged_in()): ?>
                                <a href="<?php echo BASE_URL; ?>review/add/<?php echo $location['location_id']; ?>"
                                    class="btn btn-primary btn-sm flex-grow-1">
                                    <i class="fas fa-star me-1"></i>Review
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary btn-sm flex-grow-1"
                                    data-require-login
                                    data-action-text="menulis review">
                                    <i class="fas fa-star me-1"></i>Review
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php
        echo generate_pagination($total_locations, $page, BASE_URL . 'kedai?filter=' . $filter . '&min_rating=' . $min_rating . '&');
        ?>
    <?php endif; ?>
</div>

<style>
    .hover-card {
        transition: all 0.3s ease;
    }

    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }
</style>

<?php include '../../includes/footer.php'; ?>