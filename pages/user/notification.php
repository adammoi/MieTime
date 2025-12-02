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

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all_read'])) {
        mark_all_notifications_read($user_id);
        set_flash('success', 'Semua notifikasi telah ditandai sebagai dibaca');
        redirect('notifications');
    } elseif (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        mark_notification_read($notification_id);
    }
}

// Get all notifications
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$total = db_count('notifications', 'user_id = ?', [$user_id]);
$notifications = db_fetch_all("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
", [$user_id, $per_page, $offset]);

$unread_count = get_unread_notifications_count($user_id);

$page_title = 'Notifikasi';
include '../../includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
        <div>
            <h2 class="text-4xl font-bold mb-2">
                <i class="fas fa-bell text-blue-600 mr-2"></i>
                Notifikasi
            </h2>
            <p class="text-gray-600">
                Semua notifikasi dan pembaruan terkait aktivitas Anda
            </p>
        </div>
        <div class="mt-4 md:mt-0">
            <?php if ($unread_count > 0): ?>
                <form method="POST" class="inline">
                    <button type="submit" name="mark_all_read" class="px-4 py-2 border-2 border-blue-600 text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition">
                        <i class="fas fa-check-double mr-1"></i>
                        Tandai Semua Dibaca
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <?php if ($unread_count > 0): ?>
        <div class="bg-blue-50 border-l-4 border-blue-400 p-6 mb-8 rounded-lg">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Anda memiliki <strong class="font-bold"><?php echo $unread_count; ?></strong> notifikasi yang belum dibaca
        </div>
    <?php endif; ?>

    <!-- Notifications List -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-12">
                <i class="fas fa-bell-slash text-gray-400 mb-4" style="font-size: 3rem;"></i>
                <p class="text-gray-600">Tidak ada notifikasi</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach ($notifications as $notif):
                    $is_unread = $notif['is_read'] == 0;
                    $url = $notif['link_url'] ? BASE_URL . $notif['link_url'] : '#';
                ?>
                    <div class="p-6 hover:bg-gray-50 transition <?php echo $is_unread ? 'bg-blue-50 border-l-4 border-blue-600' : ''; ?>">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <?php if ($is_unread): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-semibold mr-2">Baru</span>
                                    <?php endif; ?>
                                    <small class="text-gray-600">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo time_ago($notif['created_at']); ?>
                                    </small>
                                </div>

                                <?php if ($notif['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" class="text-gray-900 hover:text-blue-600">
                                        <p class="<?php echo $is_unread ? 'font-bold' : ''; ?>">
                                            <?php echo $notif['message']; ?>
                                        </p>
                                    </a>
                                <?php else: ?>
                                    <p class="<?php echo $is_unread ? 'font-bold text-gray-900' : 'text-gray-700'; ?>">
                                        <?php echo $notif['message']; ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="ml-4 flex gap-2">
                                <?php if ($notif['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($url); ?>" class="px-3 py-2 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if ($is_unread): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="notification_id" value="<?php echo (int)$notif['notification_id']; ?>">
                                        <button type="submit" name="mark_read" class="px-3 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-100 transition" title="Tandai sudah dibaca">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total > $per_page):
        $total_pages = (int)ceil($total / $per_page);
    ?>
        <nav class="mt-8">
            <ul class="flex justify-center gap-2">
                <?php if ($page > 1): ?>
                    <li>
                        <a class="px-4 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-100 transition" href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($total_pages, $page + 2); $p++): ?>
                    <li>
                        <a class="px-4 py-2 border-2 <?php echo $p === $page ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'; ?> font-semibold rounded-lg transition" href="?page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <li>
                        <a class="px-4 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-100 transition" href="?page=<?php echo $page + 1; ?>">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

    <!-- Info Card -->
    <div class="mt-8 bg-gradient-to-br from-blue-50 to-purple-50 rounded-2xl shadow-lg p-6">
        <h6 class="flex items-center font-bold text-lg text-gray-900 mb-4">
            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
            Tentang Notifikasi
        </h6>
        <p class="mb-3 text-gray-700">
            <i class="fas fa-check-circle text-green-600 mr-2"></i>
            Anda akan menerima notifikasi untuk aktivitas penting seperti:
        </p>
        <ul class="space-y-2 text-gray-700">
            <li class="flex items-start">
                <i class="fas fa-star text-yellow-500 mr-2 mt-1"></i>
                Review Anda mendapat upvote
            </li>
            <li class="flex items-start">
                <i class="fas fa-comment text-blue-500 mr-2 mt-1"></i>
                Seseorang membalas komentar Anda
            </li>
            <li class="flex items-start">
                <i class="fas fa-store text-green-500 mr-2 mt-1"></i>
                Kedai yang Anda tambahkan disetujui
            </li>
            <li class="flex items-start">
                <i class="fas fa-medal text-purple-500 mr-2 mt-1"></i>
                Anda mendapatkan badge baru
            </li>
            <li class="flex items-start">
                <i class="fas fa-trophy text-orange-500 mr-2 mt-1"></i>
                Poin Anda bertambah
            </li>
        </ul>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>