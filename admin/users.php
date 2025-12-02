<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_admin_or_moderator();

// Small migration: ensure users table has is_banned column
try {
    $col = db_fetch("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_banned'", [DB_NAME]);
    if (!$col) {
        db_query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER points");
    }
} catch (Exception $e) {
    // ignore migration errors (user may not have ALTER privileges)
}

// Handle POST actions: change_role, ban, unban, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    // detect AJAX
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (!verify_csrf_token($_POST[CSRF_TOKEN_NAME] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['error' => 'CSRF token tidak valid']);
            exit;
        }
        set_flash('error', 'CSRF token tidak valid');
        redirect('admin/users');
    }

    if ($user_id <= 0) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['error' => 'User tidak ditemukan']);
            exit;
        }
        set_flash('error', 'User tidak ditemukan');
        redirect('admin/users');
    }

    // Prevent acting on self for destructive actions
    if ($user_id === get_current_user_id() && in_array($action, ['delete', 'ban'])) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode(['error' => 'Anda tidak dapat melakukan tindakan ini pada diri sendiri']);
            exit;
        }
        set_flash('error', 'Anda tidak dapat melakukan tindakan ini pada diri sendiri');
        redirect('admin/users');
    }

    if ($action === 'change_role') {
        $new_role = $_POST['new_role'] ?? '';
        $allowed = ['admin', 'moderator', 'contributor', 'verified_owner'];
        if (!in_array($new_role, $allowed)) {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(400);
                echo json_encode(['error' => 'Peran tidak valid']);
                exit;
            }
            set_flash('error', 'Peran tidak valid');
        } else {
            db_update('users', ['role' => $new_role], 'user_id = :user_id', ['user_id' => $user_id]);

            // Auto-assign role badge if role is admin, moderator, or verified_owner
            if (in_array($new_role, ['admin', 'moderator', 'verified_owner'])) {
                assign_role_badge($user_id);
            }

            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }
            set_flash('success', 'Peran pengguna diperbarui');
        }
        if (!$is_ajax) redirect('admin/users');
    }

    if ($action === 'ban' || $action === 'unban') {
        $is_banned_val = $action === 'ban' ? 1 : 0;
        db_update('users', ['is_banned' => $is_banned_val], 'user_id = :user_id', ['user_id' => $user_id]);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
            exit;
        }
        set_flash('success', $is_banned_val ? 'Pengguna diblokir' : 'Pengguna diaktifkan kembali');
        redirect('admin/users');
    }

    if ($action === 'delete') {
        // Prevent deleting last admin
        $target = db_fetch('SELECT role FROM users WHERE user_id = ?', [$user_id]);
        if ($target && $target['role'] === 'admin') {
            $admin_count = db_count('users', "role = 'admin'");
            if ($admin_count <= 1) {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    http_response_code(400);
                    echo json_encode(['error' => 'Tidak dapat menghapus admin terakhir']);
                    exit;
                }
                set_flash('error', 'Tidak dapat menghapus admin terakhir');
                redirect('admin/users');
            }
        }

        db_delete('users', 'user_id = ?', [$user_id]);
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
            exit;
        }
        set_flash('success', 'Pengguna dihapus');
        redirect('admin/users');
    }
}

// Listing with search & pagination
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = '1=1';
$count_params = [];
$fetch_params = [];
if ($q !== '') {
    $where = '(username LIKE ? OR email LIKE ?)';
    $count_params = ["%$q%", "%$q%"];
    $fetch_params = ["%$q%", "%$q%"];
}

$total = db_count('users', $where, $count_params);

$sql = "SELECT * FROM users WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
// merge params for fetch
foreach ([$per_page, $offset] as $p) {
    $fetch_params[] = $p;
}

$users = db_fetch_all($sql, $fetch_params);

$csrf_token = generate_csrf_token();
$page_title = 'Admin - Pengguna';
include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-6">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="md:col-span-3 lg:col-span-3 admin-container">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-4xl font-bold mb-2">Manajemen Pengguna</h2>
                    <p class="text-gray-600">Kelola akun pengguna platform</p>
                </div>
                <form class="flex gap-2" method="GET" action="">
                    <input type="text" name="q" class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                        placeholder="Cari username atau email" value="<?php echo htmlspecialchars($q); ?>">
                    <button class="px-6 py-2 gradient-primary text-white font-bold rounded-lg hover-lift transition">Cari</button>
                </form>
            </div>

            <?php if ($total === 0): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">Tidak ada pengguna ditemukan.</div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:60px">No</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Username</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Email</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:120px">Peran</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:80px">Reviews</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:80px">Poin</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:140px">Terdaftar</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:120px">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:180px">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php $no = $offset + 1;
                                foreach ($users as $u): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-4"><?php echo $no++; ?></td>
                                        <td class="px-4 py-4 font-medium"><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td class="px-4 py-4 text-gray-600"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td class="px-4 py-4"><?php echo htmlspecialchars($u['role']); ?> <?php echo get_role_badge($u['role']); ?></td>
                                        <td class="px-4 py-4"><?php echo (int)($u['review_count'] ?? 0); ?></td>
                                        <td class="px-4 py-4 font-bold text-blue-600"><?php echo (int)($u['points'] ?? 0); ?></td>
                                        <td class="px-4 py-4 text-sm text-gray-600"><?php echo format_date_id($u['created_at']); ?></td>
                                        <td class="px-4 py-4">
                                            <?php if (!empty($u['is_banned'])): ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-gray-800 text-white rounded-full text-xs font-semibold">Diblokir</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-full text-xs font-semibold">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <button type="button" class="px-3 py-2 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal" data-user-id="<?php echo (int)$u['user_id']; ?>" title="Edit pengguna">
                                                <i class="fas fa-pen"></i>
                                            </button>

                                            <form method="POST" class="inline ml-2 ajax-delete-user" data-user-id="<?php echo (int)$u['user_id']; ?>">
                                                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php
                $total_pages = (int)ceil($total / $per_page);
                if ($total_pages > 1):
                ?>
                    <nav class="mt-6">
                        <ul class="flex justify-center gap-2">
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li>
                                    <a class="px-4 py-2 border-2 <?php echo $p === $page ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-300 text-gray-700 hover:bg-gray-100'; ?> font-semibold rounded-lg transition"
                                        href="?page=<?php echo $p; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Edit user modal (placeholder) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div id="editUserAlert" style="display:none;" class="alert" role="alert"></div>
                <form id="editUserForm">
                    <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="user_id" id="modal_user_id" value="">

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" id="modal_username" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" id="modal_email" name="email">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Peran</label>
                        <select class="form-select" id="modal_role" name="role">
                            <?php foreach (['admin', 'moderator', 'contributor', 'verified_owner'] as $r): ?>
                                <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="modal_is_banned" name="is_banned">
                        <label class="form-check-label" for="modal_is_banned">Diblokir</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Simpan perubahan</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var editModal = document.getElementById('editUserModal');
        if (!editModal) return;
        editModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-user-id');
            loadUser(userId);
        });
    });

    function showAlert(message, type) {
        var alertEl = document.getElementById('editUserAlert');
        if (!alertEl) return;
        alertEl.className = 'alert alert-' + (type || 'info');
        alertEl.textContent = message;
        alertEl.style.display = 'block';
    }

    function clearAlert() {
        var alertEl = document.getElementById('editUserAlert');
        if (!alertEl) return;
        alertEl.style.display = 'none';
        alertEl.textContent = '';
    }

    function loadUser(userId) {
        clearAlert();
        var form = document.getElementById('editUserForm');
        if (!form) return;
        document.getElementById('modal_user_id').value = userId;
        fetch('<?php echo BASE_URL; ?>admin/user_api.php?user_id=' + encodeURIComponent(userId), {
                credentials: 'same-origin'
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(json) {
                if (json.error) {
                    showAlert(json.error, 'danger');
                    return;
                }
                var u = json.user;
                document.getElementById('modal_username').value = u.username || '';
                document.getElementById('modal_email').value = u.email || '';
                document.getElementById('modal_role').value = u.role || 'contributor';
                document.getElementById('modal_is_banned').checked = !!u.is_banned;
            })
            .catch(function(err) {
                showAlert('Gagal memuat data pengguna', 'danger');
                console.error(err);
            });
    }

    document.getElementById('saveUserBtn').addEventListener('click', function() {
        var form = document.getElementById('editUserForm');
        if (!form) return;
        clearAlert();
        var data = new FormData(form);
        var btn = document.getElementById('saveUserBtn');
        var prevHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';

        fetch('<?php echo BASE_URL; ?>admin/user_api.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function(res) {
                return res.json();
            })
            .then(function(json) {
                if (json.error) {
                    showAlert(json.error, 'danger');
                    btn.disabled = false;
                    btn.innerHTML = prevHtml;
                    return;
                }
                // success: close modal and reload to reflect changes
                var modalEl = document.getElementById('editUserModal');
                var bsModal = bootstrap.Modal.getInstance(modalEl);
                if (bsModal) bsModal.hide();
                location.reload();
            })
            .catch(function(err) {
                showAlert('Gagal menyimpan perubahan', 'danger');
                console.error(err);
                btn.disabled = false;
                btn.innerHTML = prevHtml;
            });
    });

    // Helper: show a small toast-like alert (auto-dismiss)
    function showToast(message, type) {
        type = type || 'success';
        var el = document.createElement('div');
        el.className = 'alert alert-' + type + ' shadow-sm';
        el.style.position = 'fixed';
        el.style.top = '1rem';
        el.style.right = '1rem';
        el.style.zIndex = 1080;
        el.textContent = message;
        document.body.appendChild(el);
        setTimeout(function() {
            el.style.opacity = '0';
            setTimeout(function() {
                el.remove();
            }, 300);
        }, 2500);
    }

    // Attach AJAX delete handlers to delete forms
    function bindAjaxDeleteUsers() {
        var forms = document.querySelectorAll('form.ajax-delete-user');
        forms.forEach(function(form) {
            if (form.__bound) return; // prevent double-bind
            form.__bound = true;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var userId = form.getAttribute('data-user-id') || form.querySelector('input[name="user_id"]').value;
                if (!confirm('Hapus pengguna ini? Tindakan ini tidak dapat dibatalkan.')) return;
                var data = new FormData(form);
                fetch((form.getAttribute('action') || window.location.href), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: data
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(json) {
                        if (json.error) {
                            showToast(json.error, 'danger');
                            return;
                        }
                        // remove row from DOM
                        var row = form.closest('tr');
                        if (row) row.remove();
                        showToast('Pengguna dihapus', 'success');
                    })
                    .catch(function(err) {
                        console.error(err);
                        showToast('Gagal menghapus pengguna', 'danger');
                    });
            });
        });
    }

    // bind on load
    bindAjaxDeleteUsers();
</script>