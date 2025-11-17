<?php
if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Public leaderboard: top users by upvotes, then by reviews
$page_title = 'Leaderboard';
include '../../includes/header.php';

// Query top users (show up to 50)
$leaderboard = db_fetch_all(
    "SELECT u.user_id, u.username, u.role, COALESCE(SUM(r.upvotes), 0) AS upvotes, COUNT(r.review_id) AS reviews
	 FROM users u
	 LEFT JOIN reviews r ON u.user_id = r.user_id
	 GROUP BY u.user_id
	 ORDER BY upvotes DESC, reviews DESC, u.user_id ASC
	 LIMIT 50"
);

?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold"><i class="fas fa-trophy text-warning me-2"></i>Leaderboard</h1>
            <p class="text-muted">Daftar pengguna terbaik berdasarkan total upvotes dan jumlah review.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="<?php echo rtrim(BASE_URL, '/'); ?>/" class="btn btn-sm btn-outline-secondary">Kembali</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-3">
            <?php if (empty($leaderboard)): ?>
                <div class="alert alert-info mb-0">Belum ada data leaderboard.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">#</th>
                                <th>Pengguna</th>
                                <th style="width:120px;">Upvotes</th>
                                <th style="width:120px;">Reviews</th>
                                <th style="width:140px;">Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1;
                            foreach ($leaderboard as $row): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo $rank; ?></td>
                                    <td>
                                        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/pages/user/profile.php?user_id=<?php echo (int)$row['user_id']; ?>">
                                            <?php echo htmlspecialchars($row['username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo (int)$row['upvotes']; ?></td>
                                    <td><?php echo (int)$row['reviews']; ?></td>
                                    <td><?php echo htmlspecialchars($row['role']); ?></td>
                                </tr>
                            <?php $rank++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include '../../includes/footer.php'; ?>