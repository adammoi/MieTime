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

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
    <div class="container">
        <!-- Logo & Brand -->
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">
            <i class="fas fa-bowl-food me-2"></i>Mie Time
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Left Menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>kedai">
                        <i class="fas fa-store me-1"></i>Kedai
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>search">
                        <i class="fas fa-search me-1"></i>Cari
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>leaderboard">
                        <i class="fas fa-trophy me-1"></i>Leaderboard
                    </a>
                </li>
                <?php if (is_logged_in()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>kedai/add">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Kedai
                        </a>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Right Menu -->
            <ul class="navbar-nav ms-auto">
                <?php if (is_logged_in()): ?>
                    <!-- Notifications -->
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" href="#" id="notificationDropdown"
                            role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if ($notification_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $notification_count > 9 ? '9+' : $notification_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" id="notificationList">
                            <li>
                                <h6 class="dropdown-header">Notifikasi</h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li class="text-center py-3">
                                <div class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </li>
                        </ul>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown"
                            role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>dashboard">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>profile">
                                    <i class="fas fa-user me-2"></i>Profil
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>badges">
                                    <i class="fas fa-award me-2"></i>Lencana Saya
                                </a>
                            </li>
                            <?php if (is_admin_or_moderator()): ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>admin">
                                        <i class="fas fa-user-shield me-2"></i>Admin Panel
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest Menu -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>login">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-2" href="<?php echo BASE_URL; ?>register">
                            <i class="fas fa-user-plus me-1"></i>Daftar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if (is_logged_in()): ?>
    <!-- AJAX notification polling script -->
    <script>
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
                        }
                    }
                    const list = document.getElementById('notificationList');
                    if (!list) return;

                    // Update badge
                    const badge = document.querySelector('#notificationDropdown .badge');
                    if (data.unread_count > 0) {
                        if (badge) {
                            badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                            badge.style.display = '';
                        }
                    } else {
                        if (badge) badge.style.display = 'none';
                    }

                    // Update list
                    let html = '<li><h6 class="dropdown-header">Notifikasi</h6></li><li><hr class="dropdown-divider"></li>';

                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notif => {
                            const readClass = notif.is_read == 0 ? 'bg-light' : '';
                            html += `<li><a class="dropdown-item ${readClass}" href="${notif.link_url || '#'}">${notif.message}</a></li>`;
                        });
                        html += '<li><hr class="dropdown-divider"></li>';
                        html += '<li><a class="dropdown-item text-center small" href="<?php echo BASE_URL; ?>notifications">Lihat Semua</a></li>';
                    } else {
                        html += '<li><span class="dropdown-item text-muted text-center">Tidak ada notifikasi</span></li>';
                    }

                    list.innerHTML = html;
                })
                .catch(error => console.error('Error fetching notifications:', error));
        }
    </script>
<?php endif; ?>