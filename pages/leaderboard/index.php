<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Public leaderboard: top users by upvotes, then by reviews
$page_title = 'Leaderboard';
include '../../includes/header.php';

// Query top users (show up to 50)
$leaderboard = db_fetch_all(
    "SELECT u.user_id, u.username, u.role, u.created_at,
            COALESCE(SUM(r.upvotes), 0) AS upvotes, 
            COUNT(r.review_id) AS reviews,
            (SELECT COUNT(*) FROM user_badges WHERE user_id = u.user_id) as badge_count
     FROM users u
     LEFT JOIN reviews r ON u.user_id = r.user_id
     GROUP BY u.user_id
     ORDER BY upvotes DESC, reviews DESC, u.user_id ASC
     LIMIT 50"
);

?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-col md:flex-row justify-between items-center mb-12">
        <div>
            <h1 class="text-5xl font-bold mb-2">
                <i class="fas fa-trophy text-yellow-500 mr-3"></i>
                <span class="text-gray-900 font-semibold">Leaderboard</span>
            </h1>
            <p class="text-gray-600 text-lg">Daftar pengguna terbaik berdasarkan total upvotes dan jumlah review.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="<?php echo rtrim(BASE_URL, '/'); ?>/" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-home mr-2"></i>Kembali
            </a>
        </div>
    </div>

    <!-- Top 3 Podium -->
    <?php if (count($leaderboard) >= 3): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12 items-end">
            <!-- 2nd Place -->
            <div class="md:order-1">
                <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="p-6 text-center text-white">
                        <div class="mb-4">
                            <i class="fas fa-medal text-6xl" style="color: #C0C0C0;"></i>
                        </div>
                        <h2 class="text-4xl font-bold mb-2">#2</h2>
                        <?php
                        $user = $leaderboard[1];
                        ?>
                        <div class="w-24 h-24 rounded-full bg-white border-4 border-white inline-flex items-center justify-center mb-4">
                            <i class="fas fa-user text-5xl text-purple-600"></i>
                        </div>
                        <h4 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h4>
                        <?php echo get_role_badge($user['role']); ?>
                        <div class="mt-4 flex justify-center gap-6">
                            <div>
                                <div class="text-sm opacity-90">Upvotes</div>
                                <div class="text-2xl font-bold"><?php echo number_format($user['upvotes']); ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-90">Reviews</div>
                                <div class="text-2xl font-bold"><?php echo number_format($user['reviews']); ?></div>
                            </div>
                        </div>
                        <?php if ($user['badge_count'] > 0): ?>
                            <div class="mt-3">
                                <i class="fas fa-medal mr-1"></i>
                                <span class="text-sm"><?php echo $user['badge_count']; ?> Badges</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 1st Place (Larger) -->
            <div class="md:order-0 transform md:scale-110 z-10">
                <div class="rounded-2xl shadow-2xl overflow-hidden" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="p-8 text-center text-white">
                        <div class="mb-4">
                            <i class="fas fa-crown text-7xl text-yellow-400"></i>
                        </div>
                        <h1 class="text-5xl font-bold mb-2">#1</h1>
                        <?php
                        $user = $leaderboard[0];
                        ?>
                        <div class="w-28 h-28 rounded-full bg-white border-4 border-yellow-400 inline-flex items-center justify-center mb-4">
                            <i class="fas fa-user text-6xl text-pink-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <?php echo get_role_badge($user['role']); ?>
                        <div class="mt-4 flex justify-center gap-8">
                            <div>
                                <div class="text-sm opacity-90">Upvotes</div>
                                <div class="text-3xl font-bold"><?php echo number_format($user['upvotes']); ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-90">Reviews</div>
                                <div class="text-3xl font-bold"><?php echo number_format($user['reviews']); ?></div>
                            </div>
                        </div>
                        <?php if ($user['badge_count'] > 0): ?>
                            <div class="mt-3">
                                <i class="fas fa-medal mr-1"></i>
                                <span class="text-sm"><?php echo $user['badge_count']; ?> Badges</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 3rd Place -->
            <div class="md:order-2">
                <div class="rounded-2xl shadow-lg overflow-hidden" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="p-6 text-center text-white">
                        <div class="mb-4">
                            <i class="fas fa-medal text-6xl" style="color: #CD7F32;"></i>
                        </div>
                        <h2 class="text-4xl font-bold mb-2">#3</h2>
                        <?php
                        $user = $leaderboard[2];
                        ?>
                        <div class="w-20 h-20 rounded-full bg-white border-4 border-white inline-flex items-center justify-center mb-4">
                            <i class="fas fa-user text-4xl text-blue-500"></i>
                        </div>
                        <h5 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($user['username']); ?></h5>
                        <?php echo get_role_badge($user['role']); ?>
                        <div class="mt-4 flex justify-center gap-6">
                            <div>
                                <div class="text-sm opacity-90">Upvotes</div>
                                <div class="text-xl font-bold"><?php echo number_format($user['upvotes']); ?></div>
                            </div>
                            <div>
                                <div class="text-sm opacity-90">Reviews</div>
                                <div class="text-xl font-bold"><?php echo number_format($user['reviews']); ?></div>
                            </div>
                        </div>
                        <?php if ($user['badge_count'] > 0): ?>
                            <div class="mt-3">
                                <i class="fas fa-medal mr-1"></i>
                                <span class="text-sm"><?php echo $user['badge_count']; ?> Badges</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Full Leaderboard Table -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 gradient-primary">
            <h5 class="text-xl font-bold text-white">
                <i class="fas fa-list-ol mr-2"></i>Peringkat Lengkap
            </h5>
        </div>
        <div class="p-6">
            <?php if (empty($leaderboard)): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                    <p class="text-blue-700">Belum ada data leaderboard.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:70px;">Rank</th>
                                <th class="px-4 py-3" style="width:80px;"></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pengguna</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:120px;">Upvotes</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:120px;">Reviews</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:100px;">Badges</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width:150px;">Role</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $rank = 1;
                            foreach ($leaderboard as $row):
                                $rank_icon = '';
                                $rank_class = 'hover:bg-gray-50';
                                if ($rank === 1) {
                                    $rank_icon = '<i class="fas fa-crown text-yellow-500"></i>';
                                    $rank_class = 'bg-yellow-50 hover:bg-yellow-100';
                                } elseif ($rank === 2) {
                                    $rank_icon = '<i class="fas fa-medal" style="color: #C0C0C0;"></i>';
                                    $rank_class = 'bg-blue-50 hover:bg-blue-100';
                                } elseif ($rank === 3) {
                                    $rank_icon = '<i class="fas fa-medal" style="color: #CD7F32;"></i>';
                                    $rank_class = 'bg-gray-50 hover:bg-gray-100';
                                }
                            ?>
                                <tr class="<?php echo $rank_class; ?>">
                                    <td class="px-4 py-4 text-center font-bold">
                                        <?php echo $rank_icon ? $rank_icon . ' ' : ''; ?>
                                        <?php echo $rank; ?>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 border-2 border-gray-300 inline-flex items-center justify-center">
                                            <i class="fas fa-user text-lg text-gray-600"></i>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/pages/user/profile.php?user_id=<?php echo (int)$row['user_id']; ?>" class="font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </a>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                                            <i class="fas fa-thumbs-up mr-1"></i><?php echo number_format($row['upvotes']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-comment mr-1"></i><?php echo number_format($row['reviews']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <?php if ($row['badge_count'] > 0): ?>
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-medal mr-1"></i><?php echo $row['badge_count']; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4"><?php echo get_role_badge($row['role']); ?></td>
                                </tr>
                            <?php
                                $rank++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>