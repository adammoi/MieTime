<?php
if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}

// Get notification count if user is logged in
$notification_count = 0;
if (is_logged_in()) {
    $notification_count = get_unread_notifications_count(get_current_user_id());
}
?>

<nav class="bg-white shadow-lg sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo & Brand -->
            <div class="flex items-center">
                <a href="<?php echo BASE_URL; ?>" class="flex items-center space-x-2 text-xl font-bold text-gray-900">
                    <i class="fas fa-bowl-food text-blue-600"></i>
                    <span>Mie Time</span>
                </a>

                <!-- Desktop Navigation -->
                <div class="hidden md:ml-10 md:flex md:items-center md:space-x-1">
                    <a href="<?php echo BASE_URL; ?>kedai" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <i class="fas fa-store mr-1"></i>Jelajahi Kedai
                    </a>
                    <a href="<?php echo BASE_URL; ?>leaderboard" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <i class="fas fa-trophy mr-1"></i>Leaderboard
                    </a>
                    <a href="<?php echo BASE_URL; ?>stats" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <i class="fas fa-chart-bar mr-1"></i>Statistik
                    </a>
                    <?php if (is_logged_in()): ?>
                        <a href="<?php echo BASE_URL; ?>kedai/add" class="px-3 py-2 rounded-lg text-sm font-medium text-white gradient-primary hover:shadow-lg transition">
                            <i class="fas fa-plus-circle mr-1"></i>Tambah Kedai
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Menu -->
            <div class="hidden md:flex md:items-center md:space-x-4">
                <?php if (is_logged_in()): ?>
                    <!-- Notifications -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" aria-haspopup="true" :aria-expanded="open" class="relative p-2 text-gray-600 hover:text-blue-600 focus:outline-none">
                            <i class="fas fa-bell text-xl"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="notification-badge absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full">
                                    <?php echo $notification_count > 9 ? '9+' : $notification_count; ?>
                                </span>
                            <?php endif; ?>
                        </button>

                        <div x-cloak x-show="open" x-transition.opacity.duration.150ms @click.away="open = false" @keydown.escape.window="open = false"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div id="notificationList" class="max-h-96 overflow-y-auto">
                                <div class="flex justify-center py-8">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 focus:outline-none">
                            <i class="fas fa-user-circle text-xl"></i>
                            <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div x-show="open" @click.away="open = false"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-50"
                            style="display: none;">
                            <a href="<?php echo BASE_URL; ?>dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                <i class="fas fa-tachometer-alt mr-2 w-4"></i>Dashboard
                            </a>
                            <a href="<?php echo BASE_URL; ?>profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                <i class="fas fa-user mr-2 w-4"></i>Profil
                            </a>
                            <a href="<?php echo BASE_URL; ?>badges" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                                <i class="fas fa-award mr-2 w-4"></i>Lencana Saya
                            </a>
                            <?php if (is_admin_or_moderator()): ?>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="<?php echo BASE_URL; ?>admin" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-user-shield mr-2 w-4"></i>Admin Panel
                                </a>
                            <?php endif; ?>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="<?php echo BASE_URL; ?>logout" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-sign-out-alt mr-2 w-4"></i>Logout
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <a href="<?php echo BASE_URL; ?>login" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                    </a>
                    <a href="<?php echo BASE_URL; ?>register" class="px-4 py-2 rounded-lg text-sm font-medium text-white gradient-primary hover:shadow-lg transition">
                        <i class="fas fa-user-plus mr-1"></i>Daftar
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button"
                    class="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 hover:text-blue-600 hover:bg-gray-100 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div x-show="mobileMenuOpen" class="md:hidden border-t border-gray-200" style="display: none;">
        <div class="px-2 pt-2 pb-3 space-y-1">
            <a href="<?php echo BASE_URL; ?>kedai" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                <i class="fas fa-store mr-2"></i>Jelajahi Kedai
            </a>
            <a href="<?php echo BASE_URL; ?>leaderboard" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                <i class="fas fa-trophy mr-2"></i>Leaderboard
            </a>
            <a href="<?php echo BASE_URL; ?>stats" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                <i class="fas fa-chart-bar mr-2"></i>Statistik
            </a>
            <?php if (is_logged_in()): ?>
                <a href="<?php echo BASE_URL; ?>kedai/add" class="block px-3 py-2 rounded-lg text-base font-medium text-white gradient-primary">
                    <i class="fas fa-plus-circle mr-2"></i>Tambah Kedai
                </a>
                <div class="border-t border-gray-200 my-2"></div>
                <a href="<?php echo BASE_URL; ?>dashboard" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>profile" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i>Profil
                </a>
                <a href="<?php echo BASE_URL; ?>badges" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-award mr-2"></i>Lencana Saya
                </a>
                <a href="<?php echo BASE_URL; ?>notifications" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-bell mr-2"></i>Notifikasi
                    <?php if ($notification_count > 0): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full">
                            <?php echo $notification_count > 9 ? '9+' : $notification_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php if (is_admin_or_moderator()): ?>
                    <a href="<?php echo BASE_URL; ?>admin" class="block px-3 py-2 rounded-lg text-base font-medium text-red-600 hover:bg-red-50">
                        <i class="fas fa-user-shield mr-2"></i>Admin Panel
                    </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>logout" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            <?php else: ?>
                <a href="<?php echo BASE_URL; ?>login" class="block px-3 py-2 rounded-lg text-base font-medium text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </a>
                <a href="<?php echo BASE_URL; ?>register" class="block px-3 py-2 rounded-lg text-base font-medium text-white gradient-primary">
                    <i class="fas fa-user-plus mr-2"></i>Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Alpine hooks already loaded globally in header -->

<?php if (is_logged_in()): ?>
    <!-- Notification polling script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
            setInterval(fetchNotifications, 30000);
        });

        function fetchNotifications() {
            fetch('<?php echo BASE_URL; ?>api/notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) return;

                    const list = document.getElementById('notificationList');
                    if (!list) return;

                    // Update badge
                    const badges = document.querySelectorAll('.notification-badge');
                    badges.forEach(badge => {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                            badge.style.display = '';
                        } else {
                            badge.style.display = 'none';
                        }
                    });

                    // Update list
                    let html = '';
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 0 ? 'bg-blue-50 font-semibold' : '';
                            const url = notif.link_url ? '<?php echo BASE_URL; ?>' + notif.link_url : '#';
                            const timeAgo = notif.created_at ? '<div class="text-xs text-gray-500 mt-1">' + formatTimeAgo(notif.created_at) + '</div>' : '';
                            html += `<a href="${url}" class="block px-4 py-3 hover:bg-gray-50 ${readClass}">
                                <div class="text-sm text-gray-900">${notif.message}</div>
                                ${timeAgo}
                            </a>`;
                        });
                        html += '<div class="border-t border-gray-200"></div>';
                        html += '<a href="<?php echo BASE_URL; ?>notifications" class="block px-4 py-2 text-center text-sm text-blue-600 hover:bg-blue-50">Lihat Semua Notifikasi</a>';
                    } else {
                        html = '<div class="px-4 py-8 text-center text-sm text-gray-500">Tidak ada notifikasi</div>';
                    }

                    list.innerHTML = html;
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }

        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            if (seconds < 60) return 'Baru saja';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + ' menit yang lalu';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + ' jam yang lalu';
            const days = Math.floor(hours / 24);
            if (days < 30) return days + ' hari yang lalu';
            const months = Math.floor(days / 30);
            if (months < 12) return months + ' bulan yang lalu';
            return Math.floor(months / 12) + ' tahun yang lalu';
        }
    </script>
<?php endif; ?>

<?php if (is_logged_in()): ?>
    <!-- AJAX notification polling script -->
    <script>
        // Fetch notifications on page load
        document.addEventListener('DOMContentLoaded', function() {
            fetchNotifications();
        });

        // Fetch notifications every 30 seconds
        setInterval(function() {
            fetchNotifications();
        }, 30000);

        // Fetch on dropdown click
        document.getElementById('notificationDropdown')?.addEventListener('click', function() {
            fetchNotifications();
        });

        function fetchNotifications() {
            fetch('<?php echo BASE_URL; ?>api/notifications.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(text => {
                    let data = {
                        notifications: [],
                        unread_count: 0
                    };
                    if (text && text.trim() !== '') {
                        try {
                            data = JSON.parse(text);
                        } catch (e) {
                            console.warn('Notifications response not JSON', e);
                            return;
                        }
                    }

                    if (data.error) {
                        console.error('Notification error:', data.error);
                        return;
                    }

                    const list = document.getElementById('notificationList');
                    if (!list) return;

                    // Update badge
                    const badgeContainer = document.querySelector('#notificationDropdown');
                    let badge = badgeContainer?.querySelector('.badge');

                    if (data.unread_count > 0) {
                        if (!badge) {
                            badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            badgeContainer?.appendChild(badge);
                        }
                        badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                        badge.style.display = '';
                    } else {
                        if (badge) badge.style.display = 'none';
                    }

                    // Update list
                    let html = '<li><h6 class="dropdown-header">Notifikasi</h6></li><li><hr class="dropdown-divider"></li>';

                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 0 ? 'bg-light fw-bold' : '';
                            const url = notif.link_url ? '<?php echo BASE_URL; ?>' + notif.link_url : '#';
                            const timeAgo = notif.created_at ? '<small class="text-muted d-block">' + formatTimeAgo(notif.created_at) + '</small>' : '';
                            html += `<li><a class="dropdown-item ${readClass}" href="${url}" style="white-space: normal; max-width: 300px;">
                                ${notif.message}
                                ${timeAgo}
                            </a></li>`;
                        });
                        html += '<li><hr class="dropdown-divider"></li>';
                        html += '<li><a class="dropdown-item text-center small text-primary" href="<?php echo BASE_URL; ?>notifications">Lihat Semua Notifikasi</a></li>';
                    } else {
                        html += '<li><span class="dropdown-item text-muted text-center small">Tidak ada notifikasi</span></li>';
                    }

                    list.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error fetching notifications:', error);
                    const list = document.getElementById('notificationList');
                    if (list) {
                        list.innerHTML = '<li><h6 class="dropdown-header">Notifikasi</h6></li><li><hr class="dropdown-divider"></li><li><span class="dropdown-item text-danger text-center small">Gagal memuat notifikasi</span></li>';
                    }
                });
        }

        // Helper function to format time ago
        function formatTimeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);

            if (seconds < 60) return 'Baru saja';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + ' menit yang lalu';
            const hours = Math.floor(minutes / 60);
            if (hours < 24) return hours + ' jam yang lalu';
            const days = Math.floor(hours / 24);
            if (days < 30) return days + ' hari yang lalu';
            const months = Math.floor(days / 30);
            if (months < 12) return months + ' bulan yang lalu';
            return Math.floor(months / 12) + ' tahun yang lalu';
        }
    </script>
<?php endif; ?>