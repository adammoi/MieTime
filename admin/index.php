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
    'total_badges' => db_count('badges'),
    'total_claims' => db_count('location_claims'),
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

// Stats for chart - Reviews per month (last 6 months)
$review_monthly = db_fetch_all("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

// User registration trend (last 6 months)
$user_monthly = db_fetch_all("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month ASC
");

// Rating distribution
$rating_distribution = db_fetch_all("
    SELECT rating, COUNT(*) as count
    FROM reviews
    GROUP BY rating
    ORDER BY rating ASC
");

// Top locations by review count
$top_locations = db_fetch_all("
    SELECT l.name, COUNT(r.review_id) as review_count
    FROM locations l
    LEFT JOIN reviews r ON l.location_id = r.location_id
    GROUP BY l.location_id
    ORDER BY review_count DESC
    LIMIT 10
");

// Monthly comparison (current vs previous month)
$current_month_reviews = db_count('reviews', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
$previous_month_reviews = db_count('reviews', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')");
$review_growth = $previous_month_reviews > 0 ? (($current_month_reviews - $previous_month_reviews) / $previous_month_reviews * 100) : 0;

$current_month_users = db_count('users', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')");
$previous_month_users = db_count('users', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')");
$user_growth = $previous_month_users > 0 ? (($current_month_users - $previous_month_users) / $previous_month_users * 100) : 0;

$page_title = 'Admin Dashboard';
include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="lg:col-span-3">
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                <div>
                    <h2 class="text-4xl font-bold mb-2">Admin Dashboard</h2>
                    <p class="text-gray-600">Overview statistik platform</p>
                </div>
                <div>
                    <span class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-bold rounded-lg shadow-lg text-sm">
                        <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'Administrator' : 'Moderator'; ?>
                    </span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="rounded-2xl shadow-lg overflow-hidden hover-lift" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="p-6 text-white text-center">
                        <i class="fas fa-users text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-4xl font-bold mb-1"><?php echo number_format($stats['total_users']); ?></h3>
                        <p class="text-sm opacity-90 mb-3">Total Pengguna</p>
                        <?php if ($user_growth != 0): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-white bg-opacity-50 rounded-full text-xs font-semibold">
                                <i class="fas fa-arrow-<?php echo $user_growth > 0 ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(round($user_growth, 1)); ?>% bulan ini
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl shadow-lg overflow-hidden hover-lift" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="p-6 text-white text-center">
                        <i class="fas fa-store text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-4xl font-bold mb-1"><?php echo number_format($stats['total_locations']); ?></h3>
                        <p class="text-sm opacity-90 mb-3">Total Kedai</p>
                        <?php if ($stats['pending_locations'] > 0): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-white bg-opacity-50 rounded-full text-xs font-semibold">
                                <i class="fas fa-clock mr-1"></i> <?php echo $stats['pending_locations']; ?> pending
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl shadow-lg overflow-hidden hover-lift" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="p-6 text-white text-center">
                        <i class="fas fa-comment text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-4xl font-bold mb-1"><?php echo number_format($stats['total_reviews']); ?></h3>
                        <p class="text-sm opacity-90 mb-3">Total Review</p>
                        <?php if ($review_growth != 0): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-white bg-opacity-50 rounded-full text-xs font-semibold">
                                <i class="fas fa-arrow-<?php echo $review_growth > 0 ? 'up' : 'down'; ?> mr-1"></i>
                                <?php echo abs(round($review_growth, 1)); ?>% bulan ini
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="rounded-2xl shadow-lg overflow-hidden hover-lift" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                    <div class="p-6 text-white text-center">
                        <i class="fas fa-thumbs-up text-5xl mb-4 opacity-75"></i>
                        <h3 class="text-4xl font-bold mb-1"><?php echo number_format($stats['total_upvotes']); ?></h3>
                        <p class="text-sm opacity-90 mb-3">Total Upvotes</p>
                        <span class="inline-flex items-center px-3 py-1 bg-white bg-opacity-50 rounded-full text-xs font-semibold">
                            <i class="fas fa-medal mr-1"></i> <?php echo $stats['total_badges']; ?> badges
                        </span>
                    </div>
                </div>
            </div>

            <!-- Pending Items Alert -->
            <?php if ($stats['pending_reviews'] > 0 || $stats['pending_locations'] > 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-6 shadow-lg mb-8">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-2xl mr-3 mt-1"></i>
                        <div class="flex-1">
                            <h5 class="text-lg font-bold text-gray-900 mb-3">Perlu Perhatian</h5>
                            <ul class="space-y-2">
                                <?php if ($stats['pending_reviews'] > 0): ?>
                                    <li class="text-gray-700">
                                        <strong class="font-bold"><?php echo $stats['pending_reviews']; ?></strong> review menunggu moderasi
                                        <a href="<?php echo BASE_URL; ?>admin/moderation" class="text-blue-600 hover:text-blue-800 font-semibold ml-2">
                                            Lihat Sekarang →
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <?php if ($stats['pending_locations'] > 0): ?>
                                    <li class="text-gray-700">
                                        <strong class="font-bold"><?php echo $stats['pending_locations']; ?></strong> kedai baru menunggu approval
                                        <a href="<?php echo BASE_URL; ?>admin/locations" class="text-blue-600 hover:text-blue-800 font-semibold ml-2">
                                            Lihat Sekarang →
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Reviews Trend -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 p-6">
                        <h6 class="font-bold text-lg flex items-center">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                            Tren Review & User (6 Bulan Terakhir)
                        </h6>
                    </div>
                    <div class="p-6">
                        <canvas id="trendChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Rating Distribution -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 p-6">
                        <h6 class="font-bold text-lg flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>
                            Distribusi Rating
                        </h6>
                    </div>
                    <div class="p-6">
                        <canvas id="ratingChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Locations Chart -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 p-6">
                        <h6 class="font-bold text-lg flex items-center">
                            <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                            Top 10 Kedai Berdasarkan Jumlah Review
                        </h6>
                    </div>
                    <div class="p-6">
                        <canvas id="topLocationsChart" height="80"></canvas>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="border-b border-gray-200 p-6">
                        <h6 class="font-bold text-lg flex items-center">
                            <i class="fas fa-user-plus text-green-600 mr-2"></i>
                            Pengguna Baru Terbaru
                        </h6>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php foreach ($recent_users as $u): ?>
                            <div class="flex justify-between items-center p-4 hover:bg-gray-50 transition">
                                <div class="flex items-center">
                                    <i class="fas fa-user-circle text-blue-600 text-2xl mr-3"></i>
                                    <div>
                                        <strong class="font-semibold text-gray-900"><?php echo htmlspecialchars($u['username']); ?></strong>
                                        <br>
                                        <small class="text-gray-600"><?php echo time_ago($u['created_at']); ?></small>
                                    </div>
                                </div>
                                <?php echo get_role_badge($u['role']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Reviews -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="border-b border-gray-200 p-6">
                    <h5 class="font-bold text-xl flex items-center">
                        <i class="fas fa-clock text-yellow-500 mr-2"></i>
                        Review Terbaru
                    </h5>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Kedai</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rating</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Review</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recent_reviews as $r): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
                                        <?php echo htmlspecialchars($r['username']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <a href="<?php echo BASE_URL; ?>kedai/<?php echo $r['location_id']; ?>"
                                            target="_blank"
                                            class="text-blue-600 hover:text-blue-800 font-semibold">
                                            <?php echo htmlspecialchars($r['location_name']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php echo star_rating($r['rating']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 text-sm">
                                        <?php echo htmlspecialchars(substr($r['review_text'], 0, 50)); ?>...
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                            <?php echo $r['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($r['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo $r['status']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <?php echo time_ago($r['created_at']); ?>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Prepare data
    const reviewMonthly = <?php echo json_encode($review_monthly); ?>;
    const userMonthly = <?php echo json_encode($user_monthly); ?>;
    const ratingDist = <?php echo json_encode($rating_distribution); ?>;
    const topLocs = <?php echo json_encode($top_locations); ?>;

    // Function to format month labels
    function formatMonth(monthStr) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const [year, month] = monthStr.split('-');
        return months[parseInt(month) - 1] + ' ' + year;
    }

    // 1. Trend Chart (Reviews & Users)
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        // Merge months from both datasets
        const allMonths = [...new Set([
            ...reviewMonthly.map(r => r.month),
            ...userMonthly.map(u => u.month)
        ])].sort();

        const reviewData = allMonths.map(month => {
            const found = reviewMonthly.find(r => r.month === month);
            return found ? parseInt(found.count) : 0;
        });

        const userData = allMonths.map(month => {
            const found = userMonthly.find(u => u.month === month);
            return found ? parseInt(found.count) : 0;
        });

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: allMonths.map(formatMonth),
                datasets: [{
                        label: 'Reviews',
                        data: reviewData,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    },
                    {
                        label: 'New Users',
                        data: userData,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        }
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

    // 2. Rating Distribution (Doughnut Chart)
    const ratingCtx = document.getElementById('ratingChart');
    if (ratingCtx) {
        const ratings = ratingDist.map(r => r.rating + ' ⭐');
        const counts = ratingDist.map(r => parseInt(r.count));

        new Chart(ratingCtx, {
            type: 'doughnut',
            data: {
                labels: ratings,
                datasets: [{
                    data: counts,
                    backgroundColor: [
                        'rgba(239, 68, 68, 0.8)', // 1 star - red
                        'rgba(251, 146, 60, 0.8)', // 2 stars - orange
                        'rgba(250, 204, 21, 0.8)', // 3 stars - yellow
                        'rgba(134, 239, 172, 0.8)', // 4 stars - green
                        'rgba(34, 197, 94, 0.8)' // 5 stars - dark green
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Top Locations (Horizontal Bar Chart)
    const topLocsCtx = document.getElementById('topLocationsChart');
    if (topLocsCtx) {
        const locationNames = topLocs.map(l => l.name.length > 25 ? l.name.substring(0, 25) + '...' : l.name);
        const reviewCounts = topLocs.map(l => parseInt(l.review_count));

        new Chart(topLocsCtx, {
            type: 'bar',
            data: {
                labels: locationNames,
                datasets: [{
                    label: 'Jumlah Review',
                    data: reviewCounts,
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(252, 211, 77, 0.7)',
                        'rgba(253, 224, 71, 0.7)',
                        'rgba(190, 242, 100, 0.6)',
                        'rgba(163, 230, 53, 0.6)',
                        'rgba(132, 204, 22, 0.6)',
                        'rgba(101, 163, 13, 0.5)',
                        'rgba(77, 124, 15, 0.5)'
                    ],
                    borderWidth: 2,
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        callbacks: {
                            title: function(context) {
                                // Show full name in tooltip
                                return topLocs[context[0].dataIndex].name;
                            }
                        }
                    }
                },
                scales: {
                    x: {
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