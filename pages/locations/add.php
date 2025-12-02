<?php


if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Require login
require_login();
$latitude = $latitude ?? '';
$longitude = $longitude ?? '';
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $address = clean_input($_POST['address'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    // Validation
    if (empty($name)) {
        $errors[] = 'Nama kedai harus diisi';
    } elseif (strlen($name) < 3) {
        $errors[] = 'Nama kedai minimal 3 karakter';
    }

    if (empty($address)) {
        $errors[] = 'Alamat harus diisi';
    } elseif (strlen($address) < 10) {
        $errors[] = 'Alamat minimal 10 karakter';
    }

    if (empty($latitude) || empty($longitude)) {
        $errors[] = 'Lokasi pada peta harus dipilih';
    } elseif (!is_numeric($latitude) || !is_numeric($longitude)) {
        $errors[] = 'Koordinat lokasi tidak valid';
    }

    // Check duplicate
    $duplicate = db_fetch(
        "SELECT location_id FROM locations WHERE name = ? AND address LIKE ?",
        [$name, "%$address%"]
    );
    if ($duplicate) {
        $errors[] = 'Kedai dengan nama dan alamat serupa sudah ada';
    }

    // Insert if no errors
    if (empty($errors)) {
        $location_id = db_insert('locations', [
            'name' => $name,
            'address' => $address,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'status' => AUTO_APPROVE_REVIEWS ? 'active' : 'pending_approval'
        ]);

        if ($location_id) {
            // Award points
            $user = get_user_by_id(get_current_user_id());
            $new_points = $user['points'] + POINTS_PER_LOCATION_ADD;
            update_user_points(get_current_user_id(), $new_points);

            // Create notification
            create_notification(
                get_current_user_id(),
                "Kedai <strong>$name</strong> berhasil ditambahkan! +" . POINTS_PER_LOCATION_ADD . " poin",
                "kedai/$location_id"
            );

            set_flash('success', 'Kedai berhasil ditambahkan! ' . (AUTO_APPROVE_REVIEWS ? '' : 'Menunggu persetujuan admin.'));
            redirect('kedai/' . $location_id);
        } else {
            $errors[] = 'Gagal menambahkan kedai. Silakan coba lagi.';
        }
    }
}

$page_title = 'Tambah Kedai Baru';
include '../../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h2 class="text-4xl font-bold mb-3">
            <i class="fas fa-plus-circle text-blue-600 mr-2"></i>Tambah Kedai Mie Ayam Baru
        </h2>
        <p class="text-gray-600">
            Bantu komunitas menemukan kedai mie ayam favorit Anda!
            Anda akan mendapatkan <strong class="text-blue-600"><?php echo POINTS_PER_LOCATION_ADD; ?> poin</strong>.
        </p>
    </div>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-400 p-6 mb-6 rounded-lg relative">
            <div class="flex items-start">
                <i class="fas fa-exclamation-circle text-red-600 text-xl mr-3 mt-1"></i>
                <div>
                    <strong class="font-bold text-red-900">Terjadi kesalahan:</strong>
                    <ul class="mt-2 list-disc list-inside space-y-1 text-red-700">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <form method="POST" action="" id="addLocationForm">
            <!-- Name -->
            <div class="mb-6">
                <label for="name" class="block font-bold text-gray-900 mb-2">
                    <i class="fas fa-store text-blue-600 mr-2"></i>Nama Kedai
                    <span class="text-red-600">*</span>
                </label>
                <input type="text" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                    id="name" name="name"
                    placeholder="Contoh: Mie Ayam Pak Sastro"
                    value="<?php echo htmlspecialchars($name ?? ''); ?>"
                    required>
                <small class="text-gray-600 mt-1 block">Masukkan nama kedai yang jelas dan mudah dikenali</small>
            </div>

            <!-- Address -->
            <div class="mb-6">
                <label for="address" class="block font-bold text-gray-900 mb-2">
                    <i class="fas fa-map-marker-alt text-red-600 mr-2"></i>Alamat Lengkap
                    <span class="text-red-600">*</span>
                </label>
                <textarea class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                    id="address" name="address"
                    rows="3" placeholder="Contoh: Jl. Sudirman No. 123, Surabaya"
                    required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                <small class="text-gray-600 mt-1 block">Alamat lengkap dengan nama jalan, nomor, dan kota</small>
            </div>

            <!-- Map -->
            <div class="mb-6">
                <label class="block font-bold text-gray-900 mb-2">
                    <i class="fas fa-map text-green-600 mr-2"></i>Tentukan Lokasi di Peta
                    <span class="text-red-600">*</span>
                </label>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                    <strong class="text-blue-900">Cara menggunakan peta:</strong>
                    <ol class="mt-2 ml-4 list-decimal space-y-1 text-blue-800">
                        <li>Klik tombol "Temukan Lokasi Saya" untuk otomatis mendeteksi posisi Anda</li>
                        <li>Atau klik langsung pada peta untuk menaruh pin</li>
                        <li>Anda bisa drag (geser) pin untuk posisi yang lebih tepat</li>
                    </ol>
                </div>

                <!-- GPS Location Button -->
                <div class="mb-4">
                    <button class="w-full px-6 py-3 gradient-primary text-white font-semibold rounded-lg hover-lift" type="button" id="findMyLocationBtn">
                        <i class="fas fa-location-arrow mr-2"></i>Temukan Lokasi Saya
                    </button>
                </div>

                <!-- Map Container -->
                <div id="map" style="height: 400px;" class="rounded-2xl shadow-lg mb-4"></div>

                <!-- Hidden Coordinates -->
                <input type="hidden" id="latitude" name="latitude" value="<?php echo $latitude ?? ''; ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?php echo $longitude ?? ''; ?>">

                <!-- Coordinate Display -->
                <div class="bg-gray-100 rounded-lg p-3">
                    <small class="text-gray-600">
                        <i class="fas fa-crosshairs mr-2"></i>
                        Koordinat:
                        <span id="coordDisplay" class="font-bold text-gray-900">
                            <?php if ($latitude && $longitude): ?>
                                <?php echo $latitude; ?>, <?php echo $longitude; ?>
                            <?php else: ?>
                                Belum dipilih
                            <?php endif; ?>
                        </span>
                    </small>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex gap-3">
                <button type="submit" class="flex-1 px-6 py-4 gradient-primary text-white font-bold text-lg rounded-lg hover-lift">
                    <i class="fas fa-check mr-2"></i>Tambah Kedai
                </button>
                <a href="<?php echo BASE_URL; ?>kedai" class="px-6 py-4 border-2 border-gray-300 text-gray-700 font-bold text-lg rounded-lg hover:bg-gray-100 transition">
                    <i class="fas fa-times mr-2"></i>Batal
                </a>
            </div>
        </form>
    </div>

    <!-- Tips Card -->
    <div class="mt-8 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-2xl shadow-lg p-6">
        <h6 class="flex items-center font-bold text-lg text-gray-900 mb-4">
            <i class="fas fa-lightbulb text-yellow-500 mr-2"></i>Tips Menambahkan Kedai
        </h6>
        <ul class="space-y-2 text-gray-700">
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                Pastikan nama kedai benar dan tidak typo
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                Alamat harus lengkap agar mudah ditemukan
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                Pilih lokasi di peta seakurat mungkin
            </li>
            <li class="flex items-start">
                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                Setelah menambah kedai, jangan lupa tulis review!
            </li>
        </ul>
    </div>
</div>
</div>

<script>
    // Wrap map logic in a function and run after window load so Leaflet is available
    function initAddLocationMap() {
        // Initialize map centered at Surabaya, Indonesia
        const defaultLat = -7.2575;
        const defaultLng = 112.7521;
        let currentMarker = null;

        const map = L.map('map').setView([<?php echo $latitude ?: 'defaultLat'; ?>, <?php echo $longitude ?: 'defaultLng'; ?>], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Helper to create a marker using a FontAwesome divIcon (avoids external image assets)
        const faIconHtml = '<i class="fas fa-map-marker-alt" style="color:#d00;font-size:28px;line-height:28px;"></i>';

        function createMapMarker(lat, lng) {
            const icon = L.divIcon({
                className: 'fa-map-marker-divicon',
                html: faIconHtml,
                iconSize: [28, 28],
                iconAnchor: [14, 28]
            });
            return L.marker([lat, lng], {
                icon: icon,
                draggable: true
            }).addTo(map);
        }

        // Add existing marker if editing
        <?php if (!empty($latitude) && !empty($longitude)): ?>
            currentMarker = createMapMarker(<?php echo $latitude; ?>, <?php echo $longitude; ?>);
            setupMarker(currentMarker);
        <?php endif; ?>

        // Click on map to add marker
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;

            if (currentMarker) {
                map.removeLayer(currentMarker);
            }

            currentMarker = createMapMarker(lat, lng);

            setupMarker(currentMarker);
            updateCoordinates(lat, lng);
        });

        // Setup marker drag event
        function setupMarker(marker) {
            marker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });
        }

        // Update hidden inputs and display
        function updateCoordinates(lat, lng) {
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('coordDisplay').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
        }

        // Find My Location using GPS
        document.getElementById('findMyLocationBtn').addEventListener('click', function() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung fitur GPS. Silakan klik langsung pada peta.');
                return;
            }

            // Show loading
            this.disabled = true;
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mencari lokasi Anda...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Move map to user's location
                    map.setView([lat, lng], 16);

                    // Add marker
                    if (currentMarker) {
                        map.removeLayer(currentMarker);
                    }

                    currentMarker = createMapMarker(lat, lng);
                    setupMarker(currentMarker);
                    updateCoordinates(lat, lng);

                    // Show popup
                    currentMarker.bindPopup('Lokasi Anda').openPopup();

                    this.disabled = false;
                    this.innerHTML = originalHtml;
                },
                (error) => {
                    this.disabled = false;
                    this.innerHTML = originalHtml;

                    let errorMsg = 'Gagal mendapatkan lokasi Anda. ';
                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Izin lokasi ditolak. Silakan aktifkan izin lokasi di browser Anda.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Informasi lokasi tidak tersedia.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Waktu permintaan lokasi habis.';
                            break;
                        default:
                            errorMsg += 'Terjadi kesalahan yang tidak diketahui.';
                    }
                    errorMsg += ' Silakan klik langsung pada peta.';
                    alert(errorMsg);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });

        // Form validation
        document.getElementById('addLocationForm').addEventListener('submit', function(e) {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;

            if (!lat || !lng) {
                e.preventDefault();
                alert('Silakan pilih lokasi pada peta terlebih dahulu');
                return false;
            }
        });
    }

    // Run after all resources (including footer scripts) are loaded
    window.addEventListener('load', function() {
        if (typeof L === 'undefined') {
            // Leaflet not loaded yet; try again shortly
            setTimeout(function() {
                if (typeof L !== 'undefined') {
                    initAddLocationMap();
                } else {
                    console.error('Leaflet library is not available.');
                }
            }, 250);
        } else {
            initAddLocationMap();
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>