<?php


define('MIE_TIME', true);
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

$query = isset($_GET['q']) ? clean_input($_GET['q']) : '';
$results = [];
$total_results = 0;

if (!empty($query) && strlen($query) >= 3) {
    $results = search_locations($query, 50);
    $total_results = count($results);
}

$page_title = 'Pencarian' . ($query ? ': ' . $query : '');
include '../../includes/header.php';
?>

<div class="container my-5">
    <!-- Search Header -->
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-3">
            <i class="fas fa-search text-primary me-2"></i>Cari Warung Mie Ayam
        </h2>
        <p class="text-muted">Temukan warung mie ayam favorit Anda di seluruh Indonesia</p>
    </div>

    <!-- Search Form -->
    <div class="row justify-content-center mb-5">
        <div class="col-lg-8">
            <form action="" method="GET" class="card shadow">
                <div class="card-body p-4">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-primary text-white">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control"
                            placeholder="Nama warung atau lokasi..."
                            value="<?php echo htmlspecialchars($query); ?>"
                            autofocus>
                        <button type="submit" class="btn btn-primary px-4">
                            Cari
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-lightbulb me-1"></i>
                        Contoh: "Mie Ayam Pak Sastro", "mie ayam Surabaya", "mie pangsit Jakarta"
                    </small>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <?php if (!empty($query)): ?>
        <?php if ($total_results > 0): ?>
            <div class="mb-4">
                <h5 class="fw-bold">
                    <i class="fas fa-list text-success me-2"></i>
                    Ditemukan <?php echo $total_results; ?> hasil untuk "<?php echo htmlspecialchars($query); ?>"
                </h5>
            </div>

            <div class="row">
                <?php foreach ($results as $location): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm hover-card">
                            <!-- Image -->
                            <div class="position-relative" style="height: 180px; overflow: hidden;">
                                <img src="https://via.placeholder.com/400x180/6c757d/ffffff?text=<?php echo urlencode($location['name']); ?>"
                                    class="card-img-top w-100 h-100" style="object-fit: cover;"
                                    alt="<?php echo htmlspecialchars($location['name']); ?>">

                                <?php if ($location['total_reviews'] > 0): ?>
                                    <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                                        <i class="fas fa-star me-1"></i>
                                        <?php echo number_format($location['average_rating'], 1); ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Body -->
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="<?php echo BASE_URL; ?>warung/<?php echo $location['location_id']; ?>"
                                        class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </a>
                                </h5>

                                <p class="card-text text-muted small">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>
                                    <?php if (strlen($location['address']) > 60) echo '...'; ?>
                                </p>

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
                                        <?php echo $location['total_reviews']; ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="card-footer bg-white border-0">
                                <a href="<?php echo BASE_URL; ?>warung/<?php echo $location['location_id']; ?>"
                                    class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-info-circle me-1"></i>Lihat Detail
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- No Results -->
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h4 class="fw-bold mb-3">Tidak ada hasil untuk "<?php echo htmlspecialchars($query); ?>"</h4>
                <p class="text-muted mb-4">
                    Coba kata kunci yang berbeda atau lebih umum
                </p>
                <a href="<?php echo BASE_URL; ?>warung" class="btn btn-primary">
                    <i class="fas fa-store me-2"></i>Lihat Semua Warung
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Empty State / Popular Locations -->
        <div class="text-center mb-5">
            <i class="fas fa-fire text-warning fa-3x mb-3"></i>
            <h4 class="fw-bold mb-3">Warung Populer</h4>
            <p class="text-muted">Belum tahu mau cari apa? Lihat warung-warung populer di bawah ini</p>
        </div>

        <div class="row">
            <?php
            $popular = get_top_locations(6);
            foreach ($popular as $location):
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm hover-card">
                        <div class="position-relative" style="height: 180px; overflow: hidden;">
                            <img src="https://via.placeholder.com/400x180/6c757d/ffffff?text=<?php echo urlencode($location['name']); ?>"
                                class="card-img-top w-100 h-100" style="object-fit: cover;"
                                alt="<?php echo htmlspecialchars($location['name']); ?>">

                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">
                                <i class="fas fa-star me-1"></i>
                                <?php echo number_format($location['average_rating'], 1); ?>
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
                                <?php echo htmlspecialchars(substr($location['address'], 0, 60)); ?>...
                            </p>

                            <div class="d-flex justify-content-between">
                                <div><?php echo star_rating($location['average_rating']); ?></div>
                                <span class="text-muted small">
                                    <i class="fas fa-comment me-1"></i>
                                    <?php echo $location['total_reviews']; ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-0">
                            <a href="<?php echo BASE_URL; ?>warung/<?php echo $location['location_id']; ?>"
                                class="btn btn-primary btn-sm w-100">
                                Lihat Detail
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
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