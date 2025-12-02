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

    <!-- Tailwind CSS (compiled) -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/output.css">

    <!-- Bootstrap CSS (for admin components like modals) -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/bootstrap/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/fontawesome-free-7.1.0-web/css/all.min.css">

    <!-- Leaflet CSS untuk peta -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/leaflet/leaflet.css" />

    <!-- Alpine.js (local, vendored) -->
    <script defer src="<?php echo ASSETS_URL; ?>js/alpine.min.js"></script>

    <!-- Local Inter font -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/inter.css">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
        }

        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .gradient-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .gradient-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .gradient-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .gradient-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>img/favicon.png">
</head>

<body class="bg-gray-50 min-h-screen flex flex-col" x-data="{ mobileMenuOpen: false }">

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
        <div class="container mx-auto px-4 mt-4 max-w-7xl">
            <?php if ($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-4 flex items-start justify-between">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 mt-0.5 mr-3"></i>
                        <p class="text-green-700"><?php echo $success; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-4 flex items-start justify-between">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($warning): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-4 flex items-start justify-between">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5 mr-3"></i>
                        <p class="text-yellow-700"><?php echo $warning; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-yellow-500 hover:text-yellow-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4 flex items-start justify-between">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-3"></i>
                        <p class="text-blue-700"><?php echo $info; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>