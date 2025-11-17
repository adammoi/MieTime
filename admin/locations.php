<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

require_admin_or_moderator();

// Handle approve/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;

    if ($action === 'approve' && $location_id) {
        db_update('locations', ['status' => 'active'], 'location_id = :location_id', ['location_id' => $location_id]);
        set_flash('success', 'Kedai disetujui');
        redirect('admin/locations');
    }

    if ($action === 'delete' && $location_id) {
        db_delete('locations', 'location_id = ?', [$location_id]);
        set_flash('success', 'Kedai dihapus');
        redirect('admin/locations');
    }
}

$status = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$offset = ($page - 1) * $per_page;

$where = '1=1';
if ($status === 'pending' || $status === 'pending_approval') {
    $where = "status = 'pending_approval'";
}

$total = db_count('locations', $where);
$locations = db_fetch_all("SELECT * FROM locations WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?", [$per_page, $offset]);

$page_title = 'Admin - Kedai';
include '../includes/header.php';
?>

<div class="container-fluid my-4">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-10 px-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0">Manajemen Kedai</h3>
                <div>
                    <a href="<?php echo BASE_URL; ?>admin/locations?status=pending_approval" class="btn btn-sm btn-outline-warning">Pending</a>
                    <a href="<?php echo BASE_URL; ?>admin/locations" class="btn btn-sm btn-outline-secondary">Semua</a>
                </div>
            </div>

            <?php if ($total === 0): ?>
                <div class="alert alert-info">Tidak ada kedai.</div>
            <?php else: ?>
                <div class="mx-auto" style="max-width:1100px;">
                    <div class="card shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive px-3">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:60px;">No</th>
                                            <th>Nama</th>
                                            <th>Alamat</th>
                                            <th>Status</th>
                                            <th>Dibuat</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = $offset + 1;
                                        foreach ($locations as $loc): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($loc['name']); ?></td>
                                                <td><?php echo htmlspecialchars($loc['address']); ?></td>
                                                <td><?php echo htmlspecialchars($loc['status']); ?></td>
                                                <td><small><?php echo format_date_id($loc['created_at']); ?></small></td>
                                                <td>
                                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>kedai/<?php echo (int)$loc['location_id']; ?>" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>

                                                    <?php if ($loc['status'] === 'pending_approval'): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="location_id" value="<?php echo (int)$loc['location_id']; ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button class="btn btn-sm btn-success ms-1">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus kedai ini?');">
                                                        <input type="hidden" name="location_id" value="<?php echo (int)$loc['location_id']; ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button class="btn btn-sm btn-danger ms-1">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $total_pages = (int)ceil($total / $per_page);
                if ($total_pages > 1):
                ?>
                    <nav class="mt-3">
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $p; ?><?php echo $status ? '&status=' . urlencode($status) : ''; ?>"><?php echo $p; ?></a>
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