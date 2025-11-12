<?php


define('MIE_TIME', true);
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$page_title = 'Beranda';
$page_description = 'Temukan warung mie ayam terbaik di sekitar Anda';

// Get featured locations
$top_locations = get_top_locations(6);
$recent_reviews = db_fetch_all("
SELECT r.*, u.username, l.name as location_name, l.location_id
FROM reviews r
JOIN users u ON r.user_id = u.user_id
JOIN locations l ON r.location_id = l.location_id
WHERE r.status = 'approved'
ORDER BY r.created_at DESC
LIMIT 6
");


include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-bowl-food me-3"></i>Mie Time
                </h1>
                <p class="lead mb-4">
                    Platform komunitas untuk menemukan dan berbagi review warung mie ayam terbaik di Indonesia
                </p>
                <div class="d-grid gap-2 d-md-flex">
                    <a href="<?php echo BASE_URL; ?>warung" class="btn btn-light btn-lg">
                        <i class="fas fa-search me-2"></i>Jelajahi Warung
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?php echo BASE_URL; ?>register" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <img src="<?php echo ASSETS_URL; ?>img/hero-mie.jpg" alt="Mie Ayam"
                    class="img-fluid rounded shadow"
                    onerror="this.src='https://via.placeholder.com/600x400/0d6efd/ffffff?text=Mie+Ayam'">
            </div>
        </div>
    </div>
</div>

<!-- Search Bar -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <form action="<?php echo BASE_URL; ?>search" method="GET" class="card shadow">
                <div class="card-body">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control"
                            placeholder="Cari warung mie ayam atau lokasi...">
                        <button type="submit" class="btn btn-primary">Cari</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="container mb-5">
    <div class="row text-center">
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-store fa-3x text-primary mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format(db_count('locations', 'status = "active"')); ?></h3>
                    <p class="text-muted mb-0">Warung Terdaftar</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-star fa-3x text-warning mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format(db_count('reviews', 'status = "approved"')); ?></h3>
                    <p class="text-muted mb-0">Review Ditulis</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format(db_count('users')); ?></h3>
                    <p class="text-muted mb-0">Pengguna Aktif</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-images fa-3x text-danger mb-3"></i>
                    <h3 class="fw-bold"><?php echo number_format(db_count('review_images')); ?></h3>
                    <p class="text-muted mb-0">Foto Dibagikan</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Locations -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="fas fa-trophy text-warning me-2"></i>Warung Terbaik
        </h2>
        <a href="<?php echo BASE_URL; ?>warung" class="btn btn-outline-primary">
            Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="row">
        <?php foreach ($top_locations as $location): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm hover-shadow">
                    <div class="card-img-top bg-light" style="height: 200px; position: relative;">
                        <img src="https://via.placeholder.com/400x200/6c757d/ffffff?text=<?php echo urlencode($location['name']); ?>"
                            class="w-100 h-100" style="object-fit: cover;" alt="<?php echo htmlspecialchars($location['name']); ?>">
                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                            <i class="fas fa-star me-1"></i><?php echo number_format($location['average_rating'], 1); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $location['location_id']; ?>"
                                class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($location['name']); ?>
                            </a>
                        </h5>
                        <p class="card-text text-muted small">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars(substr($location['address'], 0, 50)); ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-muted small">
                                <?php echo star_rating($location['average_rating']); ?>
                            </span>
                            <span class="text-muted small">
                                <?php echo $location['total_reviews']; ?> review
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0">
                        <a href="<?php echo BASE_URL; ?>warung/<?php echo $location['location_id']; ?>"
                            class="btn btn-outline-primary btn-sm w-100">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Recent Reviews -->
<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="fas fa-comment-dots text-primary me-2"></i>Review Terbaru
        </h2>
    </div>

    <div class="row">
        <?php foreach ($recent_reviews as $review): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-0">
                                    <i class="fas fa-user-circle text-muted me-1"></i>
                                    <?php echo htmlspecialchars($review['username']); ?>
                                </h6>
                                <small class="text-muted"><?php echo time_ago($review['created_at']); ?></small>
                            </div>
                            <span class="badge bg-warning text-dark">
                                <?php echo star_rating($review['rating']); ?>
                            </span>
                        </div>
                        <h6 class="mt-2">
                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $review['location_id']; ?>"
                                class="text-decoration-none">
                                <?php echo htmlspecialchars($review['location_name']); ?>
                            </a>
                        </h6>
                        <p class="card-text text-muted">
                            <?php echo htmlspecialchars(substr($review['review_text'], 0, 100)); ?>...
                        </p>
                        <div class="d-flex justify-content-between text-muted small">
                            <span>
                                <i class="fas fa-thumbs-up me-1"></i><?php echo $review['upvotes']; ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $review['location_id']; ?>"
                                class="text-primary text-decoration-none">
                                Baca Selengkapnya
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Call to Action -->
<div class="bg-light py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Bergabunglah dengan Komunitas Mie Time</h2>
        <p class="lead text-muted mb-4">
            Bagikan pengalaman kuliner Anda dan bantu orang lain menemukan warung mie ayam terbaik
        </p>
        <?php if (!is_logged_in()): ?>
            <a href="<?php echo BASE_URL; ?>register" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus me-2"></i>Daftar Gratis
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>warung/add" class="btn btn-primary btn-lg">
                <i class="fas fa-plus-circle me-2"></i>Tambah Warung Baru
            </a>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-shadow {
        transition: all 0.3s ease;
    }

    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
</style>

<?php include 'includes/footer.php'; ?>