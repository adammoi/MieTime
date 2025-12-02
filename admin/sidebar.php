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
<div class="col-span-1 md:col-span-1 lg:col-span-1">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-24">
        <!-- Sidebar Header -->
        <div class="bg-gradient-to-r from-red-600 to-red-700 p-6">
            <h5 class="text-white font-bold text-lg flex items-center">
                <i class="fas fa-user-shield mr-2"></i>
                Admin Menu
            </h5>
        </div>

        <!-- Menu Items -->
        <nav class="p-4">
            <a href="<?php echo BASE_URL; ?>admin" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'index' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-tachometer-alt w-5 mr-3"></i>
                Dashboard
            </a>

            <a href="<?php echo BASE_URL; ?>admin/moderation" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'moderation' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-clipboard-check w-5 mr-3"></i>
                Moderasi
            </a>

            <a href="<?php echo BASE_URL; ?>admin/users" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'users' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-users w-5 mr-3"></i>
                Pengguna
            </a>

            <a href="<?php echo BASE_URL; ?>admin/locations" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'locations' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-store w-5 mr-3"></i>
                Kedai
            </a>

            <a href="<?php echo BASE_URL; ?>admin/claims" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'claims' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-building w-5 mr-3"></i>
                Klaim Kedai
            </a>

            <a href="<?php echo BASE_URL; ?>admin/badges" class="flex items-center px-4 py-3 mb-2 rounded-lg transition <?php echo $current === 'badges' ? 'bg-blue-600 text-white font-semibold' : 'text-gray-700 hover:bg-gray-100'; ?>">
                <i class="fas fa-medal w-5 mr-3"></i>
                Badges
            </a>

            <div class="border-t border-gray-200 my-4"></div>

            <a href="<?php echo BASE_URL; ?>" class="flex items-center px-4 py-3 rounded-lg text-blue-600 hover:bg-blue-50 transition font-semibold">
                <i class="fas fa-arrow-left w-5 mr-3"></i>
                Kembali ke Situs
            </a>
        </nav>
    </div>
</div>