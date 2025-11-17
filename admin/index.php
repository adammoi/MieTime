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

// Current user data (safe: require_admin_or_moderator ensures logged in)
$current_user_id = get_current_user_id();
$user = $current_user_id ? get_user_by_id($current_user_id) : null;

// Get stats
$stats = [
    'total_users' => db_count('users'),
    'total_locations' => db_count('locations'),
    'total_reviews' => db_count('reviews'),
    'pending_reviews' => db_count('reviews', 'status = "pending"'),
    'pending_locations' => db_count('locations', 'status = "pending_approval"'),
    'total_upvotes' => db_fetch("SELECT SUM(upvotes) as total FROM reviews")['total'] ?? 0,
];

// Recent activities
$recent_reviews = db_fetch_all("
    SELECT r.*, u.username, l.name as location_name
    FROM reviews r
    JOIN users u ON r.user_id = u.user_id
    JOIN locations l ON r.location_id = l.location_id
    ORDER BY r.created_at DESC
    LIMIT 10
");

$recent_users = db_fetch_all("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Stats for chart (reviews per day last 7 days)
$review_stats = db_fetch_all("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY date
    ORDER BY date ASC
");

$page_title = 'Admin Dashboard';
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
                    <a href="<?php echo BASE_URL; ?>admin" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/moderation" class="list-group-item list-group-item-action">
                        <i class="fas fa-clipboard-check me-2"></i>Moderasi
                        <?php if ($stats['pending_reviews'] > 0): ?>
                            <span class="badge bg-warning float-end"><?php echo $stats['pending_reviews']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/users" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2"></i>Pengguna
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/locations" class="list-group-item list-group-item-action">
                        <i class="fas fa-store me-2"></i>Kedai
                        <?php if ($stats['pending_locations'] > 0): ?>
                            <span class="badge bg-warning float-end"><?php echo $stats['pending_locations']; ?></span>
                        <?php endif; ?>
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
                    <h2 class="fw-bold mb-1">Admin Dashboard</h2>
                    <p class="text-muted mb-0">Overview statistik platform</p>
                </div>
                <div>
                    <span class="badge bg-danger fs-6 px-3 py-2">
                        <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'Administrator' : 'Moderator'; ?>
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4 g-3">
                <div class="col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_users']); ?></h3>
                            <p class="text-muted mb-0">Total Pengguna</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-store fa-3x text-success mb-3"></i>
                            <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_locations']); ?></h3>
                            <p class="text-muted mb-0">Total Kedai</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-comment fa-3x text-warning mb-3"></i>
                            <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_reviews']); ?></h3>
                            <p class="text-muted mb-0">Total Review</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-thumbs-up fa-3x text-danger mb-3"></i>
                            <h3 class="fw-bold mb-1"><?php echo number_format($stats['total_upvotes']); ?></h3>
                            <p class="text-muted mb-0">Total Upvotes</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Items Alert -->
            <?php if ($stats['pending_reviews'] > 0 || $stats['pending_locations'] > 0): ?>
                <div class="alert alert-warning shadow-sm mb-4">
                    <h5 class="alert-heading">
                        <i class="fas fa-exclamation-triangle me-2"></i>Perlu Perhatian
                    </h5>
                    <ul class="mb-0">
                        <?php if ($stats['pending_reviews'] > 0): ?>
                            <li>
                                <strong><?php echo $stats['pending_reviews']; ?></strong> review menunggu moderasi
                                <a href="<?php echo BASE_URL; ?>admin/moderation" class="alert-link">Lihat Sekarang →</a>
                            </li>
                        <?php endif; ?>
                        <?php if ($stats['pending_locations'] > 0): ?>
                            <li>
                                <strong><?php echo $stats['pending_locations']; ?></strong> kedai baru menunggu approval
                                <a href="<?php echo BASE_URL; ?>admin/locations" class="alert-link">Lihat Sekarang →</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Charts -->
            <div class="row mb-4 g-3">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line text-primary me-2"></i>
                                Review 7 Hari Terakhir
                            </h6>
                        </div>
                        <div class="card-body">
                            <canvas id="reviewChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">
                                <i class="fas fa-user-plus text-success me-2"></i>
                                Pengguna Baru Terbaru
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $u): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-circle text-primary me-2"></i>
                                            <strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo time_ago($u['created_at']); ?></small>
                                        </div>
                                        <?php echo get_role_badge($u['role']); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>
                        Review Terbaru
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>User</th>
                                    <th>Kedai</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Status</th>
                                    <th>Waktu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_reviews as $r): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['username']); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>kedai/<?php echo $r['location_id']; ?>"
                                                target="_blank">
                                                <?php echo htmlspecialchars($r['location_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo star_rating($r['rating']); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($r['review_text'], 0, 50)); ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $r['status'] === 'approved' ? 'success' : ($r['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo $r['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small><?php echo time_ago($r['created_at']); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Review Chart
    const reviewDates = <?php echo json_encode(array_column($review_stats, 'date')); ?>;
    const reviewCounts = <?php echo json_encode(array_column($review_stats, 'count')); ?>;

    const ctx = document.getElementById('reviewChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: reviewDates,
                datasets: [{
                    label: 'Review',
                    data: reviewCounts,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>