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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="fw-bold">
                    <i class="fas fa-plus-circle text-primary me-2"></i>Tambah Kedai Mie Ayam Baru
                </h2>
                <p class="text-muted">
                    Bantu komunitas menemukan kedai mie ayam favorit Anda!
                    Anda akan mendapatkan <strong><?php echo POINTS_PER_LOCATION_ADD; ?> poin</strong>.
                </p>
            </div>

            <!-- Error Alerts -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong><i class="fas fa-exclamation-circle me-2"></i>Terjadi kesalahan:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="card shadow">
                <div class="card-body p-4">
                    <form method="POST" action="" id="addLocationForm">
                        <!-- Name -->
                        <div class="mb-4">
                            <label for="name" class="form-label fw-bold">
                                <i class="fas fa-store text-primary me-2"></i>Nama Kedai
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                id="name" name="name"
                                placeholder="Contoh: Mie Ayam Pak Sastro"
                                value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                required>
                            <small class="text-muted">Masukkan nama kedai yang jelas dan mudah dikenali</small>
                        </div>

                        <!-- Address -->
                        <div class="mb-4">
                            <label for="address" class="form-label fw-bold">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>Alamat Lengkap
                                <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="address" name="address"
                                rows="3" placeholder="Contoh: Jl. Sudirman No. 123, Surabaya"
                                required><?php echo htmlspecialchars($address ?? ''); ?></textarea>
                            <small class="text-muted">Alamat lengkap dengan nama jalan, nomor, dan kota</small>
                        </div>

                        <!-- Map -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">
                                <i class="fas fa-map text-success me-2"></i>Tentukan Lokasi di Peta
                                <span class="text-danger">*</span>
                            </label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Cara menggunakan peta:</strong>
                                <ol class="mb-0 mt-2">
                                    <li>Ketik alamat di kotak pencarian untuk mencari lokasi</li>
                                    <li>Atau klik langsung pada peta untuk menaruh pin</li>
                                    <li>Anda bisa drag (geser) pin untuk posisi yang lebih tepat</li>
                                </ol>
                            </div>

                            <!-- Search Box -->
                            <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="searchAddress"
                                    placeholder="Cari alamat... (contoh: Jl. Sudirman Surabaya)">
                                <button class="btn btn-primary" type="button" id="searchBtn">
                                    Cari
                                </button>
                            </div>

                            <!-- Map Container -->
                            <div id="map" style="height: 400px; border-radius: 12px;" class="shadow-sm"></div>

                            <!-- Hidden Coordinates -->
                            <input type="hidden" id="latitude" name="latitude" value="<?php echo $latitude ?? ''; ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?php echo $longitude ?? ''; ?>">

                            <!-- Coordinate Display -->
                            <div class="mt-2 p-2 bg-light rounded">
                                <small class="text-muted">
                                    <i class="fas fa-crosshairs me-2"></i>
                                    Koordinat:
                                    <span id="coordDisplay" class="fw-bold">
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
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                                <i class="fas fa-check me-2"></i>Tambah Kedai
                            </button>
                            <a href="<?php echo BASE_URL; ?>kedai" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Tips Menambahkan Kedai</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Pastikan nama kedai benar dan tidak typo</li>
                        <li>Alamat harus lengkap agar mudah ditemukan</li>
                        <li>Pilih lokasi di peta seakurat mungkin</li>
                        <li>Setelah menambah kedai, jangan lupa tulis review!</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize map centered at Surabaya, Indonesia
    const defaultLat = -7.2575;
    const defaultLng = 112.7521;
    let currentMarker = null;

    const map = L.map('map').setView([<?php echo $latitude ?: 'defaultLat'; ?>, <?php echo $longitude ?: 'defaultLng'; ?>], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Add existing marker if editing
    <?php if (!empty($latitude) && !empty($longitude)): ?>
        currentMarker = L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>], {
            draggable: true
        }).addTo(map);
        setupMarker(currentMarker);
    <?php endif; ?>

    // Click on map to add marker
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;

        if (currentMarker) {
            map.removeLayer(currentMarker);
        }

        currentMarker = L.marker([lat, lng], {
            draggable: true
        }).addTo(map);

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

    // Search address using Nominatim (OpenStreetMap)
    document.getElementById('searchBtn').addEventListener('click', function() {
        const address = document.getElementById('searchAddress').value;

        if (!address) {
            alert('Masukkan alamat terlebih dahulu');
            return;
        }

        // Show loading
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari...';

        // Nominatim API
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
            .then(response => response.json())
            .then(data => {
                this.disabled = false;
                this.innerHTML = 'Cari';

                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);

                    // Move map to location
                    map.setView([lat, lng], 16);

                    // Add marker
                    if (currentMarker) {
                        map.removeLayer(currentMarker);
                    }

                    currentMarker = L.marker([lat, lng], {
                        draggable: true
                    }).addTo(map);

                    setupMarker(currentMarker);
                    updateCoordinates(lat, lng);

                    // Popup
                    currentMarker.bindPopup(data[0].display_name).openPopup();
                } else {
                    alert('Alamat tidak ditemukan. Coba dengan kata kunci yang lebih spesifik atau klik langsung pada peta.');
                }
            })
            .catch(error => {
                this.disabled = false;
                this.innerHTML = 'Cari';
                console.error('Error:', error);
                alert('Terjadi kesalahan saat mencari lokasi');
            });
    });

    // Allow Enter key to search
    document.getElementById('searchAddress').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('searchBtn').click();
        }
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
</script>

<?php include '../../includes/footer.php'; ?>