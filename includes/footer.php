<?php
if (!defined('MIE_TIME')) {
    die('Direct access not permitted');
}
?>

<!-- Footer -->
<footer class="bg-gray-900 text-gray-300 mt-auto w-full" style="position: relative; z-index: 10;">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- About -->
            <div class="col-span-1 md:col-span-2">
                <h3 class="text-white text-2xl font-bold mb-4">
                    <i class="fas fa-bowl-food mr-2"></i>Mie Time
                </h3>
                <p class="text-gray-400 mb-4">
                    Platform komunitas terpercaya untuk menemukan dan berbagi review kedai mie ayam terbaik di seluruh Indonesia.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-facebook text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-twitter text-2xl"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition">
                        <i class="fab fa-instagram text-2xl"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 class="text-white font-semibold mb-4">Menu</h4>
                <ul class="space-y-2">
                    <li><a href="<?php echo BASE_URL; ?>kedai" class="hover:text-white transition">Jelajahi Kedai</a></li>
                    <li><a href="<?php echo BASE_URL; ?>leaderboard" class="hover:text-white transition">Leaderboard</a></li>
                    <li><a href="<?php echo BASE_URL; ?>stats" class="hover:text-white transition">Statistik</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?php echo BASE_URL; ?>dashboard" class="hover:text-white transition">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 class="text-white font-semibold mb-4">Kontak</h4>
                <ul class="space-y-2">
                    <li class="flex items-start">
                        <i class="fas fa-envelope mt-1 mr-2"></i>
                        <span>info@mietime.com</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone mt-1 mr-2"></i>
                        <span>+62 123 4567 890</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1 mr-2"></i>
                        <span>Jakarta, Indonesia</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> Mie Time. All rights reserved.</p>
        </div>
    </div>
</footer>

<!-- Scripts -->
<!-- Leaflet (if used on the page) -->
<script src="<?php echo ASSETS_URL; ?>js/leaflet.js"></script>

<!-- Custom JS -->
<script src="<?php echo ASSETS_URL; ?>js/custom.js"></script>
<!-- Bootstrap JS (required for modals) -->
<script src="<?php echo ASSETS_URL; ?>js/bootstrap.bundle.min.js"></script>

</body>

</html>