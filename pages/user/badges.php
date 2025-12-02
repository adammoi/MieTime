<?php

if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Require login
require_login();

$user_id = get_current_user_id();
$user = get_user_by_id($user_id);

// Get all badges
$all_badges = db_fetch_all("SELECT * FROM badges ORDER BY badge_id ASC");

// Get user's earned badges
$user_badges = get_user_badges($user_id);
$earned_badge_ids = array_column($user_badges, 'badge_id');

$page_title = 'Badge Saya';
include '../../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h2 class="text-4xl font-bold mb-2">
                    <i class="fas fa-medal text-yellow-500 mr-2"></i>
                    Badge Saya
                </h2>
                <p class="text-gray-600">
                    Koleksi pencapaian Anda di MieTime
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <div class="inline-flex items-center px-6 py-3 gradient-primary text-white font-bold rounded-lg shadow-lg">
                    <i class="fas fa-trophy mr-2"></i>
                    <?php echo count($user_badges); ?> / <?php echo count($all_badges); ?> Badge
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php
        $progress_percentage = count($all_badges) > 0 ? (count($user_badges) / count($all_badges)) * 100 : 0;
        ?>
        <div class="mb-3">
            <div class="w-full bg-gray-200 rounded-full h-7 overflow-hidden">
                <div class="bg-gradient-to-r from-green-400 to-green-600 h-7 rounded-full flex items-center justify-center text-white font-bold text-sm transition-all duration-500"
                    style="width: <?php echo $progress_percentage; ?>%">
                    <?php echo round($progress_percentage); ?>%
                </div>
            </div>
        </div>
        <p class="text-gray-600 text-sm">
            <i class="fas fa-info-circle mr-1"></i>
            Kumpulkan semua badge dengan terus menulis review dan berkontribusi!
        </p>
    </div>

    <!-- Badge Categories -->
    <div class="mb-8" x-data="{ activeTab: 'all' }">
        <div class="flex border-b border-gray-200">
            <button @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                class="px-6 py-3 font-semibold border-b-2 transition">
                <i class="fas fa-th mr-2"></i>Semua Badge
            </button>
            <button @click="activeTab = 'earned'"
                :class="activeTab === 'earned' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                class="px-6 py-3 font-semibold border-b-2 transition">
                <i class="fas fa-check-circle mr-2"></i>Sudah Didapat
            </button>
            <button @click="activeTab = 'locked'"
                :class="activeTab === 'locked' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300'"
                class="px-6 py-3 font-semibold border-b-2 transition">
                <i class="fas fa-lock mr-2"></i>Terkunci
            </button>
        </div>

        <!-- Tab Content -->
        <div class="mt-8">
            <!-- All Badges Tab -->
            <div x-show="activeTab === 'all'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($all_badges as $badge):
                    $is_earned = in_array($badge['badge_id'], $earned_badge_ids);
                    $earned_data = null;
                    if ($is_earned) {
                        foreach ($user_badges as $ub) {
                            if ($ub['badge_id'] == $badge['badge_id']) {
                                $earned_data = $ub;
                                break;
                            }
                        }
                    }
                ?>
                    <div class="bg-white rounded-2xl shadow-lg p-6 text-center relative <?php echo !$is_earned ? 'opacity-60' : 'ring-2 ring-green-500'; ?>">
                        <?php if ($is_earned): ?>
                            <div class="absolute top-4 right-4">
                                <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 font-semibold rounded-full text-sm">
                                    <i class="fas fa-check mr-1"></i> Didapat
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="absolute top-4 right-4">
                                <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-600 font-semibold rounded-full text-sm">
                                    <i class="fas fa-lock mr-1"></i> Terkunci
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Badge Icon -->
                        <div class="mb-4 <?php echo !$is_earned ? 'grayscale opacity-50' : ''; ?>">
                            <?php if ($is_earned): ?>
                                <i class="fas fa-medal text-yellow-500" style="font-size: 3.5rem;"></i>
                            <?php else: ?>
                                <i class="fas fa-medal text-gray-400" style="font-size: 3.5rem;"></i>
                            <?php endif; ?>
                        </div>

                        <!-- Badge Info -->
                        <h5 class="text-xl font-bold mb-2 text-gray-900">
                            <?php echo htmlspecialchars($badge['badge_name']); ?>
                        </h5>
                        <p class="text-gray-600 mb-3">
                            <?php echo htmlspecialchars($badge['badge_description']); ?>
                        </p>

                        <!-- Badge Type -->
                        <div class="mb-3">
                            <?php if ($badge['badge_type'] === 'participation'): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 font-semibold rounded-full text-sm">
                                    <i class="fas fa-users mr-1"></i>Partisipasi
                                </span>
                            <?php elseif ($badge['badge_type'] === 'achievement'): ?>
                                <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 font-semibold rounded-full text-sm">
                                    <i class="fas fa-trophy mr-1"></i>Pencapaian
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Earned Date -->
                        <?php if ($is_earned && $earned_data): ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <small class="text-gray-600">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Didapat: <?php echo format_date_id($earned_data['earned_at']); ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <small class="text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?php echo htmlspecialchars($badge['trigger_condition']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($all_badges)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    Belum ada badge yang tersedia.
                </div>
            <?php endif; ?>
        </div>

        <!-- Earned Badges Tab -->
        <div x-show="activeTab === 'earned'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $has_earned = false;
            foreach ($all_badges as $badge):
                $is_earned = in_array($badge['badge_id'], $earned_badge_ids);
                if (!$is_earned) continue;
                $has_earned = true;

                $earned_data = null;
                foreach ($user_badges as $ub) {
                    if ($ub['badge_id'] == $badge['badge_id']) {
                        $earned_data = $ub;
                        break;
                    }
                }
            ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center relative ring-2 ring-green-500">
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 font-semibold rounded-full text-sm">
                            <i class="fas fa-check mr-1"></i> Didapat
                        </span>
                    </div>

                    <div class="mb-4">
                        <i class="fas fa-medal text-yellow-500" style="font-size: 4rem;"></i>
                    </div>

                    <h5 class="text-xl font-bold mb-2 text-gray-900">
                        <?php echo htmlspecialchars($badge['badge_name']); ?>
                    </h5>
                    <p class="text-gray-600 mb-3">
                        <?php echo htmlspecialchars($badge['badge_description']); ?>
                    </p>

                    <div class="mb-3">
                        <?php if ($badge['badge_type'] === 'participation'): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 font-semibold rounded-full text-sm">
                                <i class="fas fa-users mr-1"></i>Partisipasi
                            </span>
                        <?php elseif ($badge['badge_type'] === 'achievement'): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 font-semibold rounded-full text-sm">
                                <i class="fas fa-trophy mr-1"></i>Pencapaian
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($earned_data): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <small class="text-gray-600">
                                <i class="fas fa-calendar mr-1"></i>
                                Didapat: <?php echo format_date_id($earned_data['earned_at']); ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if (!$has_earned): ?>
                <div class="col-span-full">
                    <div class="bg-blue-50 border border-blue-200 rounded-2xl p-8 text-center">
                        <i class="fas fa-trophy text-blue-400 mb-3" style="font-size: 3rem;"></i>
                        <p class="text-gray-600 mb-4">
                            Anda belum mendapatkan badge apapun. Mulai menulis review untuk mendapatkan badge pertama Anda!
                        </p>
                        <a href="../locations/list.php" class="inline-flex items-center px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover-lift">
                            <i class="fas fa-search mr-2"></i>Jelajahi Lokasi
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Locked Badges Tab -->
        <div x-show="activeTab === 'locked'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $has_locked = false;
            foreach ($all_badges as $badge):
                $is_earned = in_array($badge['badge_id'], $earned_badge_ids);
                if ($is_earned) continue;
                $has_locked = true;
            ?>
                <div class="bg-white rounded-2xl shadow-lg p-6 text-center relative opacity-60">
                    <div class="absolute top-4 right-4">
                        <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-600 font-semibold rounded-full text-sm">
                            <i class="fas fa-lock mr-1"></i> Terkunci
                        </span>
                    </div>

                    <div class="mb-4 grayscale opacity-50">
                        <i class="fas fa-medal text-gray-400" style="font-size: 4rem;"></i>
                    </div>

                    <h5 class="text-xl font-bold mb-2 text-gray-900">
                        <?php echo htmlspecialchars($badge['badge_name']); ?>
                    </h5>
                    <p class="text-gray-600 mb-3">
                        <?php echo htmlspecialchars($badge['badge_description']); ?>
                    </p>

                    <div class="mb-3">
                        <?php if ($badge['badge_type'] === 'participation'): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 font-semibold rounded-full text-sm">
                                <i class="fas fa-users mr-1"></i>Partisipasi
                            </span>
                        <?php elseif ($badge['badge_type'] === 'achievement'): ?>
                            <span class="inline-flex items-center px-3 py-1 bg-yellow-100 text-yellow-800 font-semibold rounded-full text-sm">
                                <i class="fas fa-trophy mr-1"></i>Pencapaian
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <small class="text-gray-600">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php echo htmlspecialchars($badge['trigger_condition']); ?>
                        </small>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (!$has_locked): ?>
                <div class="col-span-full">
                    <div class="bg-green-50 border border-green-200 rounded-2xl p-8 text-center">
                        <i class="fas fa-trophy text-green-400 mb-3" style="font-size: 3rem;"></i>
                        <h4 class="text-2xl font-bold text-gray-900 mb-2">
                            Selamat! 🎉
                        </h4>
                        <p class="text-gray-600">
                            Anda telah mengumpulkan semua badge yang tersedia!
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Tips Section -->
<div class="mt-12 bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-8">
    <h3 class="text-2xl font-bold mb-6 flex items-center">
        <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
        Tips Mendapatkan Badge
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <ul class="space-y-3">
            <li class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Tulis review pertama Anda untuk badge <strong>Cicipan Pertama</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Tambahkan foto di review untuk badge <strong>Fotografer Mie</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Tulis 10 review untuk badge <strong>Juru Cicip</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Review di 3 kota berbeda untuk badge <strong>Penjelajah</strong></span>
            </li>
        </ul>
        <ul class="space-y-3">
            <li class="flex items-start">
                <i class="fas fa-check-circle text-purple-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Dapatkan 10 upvotes di satu review untuk badge <strong>Ahli Pangsit</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-purple-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Kumpulkan 100 total upvotes untuk badge <strong>Kritikus Terpercaya</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-purple-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Tulis 50 review untuk badge <strong>Pakar Mie</strong></span>
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-purple-600 mr-2 mt-1"></i>
                <span class="text-gray-700">Daftar lebih awal untuk badge <strong>Pendiri</strong></span>
            </li>
        </ul>
    </div>
</div>
</div>

<?php include '../../includes/footer.php'; ?>