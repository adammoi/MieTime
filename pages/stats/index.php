<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$page_title = 'Statistik Platform';

// Public stats
$stats = [
    'total_users' => db_count('users'),
    'total_locations' => db_count('locations', 'status = "approved"'),
    'total_reviews' => db_count('reviews', 'status = "approved"'),
    'total_upvotes' => db_fetch("SELECT SUM(upvotes) as total FROM reviews WHERE status = 'approved'")['total'] ?? 0,
];

// Monthly review trend (last 12 months)
$review_monthly = db_fetch_all("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        AND status = 'approved'
    GROUP BY month
    ORDER BY month ASC
");

// Rating distribution
$rating_distribution = db_fetch_all("
    SELECT rating, COUNT(*) as count
    FROM reviews
    WHERE status = 'approved'
    GROUP BY rating
    ORDER BY rating ASC
");

// Top reviewed locations
$top_locations = db_fetch_all("
    SELECT l.location_id, l.name, l.address, COUNT(r.review_id) as review_count,
           ROUND(AVG(r.rating), 1) as avg_rating
    FROM locations l
    LEFT JOIN reviews r ON l.location_id = r.location_id AND r.status = 'approved'
    WHERE l.status = 'approved'
    GROUP BY l.location_id
    ORDER BY review_count DESC
    LIMIT 10
");

// Top contributors
$top_contributors = db_fetch_all("
    SELECT u.user_id, u.username, u.role, COUNT(r.review_id) as review_count,
           COALESCE(SUM(r.upvotes), 0) as total_upvotes
    FROM users u
    LEFT JOIN reviews r ON u.user_id = r.user_id AND r.status = 'approved'
    GROUP BY u.user_id
    HAVING review_count > 0
    ORDER BY review_count DESC, total_upvotes DESC
    LIMIT 10
");

// Monthly growth
$current_month = db_count('reviews', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') AND status = 'approved'");
$previous_month = db_count('reviews', "DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m') AND status = 'approved'");
$growth_percentage = $previous_month > 0 ? round((($current_month - $previous_month) / $previous_month) * 100, 1) : 0;

include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Header -->
    <div class="text-center mb-12">
        <h1 class="text-5xl font-bold mb-4">
            <i class="fas fa-chart-bar mr-3"></i>
            <span class="text-gray-900 font-semibold">Statistik Platform</span>
        </h1>
        <p class="text-gray-600 text-lg">Data dan tren aktivitas pengguna Mie Time</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
        <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="p-6 text-white text-center">
                <i class="fas fa-users text-5xl mb-4 opacity-75"></i>
                <h2 class="text-4xl font-bold mb-2"><?php echo number_format($stats['total_users']); ?></h2>
                <p class="text-sm opacity-90">Total Pengguna</p>
            </div>
        </div>

        <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="p-6 text-white text-center">
                <i class="fas fa-store text-5xl mb-4 opacity-75"></i>
                <h2 class="text-4xl font-bold mb-2"><?php echo number_format($stats['total_locations']); ?></h2>
                <p class="text-sm opacity-90">Kedai Mie</p>
            </div>
        </div>

        <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="p-6 text-white text-center">
                <i class="fas fa-comment text-5xl mb-4 opacity-75"></i>
                <h2 class="text-4xl font-bold mb-2"><?php echo number_format($stats['total_reviews']); ?></h2>
                <p class="text-sm opacity-90">Total Review</p>
                <?php if ($growth_percentage != 0): ?>
                    <span class="inline-block bg-white bg-opacity-30 text-white px-2 py-1 rounded-full text-xs font-semibold mt-2">
                        <i class="fas fa-arrow-<?php echo $growth_percentage > 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs($growth_percentage); ?>% bulan ini
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="p-6 text-white text-center">
                <i class="fas fa-thumbs-up text-5xl mb-4 opacity-75"></i>
                <h2 class="text-4xl font-bold mb-2"><?php echo number_format($stats['total_upvotes']); ?></h2>
                <p class="text-sm opacity-90">Total Upvotes</p>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Review Trend -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                    Tren Review (12 Bulan Terakhir)
                </h5>
            </div>
            <div class="p-6">
                <canvas id="reviewTrendChart" height="80"></canvas>
            </div>
        </div>

        <!-- Rating Distribution -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-star text-yellow-500 mr-2"></i>
                    Distribusi Rating
                </h5>
            </div>
            <div class="p-6">
                <canvas id="ratingChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
        <!-- Top Locations -->
        <div class="lg:col-span-3 bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                    Top 10 Kedai Terpopuler
                </h5>
            </div>
            <div class="p-6">
                <canvas id="topLocationsChart" height="100"></canvas>
            </div>
        </div>

        <!-- Top Contributors -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h5 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-award text-green-600 mr-2"></i>
                    Top 10 Kontributor
                </h5>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase" style="width: 50px;">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Reviews</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Upvotes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                        $rank = 1;
                        foreach ($top_contributors as $contributor):
                            $medal = '';
                            if ($rank == 1) $medal = '🥇';
                            elseif ($rank == 2) $medal = '🥈';
                            elseif ($rank == 3) $medal = '🥉';
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-center font-bold"><?php echo $medal . ' ' . $rank; ?></td>
                                <td class="px-4 py-3">
                                    <a href="<?php echo BASE_URL; ?>pages/user/profile.php?user_id=<?php echo $contributor['user_id']; ?>"
                                        class="font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                        <?php echo htmlspecialchars($contributor['username']); ?>
                                    </a>
                                    <br>
                                    <span class="text-xs"><?php echo get_role_badge($contributor['role']); ?></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                        <?php echo number_format($contributor['review_count']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <?php echo number_format($contributor['total_upvotes']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php
                            $rank++;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center mt-12">
        <a href="<?php echo BASE_URL; ?>" class="inline-block px-8 py-3 border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-600 hover:text-white transition">
            <i class="fas fa-home mr-2"></i>Kembali ke Beranda
        </a>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    // Prepare data
    const reviewMonthly = <?php echo json_encode($review_monthly); ?>;
    const ratingDist = <?php echo json_encode($rating_distribution); ?>;
    const topLocs = <?php echo json_encode($top_locations); ?>;

    // Month formatter
    function formatMonth(monthStr) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const [year, month] = monthStr.split('-');
        return months[parseInt(month) - 1] + ' ' + year;
    }

    // 1. Review Trend Chart
    const trendCtx = document.getElementById('reviewTrendChart');
    if (trendCtx) {
        const months = reviewMonthly.map(r => formatMonth(r.month));
        const counts = reviewMonthly.map(r => parseInt(r.count));

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Jumlah Review',
                    data: counts,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
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

    // 2. Rating Distribution
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
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(250, 204, 21, 0.8)',
                        'rgba(134, 239, 172, 0.8)',
                        'rgba(34, 197, 94, 0.8)'
                    ],
                    borderWidth: 3,
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

    // 3. Top Locations
    const topLocsCtx = document.getElementById('topLocationsChart');
    if (topLocsCtx) {
        const names = topLocs.map(l => l.name.length > 30 ? l.name.substring(0, 30) + '...' : l.name);
        const counts = topLocs.map(l => parseInt(l.review_count));

        new Chart(topLocsCtx, {
            type: 'bar',
            data: {
                labels: names,
                datasets: [{
                    label: 'Jumlah Review',
                    data: counts,
                    backgroundColor: 'rgba(245, 158, 11, 0.7)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 2,
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
                                return topLocs[context[0].dataIndex].name;
                            },
                            afterBody: function(context) {
                                const idx = context[0].dataIndex;
                                return 'Rating: ' + topLocs[idx].avg_rating + ' ⭐';
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

<?php include '../../includes/footer.php'; ?>