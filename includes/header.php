<?php
if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $page_description ?? 'Platform review dan penemuan mie ayam terbaik di Indonesia'; ?>">
    <title><?php echo $page_title ?? 'Mie Time'; ?> - Platform Review Mie Ayam</title>

    <!-- Bootstrap CSS -->
    <link href="<?php echo ASSETS_URL; ?>css/bootstrap/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/fontawesome-free-7.1.0-web/css/all.min.css">

    <!-- Leaflet CSS untuk peta -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/leaflet/leaflet.css" />

    <!-- Custom CSS -->
    <link href="<?php echo ASSETS_URL; ?>css/custom.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>img/favicon.png">
</head>

<body>

    <?php
    // Include navbar
    include __DIR__ . '/navbar.php';

    // Display flash messages
    $success = get_flash('success');
    $error = get_flash('error');
    $warning = get_flash('warning');
    $info = get_flash('info');

    if ($success || $error || $warning || $info):
    ?>
        <div class="container mt-3">
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($warning): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $warning; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i><?php echo $info; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>