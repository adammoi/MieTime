<?php
if (!defined('MIE_TIME')) {
    // do not redefine; assume included from admin pages
}

// Determine current route (extension-less)
$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($request_path, '/'));
$last = end($parts);
$current = $last === '' ? 'index' : preg_replace('/\.php$/', '', $last);
?>
<div class="col-md-3 col-lg-2 mb-4">
    <div class="card shadow-sm">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0">
                <i class="fas fa-user-shield me-2"></i>Admin Menu
            </h6>
        </div>
        <div class="list-group list-group-flush">
            <a href="<?php echo BASE_URL; ?>admin" class="list-group-item list-group-item-action <?php echo $current === 'index' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>admin/moderation" class="list-group-item list-group-item-action <?php echo $current === 'moderation' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-check me-2"></i>Moderasi
            </a>
            <a href="<?php echo BASE_URL; ?>admin/users" class="list-group-item list-group-item-action <?php echo $current === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i>Pengguna
            </a>
            <a href="<?php echo BASE_URL; ?>admin/locations" class="list-group-item list-group-item-action <?php echo $current === 'locations' ? 'active' : ''; ?>">
                <i class="fas fa-store me-2"></i>Kedai
            </a>
            <a href="<?php echo BASE_URL; ?>" class="list-group-item list-group-item-action text-primary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Situs
            </a>
        </div>
    </div>
</div>