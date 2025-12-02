<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Admin only
require_admin();

$current = 'badges';
$page_title = 'Kelola Badges';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? '')) {
        set_flash('danger', 'CSRF token tidak valid');
        redirect('admin/badges');
    }

    $action = $_POST['action'] ?? '';

    // Create badge
    if ($action === 'create') {
        $badge_name = trim($_POST['badge_name'] ?? '');
        $badge_description = trim($_POST['badge_description'] ?? '');
        $badge_icon = trim($_POST['badge_icon'] ?? 'fa-award');
        $badge_type = $_POST['badge_type'] ?? 'achievement';
        $trigger_condition = trim($_POST['trigger_condition'] ?? '');

        if (empty($badge_name) || empty($trigger_condition)) {
            set_flash('danger', 'Nama badge dan trigger condition harus diisi');
        } else {
            $result = db_insert('badges', [
                'badge_name' => $badge_name,
                'badge_description' => $badge_description,
                'badge_icon' => $badge_icon,
                'badge_type' => $badge_type,
                'trigger_condition' => $trigger_condition
            ]);

            if ($result) {
                set_flash('success', 'Badge berhasil ditambahkan');
            } else {
                set_flash('danger', 'Gagal menambahkan badge');
            }
        }
        redirect('admin/badges');
    }

    // Update badge
    if ($action === 'update') {
        $badge_id = (int)($_POST['badge_id'] ?? 0);
        $badge_name = trim($_POST['badge_name'] ?? '');
        $badge_description = trim($_POST['badge_description'] ?? '');
        $badge_icon = trim($_POST['badge_icon'] ?? 'fa-award');
        $badge_type = $_POST['badge_type'] ?? 'achievement';
        $trigger_condition = trim($_POST['trigger_condition'] ?? '');

        if ($badge_id > 0 && !empty($badge_name) && !empty($trigger_condition)) {
            $result = db_update('badges', [
                'badge_name' => $badge_name,
                'badge_description' => $badge_description,
                'badge_icon' => $badge_icon,
                'badge_type' => $badge_type,
                'trigger_condition' => $trigger_condition
            ], 'badge_id = :badge_id', ['badge_id' => $badge_id]);

            if ($result !== false) {
                set_flash('success', 'Badge berhasil diupdate');
            } else {
                set_flash('danger', 'Gagal mengupdate badge');
            }
        } else {
            set_flash('danger', 'Data tidak valid');
        }
        redirect('admin/badges');
    }

    // Delete badge
    if ($action === 'delete') {
        $badge_id = (int)($_POST['badge_id'] ?? 0);
        if ($badge_id > 0) {
            $result = db_delete('badges', 'badge_id = :badge_id', ['badge_id' => $badge_id]);
            if ($result) {
                set_flash('success', 'Badge berhasil dihapus');
            } else {
                set_flash('danger', 'Gagal menghapus badge');
            }
        }
        redirect('admin/badges');
    }

    // Award badge to user
    if ($action === 'award') {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $badge_id = (int)($_POST['badge_id'] ?? 0);

        if ($user_id > 0 && $badge_id > 0) {
            // Check if already has badge
            $existing = db_fetch("SELECT * FROM user_badges WHERE user_id = ? AND badge_id = ?", [$user_id, $badge_id]);
            if ($existing) {
                set_flash('warning', 'User sudah memiliki badge ini');
            } else {
                $result = db_insert('user_badges', [
                    'user_id' => $user_id,
                    'badge_id' => $badge_id,
                    'earned_at' => date('Y-m-d H:i:s')
                ]);

                if ($result) {
                    // Create notification
                    $badge = db_fetch("SELECT badge_name FROM badges WHERE badge_id = ?", [$badge_id]);
                    $user = get_user_by_id($user_id);
                    if ($badge && $user) {
                        create_notification(
                            $user_id,
                            "🏆 Selamat! Anda mendapatkan badge <strong>" . htmlspecialchars($badge['badge_name']) . "</strong>",
                            "pages/user/badges"
                        );
                    }
                    set_flash('success', 'Badge berhasil diberikan ke user');
                } else {
                    set_flash('danger', 'Gagal memberikan badge');
                }
            }
        }
        redirect('admin/badges');
    }
}

// Get all badges
$badges = db_fetch_all("SELECT * FROM badges ORDER BY created_at DESC");

// Get badge statistics
$badge_stats = db_fetch_all("
    SELECT b.badge_id, b.badge_name, COUNT(ub.user_id) as user_count
    FROM badges b
    LEFT JOIN user_badges ub ON b.badge_id = ub.badge_id
    GROUP BY b.badge_id
    ORDER BY user_count DESC
");

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-6">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="md:col-span-3 lg:col-span-3 admin-container">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-1"><i class="fas fa-medal mr-2"></i>Kelola Badges</h2>
                    <p class="text-gray-600">Tambah, edit, atau hapus badges dan berikan ke user</p>
                </div>
                <button class="px-6 py-3 gradient-primary text-white font-bold rounded-lg hover-lift transition" data-bs-toggle="modal" data-bs-target="#createBadgeModal">
                    <i class="fas fa-plus mr-2"></i>Tambah Badge
                </button>
            </div>

            <?php
            $success = get_flash('success');
            $danger = get_flash('danger');
            $warning = get_flash('warning');
            ?>
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            <?php if ($danger): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
                    <?php echo $danger; ?>
                </div>
            <?php endif; ?>
            <?php if ($warning): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
                    <?php echo $warning; ?>
                </div>
            <?php endif; ?>

            <!-- Badge Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white border-2 border-blue-500 rounded-2xl shadow-lg p-6">
                    <h6 class="text-gray-600 mb-2">Total Badges</h6>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo count($badges); ?></h3>
                </div>
                <div class="bg-white border-2 border-green-500 rounded-2xl shadow-lg p-6">
                    <h6 class="text-gray-600 mb-2">Achievement Badges</h6>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo count(array_filter($badges, fn($b) => $b['badge_type'] === 'achievement')); ?></h3>
                </div>
                <div class="bg-white border-2 border-yellow-500 rounded-2xl shadow-lg p-6">
                    <h6 class="text-gray-600 mb-2">Participation Badges</h6>
                    <h3 class="text-3xl font-bold text-gray-900"><?php echo count(array_filter($badges, fn($b) => $b['badge_type'] === 'participation')); ?></h3>
                </div>
            </div>

            <!-- Badges Table -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gray-100 p-6">
                    <h5 class="font-bold text-xl text-gray-900 mb-0">Daftar Badges</h5>
                </div>
                <?php if (empty($badges)): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-6 m-6">Belum ada badges.</div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width: 60px;">Icon</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Nama</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Deskripsi</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width: 120px;">Tipe</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width: 200px;">Trigger Condition</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width: 100px;">User Count</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($badges as $badge):
                                    $stat = array_values(array_filter($badge_stats, fn($s) => $s['badge_id'] == $badge['badge_id']));
                                    $user_count = $stat ? $stat[0]['user_count'] : 0;
                                ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-4 text-center">
                                            <i class="fas <?php echo htmlspecialchars($badge['badge_icon']); ?> fa-2x text-yellow-500"></i>
                                        </td>
                                        <td class="px-4 py-4 font-bold text-gray-900"><?php echo htmlspecialchars($badge['badge_name']); ?></td>
                                        <td class="px-4 py-4 text-gray-700"><?php echo htmlspecialchars($badge['badge_description']); ?></td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex items-center px-3 py-1 <?php echo $badge['badge_type'] === 'achievement' ? 'bg-green-600' : 'bg-sky-500'; ?> text-white rounded-full text-xs font-semibold">
                                                <?php echo ucfirst($badge['badge_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4"><code class="text-xs bg-gray-100 px-2 py-1 rounded"><?php echo htmlspecialchars($badge['trigger_condition']); ?></code></td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="inline-flex items-center px-3 py-1 bg-gray-600 text-white rounded-full text-xs font-semibold"><?php echo $user_count; ?> users</span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <button class="px-3 py-2 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition mr-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editBadgeModal"
                                                data-id="<?php echo $badge['badge_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($badge['badge_name']); ?>"
                                                data-description="<?php echo htmlspecialchars($badge['badge_description']); ?>"
                                                data-icon="<?php echo htmlspecialchars($badge['badge_icon']); ?>"
                                                data-type="<?php echo $badge['badge_type']; ?>"
                                                data-trigger="<?php echo htmlspecialchars($badge['trigger_condition']); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="px-3 py-2 border-2 border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition mr-1"
                                                data-bs-toggle="modal"
                                                data-bs-target="#awardBadgeModal"
                                                data-id="<?php echo $badge['badge_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                                                <i class="fas fa-gift"></i>
                                            </button>
                                            <button class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
                                                onclick="if(confirm('Yakin hapus badge ini?')) document.getElementById('delete-form-<?php echo $badge['badge_id']; ?>').submit()">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <form id="delete-form-<?php echo $badge['badge_id']; ?>" method="POST" class="hidden">
                                                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="badge_id" value="<?php echo $badge['badge_id']; ?>">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Badge Modal -->
    <div class="modal fade" id="createBadgeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="create">

                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Badge Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Badge <span class="text-danger">*</span></label>
                            <input type="text" name="badge_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="badge_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <div class="row g-2">
                                <div class="col-9">
                                    <select name="badge_icon" id="create_badge_icon" class="form-select" required>
                                        <option value="fa-award">🏆 Award</option>
                                        <option value="fa-trophy">🏆 Trophy</option>
                                        <option value="fa-medal">🥇 Medal</option>
                                        <option value="fa-star">⭐ Star</option>
                                        <option value="fa-crown">👑 Crown</option>
                                        <option value="fa-certificate">📜 Certificate</option>
                                        <option value="fa-gem">💎 Gem</option>
                                        <option value="fa-fire">🔥 Fire</option>
                                        <option value="fa-heart">❤️ Heart</option>
                                        <option value="fa-thumbs-up">👍 Thumbs Up</option>
                                        <option value="fa-shield-alt">🛡️ Shield</option>
                                        <option value="fa-user-shield">🛡️ User Shield</option>
                                        <option value="fa-rocket">🚀 Rocket</option>
                                        <option value="fa-bolt">⚡ Bolt</option>
                                        <option value="fa-graduation-cap">🎓 Graduate</option>
                                        <option value="fa-hands-helping">🤝 Helping Hands</option>
                                        <option value="fa-comment">💬 Comment</option>
                                        <option value="fa-camera">📷 Camera</option>
                                    </select>
                                </div>
                                <div class="col-3 text-center">
                                    <i id="create_icon_preview" class="fas fa-award fa-3x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Badge</label>
                            <select name="badge_type" class="form-select">
                                <option value="achievement">Achievement - Dicapai dengan usaha</option>
                                <option value="participation">Participation - Otomatis/Role based</option>
                            </select>
                            <small class="text-muted">
                                <strong>Achievement:</strong> Badge yang harus dicapai dengan effort (review 10x, dapat 100 upvotes, dll).<br>
                                <strong>Participation:</strong> Badge otomatis berdasarkan role atau keikutsertaan (admin, moderator, early adopter).
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trigger Condition <span class="text-danger">*</span></label>
                            <input type="text" name="trigger_condition" class="form-control" placeholder="review_count >= 10" required>
                            <small class="text-muted">Contoh: review_count >= 10, upvotes >= 100, role = 'admin'</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Badge Modal -->
    <div class="modal fade" id="editBadgeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="badge_id" id="edit_badge_id">

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Badge</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Badge <span class="text-danger">*</span></label>
                            <input type="text" name="badge_name" id="edit_badge_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="badge_description" id="edit_badge_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Icon</label>
                            <div class="row g-2">
                                <div class="col-9">
                                    <select name="badge_icon" id="edit_badge_icon" class="form-select" required>
                                        <option value="fa-award">🏆 Award</option>
                                        <option value="fa-trophy">🏆 Trophy</option>
                                        <option value="fa-medal">🥇 Medal</option>
                                        <option value="fa-star">⭐ Star</option>
                                        <option value="fa-crown">👑 Crown</option>
                                        <option value="fa-certificate">📜 Certificate</option>
                                        <option value="fa-gem">💎 Gem</option>
                                        <option value="fa-fire">🔥 Fire</option>
                                        <option value="fa-heart">❤️ Heart</option>
                                        <option value="fa-thumbs-up">👍 Thumbs Up</option>
                                        <option value="fa-shield-alt">🛡️ Shield</option>
                                        <option value="fa-user-shield">🛡️ User Shield</option>
                                        <option value="fa-rocket">🚀 Rocket</option>
                                        <option value="fa-bolt">⚡ Bolt</option>
                                        <option value="fa-graduation-cap">🎓 Graduate</option>
                                        <option value="fa-hands-helping">🤝 Helping Hands</option>
                                        <option value="fa-comment">💬 Comment</option>
                                        <option value="fa-camera">📷 Camera</option>
                                    </select>
                                </div>
                                <div class="col-3 text-center">
                                    <i id="edit_icon_preview" class="fas fa-award fa-3x text-warning"></i>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipe Badge</label>
                            <select name="badge_type" id="edit_badge_type" class="form-select">
                                <option value="achievement">Achievement - Dicapai dengan usaha</option>
                                <option value="participation">Participation - Otomatis/Role based</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trigger Condition <span class="text-danger">*</span></label>
                            <input type="text" name="trigger_condition" id="edit_trigger_condition" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Award Badge Modal -->
    <div class="modal fade" id="awardBadgeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="award">
                    <input type="hidden" name="badge_id" id="award_badge_id">

                    <div class="modal-header">
                        <h5 class="modal-title">Berikan Badge: <span id="award_badge_name"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Cari User <span class="text-danger">*</span></label>
                            <input type="text" id="user_search" class="form-control" placeholder="Ketik username atau email..." autocomplete="off">
                            <input type="hidden" name="user_id" id="selected_user_id" required>
                            <div id="user_search_results" class="list-group mt-2" style="max-height: 300px; overflow-y: auto;"></div>
                        </div>
                        <div id="selected_user_info" class="alert alert-info" style="display: none;">
                            <strong>User Terpilih:</strong> <span id="selected_user_display"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Berikan Badge</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Icon preview for create modal
        document.getElementById('create_badge_icon').addEventListener('change', function() {
            const iconClass = this.value;
            document.getElementById('create_icon_preview').className = 'fas ' + iconClass + ' fa-3x text-warning';
        });

        // Edit badge modal
        document.getElementById('editBadgeModal').addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            document.getElementById('edit_badge_id').value = btn.dataset.id;
            document.getElementById('edit_badge_name').value = btn.dataset.name;
            document.getElementById('edit_badge_description').value = btn.dataset.description;
            document.getElementById('edit_badge_icon').value = btn.dataset.icon;
            document.getElementById('edit_badge_type').value = btn.dataset.type;
            document.getElementById('edit_trigger_condition').value = btn.dataset.trigger;

            // Update icon preview
            document.getElementById('edit_icon_preview').className = 'fas ' + btn.dataset.icon + ' fa-3x text-warning';
        });

        // Icon preview for edit modal
        document.getElementById('edit_badge_icon').addEventListener('change', function() {
            const iconClass = this.value;
            document.getElementById('edit_icon_preview').className = 'fas ' + iconClass + ' fa-3x text-warning';
        });

        // Award badge modal
        document.getElementById('awardBadgeModal').addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            document.getElementById('award_badge_id').value = btn.dataset.id;
            document.getElementById('award_badge_name').textContent = btn.dataset.name;

            // Reset search
            document.getElementById('user_search').value = '';
            document.getElementById('selected_user_id').value = '';
            document.getElementById('user_search_results').innerHTML = '';
            document.getElementById('selected_user_info').style.display = 'none';
        });

        // User search functionality
        let searchTimeout;
        document.getElementById('user_search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                document.getElementById('user_search_results').innerHTML = '';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch('<?php echo BASE_URL; ?>admin/user_api.php?action=search&q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        const resultsDiv = document.getElementById('user_search_results');
                        if (data.users && data.users.length > 0) {
                            resultsDiv.innerHTML = data.users.map(user => `
                        <button type="button" class="list-group-item list-group-item-action" 
                                onclick="selectUser(${user.user_id}, '${user.username.replace(/'/g, "\\'")}', '${user.email.replace(/'/g, "\\'")}')"
                                style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${user.username}</strong><br>
                                    <small class="text-muted">${user.email}</small>
                                </div>
                                <span class="badge bg-secondary">${user.role}</span>
                            </div>
                        </button>
                    `).join('');
                        } else {
                            resultsDiv.innerHTML = '<div class="list-group-item text-muted">Tidak ada user ditemukan</div>';
                        }
                    })
                    .catch(err => {
                        console.error('Search error:', err);
                        document.getElementById('user_search_results').innerHTML = '<div class="list-group-item text-danger">Error mencari user</div>';
                    });
            }, 300);
        });

        function selectUser(userId, username, email) {
            document.getElementById('selected_user_id').value = userId;
            document.getElementById('selected_user_display').textContent = username + ' (' + email + ')';
            document.getElementById('selected_user_info').style.display = 'block';
            document.getElementById('user_search_results').innerHTML = '';
            document.getElementById('user_search').value = username;
        }
    </script>

</div>
</div>

<?php include '../includes/footer.php'; ?>