<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_admin_or_moderator();

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $claim_id = isset($_POST['claim_id']) ? (int)$_POST['claim_id'] : 0;

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // CSRF verification
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        if ($is_ajax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Token CSRF tidak valid']);
            exit;
        }
        set_flash('danger', 'Token CSRF tidak valid');
        redirect('admin/claims');
    }

    if ($action === 'approve' && $claim_id) {
        // Get claim details
        $claim = db_fetch("SELECT * FROM location_claims WHERE claim_id = ?", [$claim_id]);

        if ($claim) {
            // Update claim status
            $ok = db_update('location_claims', [
                'status' => 'approved',
                'admin_notes' => $_POST['admin_notes'] ?? null
            ], 'claim_id = :claim_id', ['claim_id' => $claim_id]);

            if ($ok) {
                // Update user role to verified_owner if not admin/moderator
                $user = get_user_by_id($claim['user_id']);
                if ($user && !in_array($user['role'], ['admin', 'moderator'])) {
                    db_update('users', ['role' => 'verified_owner'], 'user_id = :user_id', ['user_id' => $claim['user_id']]);

                    // Auto-assign role badge
                    assign_role_badge($claim['user_id']);

                    // Send email notification to user
                    try {
                        send_verified_owner_email($claim['user_id'], $claim['location_id']);
                    } catch (Exception $e) {
                        error_log('Failed to send verified owner email: ' . $e->getMessage());
                    }
                }                // Create notification
                $location = get_location_by_id($claim['location_id']);
                create_notification(
                    $claim['user_id'],
                    "🎉 Klaim Anda untuk <strong>{$location['name']}</strong> telah disetujui! Anda sekarang adalah Verified Owner.",
                    "kedai/{$claim['location_id']}"
                );

                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true]);
                    exit;
                }
                set_flash('success', 'Klaim disetujui');
            } else {
                if ($is_ajax) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'Gagal menyetujui klaim']);
                    exit;
                }
                set_flash('danger', 'Gagal menyetujui klaim');
            }
        }
        redirect('admin/claims');
    }

    if ($action === 'reject' && $claim_id) {
        $ok = db_update('location_claims', [
            'status' => 'rejected',
            'admin_notes' => $_POST['admin_notes'] ?? null
        ], 'claim_id = :claim_id', ['claim_id' => $claim_id]);

        if ($ok) {
            // Get claim for notification
            $claim = db_fetch("SELECT * FROM location_claims WHERE claim_id = ?", [$claim_id]);
            if ($claim) {
                $location = get_location_by_id($claim['location_id']);
                create_notification(
                    $claim['user_id'],
                    "Klaim Anda untuk <strong>{$location['name']}</strong> ditolak. Silakan hubungi admin untuk informasi lebih lanjut.",
                    "kedai/{$claim['location_id']}"
                );
            }

            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }
            set_flash('success', 'Klaim ditolak');
        } else {
            if ($is_ajax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Gagal menolak klaim']);
                exit;
            }
            set_flash('danger', 'Gagal menolak klaim');
        }
        redirect('admin/claims');
    }
}

$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = '1=1';
$params = [];
if ($status === 'pending') {
    $where = "lc.status = 'pending'";
} elseif ($status === 'approved') {
    $where = "lc.status = 'approved'";
} elseif ($status === 'rejected') {
    $where = "lc.status = 'rejected'";
}

$total = db_count('location_claims lc', $where, $params);
$claims = db_fetch_all("
    SELECT lc.*, u.username, l.name as location_name, l.address as location_address
    FROM location_claims lc
    JOIN users u ON lc.user_id = u.user_id
    JOIN locations l ON lc.location_id = l.location_id
    WHERE $where
    ORDER BY lc.created_at DESC
    LIMIT ? OFFSET ?
", array_merge($params, [$per_page, $offset]));

$csrf_token = generate_csrf_token();

$page_title = 'Admin - Klaim Kedai';
include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 gap-6">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <div class="md:col-span-3 lg:col-span-3 admin-container p-0">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-building mr-2"></i>Manajemen Klaim Kedai
                </h3>
                <div class="flex gap-2">
                    <a href="<?php echo BASE_URL; ?>admin/claims?status=pending" class="px-4 py-2 border-2 border-yellow-500 text-yellow-600 font-semibold rounded-lg hover:bg-yellow-50 transition">Pending</a>
                    <a href="<?php echo BASE_URL; ?>admin/claims?status=approved" class="px-4 py-2 border-2 border-green-600 text-green-600 font-semibold rounded-lg hover:bg-green-50 transition">Disetujui</a>
                    <a href="<?php echo BASE_URL; ?>admin/claims?status=rejected" class="px-4 py-2 border-2 border-red-600 text-red-600 font-semibold rounded-lg hover:bg-red-50 transition">Ditolak</a>
                    <a href="<?php echo BASE_URL; ?>admin/claims" class="px-4 py-2 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-100 transition">Semua</a>
                </div>
            </div>

            <?php if ($total === 0): ?>
                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-lg">Tidak ada klaim.</div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900" style="width:50px;">No</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">User</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Kedai</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Verifikasi</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Tanggal</th>
                                    <th class="px-4 py-3 text-left text-sm font-bold text-gray-900">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php $no = $offset + 1;
                                foreach ($claims as $claim): ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-4 py-4"><?php echo $no++; ?></td>
                                        <td class="px-4 py-4 font-medium">
                                            <?php echo htmlspecialchars($claim['username']); ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <strong class="text-gray-900"><?php echo htmlspecialchars($claim['location_name']); ?></strong>
                                            <small class="block text-gray-600"><?php echo htmlspecialchars($claim['location_address']); ?></small>
                                        </td>
                                        <td class="px-4 py-4">
                                            <?php if ($claim['verification_type'] === 'document'): ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-full text-xs font-semibold">Dokumen</span>
                                            <?php elseif ($claim['verification_type'] === 'phone'): ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-sky-500 text-white rounded-full text-xs font-semibold">Telepon</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-gray-600 text-white rounded-full text-xs font-semibold">Email</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4">
                                            <?php if ($claim['status'] === 'pending'): ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-yellow-500 text-white rounded-full text-xs font-semibold">Pending</span>
                                            <?php elseif ($claim['status'] === 'approved'): ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-full text-xs font-semibold">Disetujui</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-3 py-1 bg-red-600 text-white rounded-full text-xs font-semibold">Ditolak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-600"><?php echo format_date_id($claim['created_at']); ?></td>
                                        <td class="px-4 py-4">
                                            <button type="button" class="px-3 py-2 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewClaimModal"
                                                data-claim='<?php echo htmlspecialchars(json_encode($claim), ENT_QUOTES); ?>'>
                                                <i class="fas fa-eye"></i>
                                            </button>

                                            <?php if ($claim['status'] === 'pending'): ?>
                                                <button type="button" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition ml-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#approveModal"
                                                    data-claim-id="<?php echo $claim['claim_id']; ?>"
                                                    data-claim-name="<?php echo htmlspecialchars($claim['location_name']); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="button" class="px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition ml-1"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal"
                                                    data-claim-id="<?php echo $claim['claim_id']; ?>"
                                                    data-claim-name="<?php echo htmlspecialchars($claim['location_name']); ?>">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?>
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
                                        href="?page=<?php echo $p; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo $p; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Claim Modal -->
<div class="modal fade" id="viewClaimModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Klaim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="claimDetailContent">
                <!-- Will be filled by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="claim_id" id="approve_claim_id">
                <input type="hidden" name="action" value="approve">
                <div class="modal-header">
                    <h5 class="modal-title">Setujui Klaim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Setujui klaim untuk <strong id="approve_claim_name"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Catatan Admin (Opsional)</label>
                        <textarea class="form-control" name="admin_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="claim_id" id="reject_claim_id">
                <input type="hidden" name="action" value="reject">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Klaim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tolak klaim untuk <strong id="reject_claim_name"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="admin_notes" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // View claim details
        var viewModal = document.getElementById('viewClaimModal');
        if (viewModal) {
            viewModal.addEventListener('show.bs.modal', function(e) {
                var button = e.relatedTarget;
                var claim = JSON.parse(button.getAttribute('data-claim'));
                var content = document.getElementById('claimDetailContent');

                var html = '<dl class="row">';
                html += '<dt class="col-sm-4">User</dt><dd class="col-sm-8">' + claim.username + '</dd>';
                html += '<dt class="col-sm-4">Kedai</dt><dd class="col-sm-8"><strong>' + claim.location_name + '</strong><br><small class="text-muted">' + claim.location_address + '</small></dd>';
                html += '<dt class="col-sm-4">Verifikasi</dt><dd class="col-sm-8">' + claim.verification_type + '</dd>';
                html += '<dt class="col-sm-4">Status</dt><dd class="col-sm-8"><span class="badge bg-' + (claim.status === 'approved' ? 'success' : claim.status === 'rejected' ? 'danger' : 'warning') + '">' + claim.status + '</span></dd>';

                if (claim.document_path) {
                    html += '<dt class="col-sm-4">Dokumen</dt><dd class="col-sm-8"><a href="<?php echo BASE_URL; ?>uploads/' + claim.document_path + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-file-pdf"></i> Lihat Dokumen</a></dd>';
                }

                if (claim.notes) {
                    html += '<dt class="col-sm-4">Catatan User</dt><dd class="col-sm-8">' + claim.notes + '</dd>';
                }

                if (claim.admin_notes) {
                    html += '<dt class="col-sm-4">Catatan Admin</dt><dd class="col-sm-8">' + claim.admin_notes + '</dd>';
                }

                html += '<dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8">' + claim.created_at + '</dd>';
                html += '</dl>';

                content.innerHTML = html;
            });
        }

        // Approve modal
        var approveModal = document.getElementById('approveModal');
        if (approveModal) {
            approveModal.addEventListener('show.bs.modal', function(e) {
                var button = e.relatedTarget;
                document.getElementById('approve_claim_id').value = button.getAttribute('data-claim-id');
                document.getElementById('approve_claim_name').textContent = button.getAttribute('data-claim-name');
            });
        }

        // Reject modal
        var rejectModal = document.getElementById('rejectModal');
        if (rejectModal) {
            rejectModal.addEventListener('show.bs.modal', function(e) {
                var button = e.relatedTarget;
                document.getElementById('reject_claim_id').value = button.getAttribute('data-claim-id');
                document.getElementById('reject_claim_name').textContent = button.getAttribute('data-claim-name');
            });
        }
    });
</script>

</div>
</div>

<?php include '../includes/footer.php'; ?>