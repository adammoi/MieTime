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

$location_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$location = get_location_by_id($location_id);

if (!$location) {
    set_flash('error', 'Kedai tidak ditemukan');
    redirect('kedai');
}

$user_id = get_current_user_id();
$user = get_user_by_id($user_id);

// Check if already claimed
$existing_claim = db_fetch(
    "SELECT * FROM location_claims WHERE location_id = ? AND status = 'pending'",
    [$location_id]
);

$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $verification_type = clean_input($_POST['verification_type'] ?? '');
    $business_document = $_FILES['business_document'] ?? null;
    $notes = clean_input($_POST['notes'] ?? '');

    // Validation
    if (empty($verification_type)) {
        $errors[] = 'Jenis verifikasi harus dipilih';
    }

    if ($verification_type === 'document' && (!$business_document || $business_document['error'] !== UPLOAD_ERR_OK)) {
        $errors[] = 'Dokumen verifikasi harus diunggah';
    }

    // Check if already claimed by this user
    $my_claim = db_fetch(
        "SELECT * FROM location_claims WHERE location_id = ? AND user_id = ?",
        [$location_id, $user_id]
    );

    if ($my_claim) {
        $errors[] = 'Anda sudah pernah mengajukan klaim untuk kedai ini';
    }

    if (empty($errors)) {
        $document_path = null;

        // Handle document upload
        if ($verification_type === 'document' && $business_document) {
            $upload_dir = __DIR__ . '/../../uploads/claims/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = strtolower(pathinfo($business_document['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

            if (!in_array($file_ext, $allowed_ext)) {
                $errors[] = 'Format file tidak didukung. Gunakan PDF, JPG, atau PNG';
            } elseif ($business_document['size'] > 5 * 1024 * 1024) {
                $errors[] = 'Ukuran file maksimal 5MB';
            } else {
                $new_filename = 'claim_' . $location_id . '_' . $user_id . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($business_document['tmp_name'], $target_path)) {
                    $document_path = 'claims/' . $new_filename;
                } else {
                    $errors[] = 'Gagal mengunggah dokumen';
                }
            }
        }

        if (empty($errors)) {
            // Insert claim request
            $claim_id = db_insert('location_claims', [
                'location_id' => $location_id,
                'user_id' => $user_id,
                'verification_type' => $verification_type,
                'document_path' => $document_path,
                'notes' => $notes,
                'status' => 'pending'
            ]);

            if ($claim_id) {
                // Create notification for user
                create_notification(
                    $user_id,
                    "Klaim Anda untuk <strong>{$location['name']}</strong> sedang diproses oleh admin",
                    "kedai/{$location_id}"
                );

                set_flash('success', 'Klaim berhasil diajukan! Admin akan meninjau permohonan Anda.');
                redirect('kedai/' . $location_id);
            } else {
                $errors[] = 'Gagal mengajukan klaim. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Klaim Kedai - ' . $location['name'];
include '../../includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <h2 class="font-bold text-3xl text-gray-900">
            <i class="fas fa-building text-blue-600 mr-2"></i>Klaim Kepemilikan Kedai
        </h2>
        <p class="text-gray-600 mt-2">
            Ajukan klaim untuk kedai <strong><?php echo htmlspecialchars($location['name']); ?></strong>
        </p>
    </div>

    <!-- Info Alert -->
    <?php if ($existing_claim): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg mb-6">
            <i class="fas fa-exclamation-triangle mr-2 text-yellow-600"></i>
            <strong>Perhatian!</strong> Kedai ini sudah ada pengajuan klaim yang sedang diproses.
        </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg mb-6">
            <strong class="text-red-800">
                <i class="fas fa-exclamation-circle mr-2"></i>Terjadi kesalahan:
            </strong>
            <ul class="mb-0 mt-2 ml-6 list-disc text-red-700">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Location Info Card -->
    <div class="bg-white rounded-2xl shadow-lg mb-6 p-6">
        <div class="flex items-center">
            <div class="gradient-primary text-white rounded-xl p-4 mr-4">
                <i class="fas fa-store fa-2x"></i>
            </div>
            <div>
                <h5 class="mb-1 font-bold text-xl text-gray-900"><?php echo htmlspecialchars($location['name']); ?></h5>
                <p class="text-gray-600 mb-0">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    <?php echo htmlspecialchars($location['address']); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Claim Form -->
    <div class="bg-white rounded-2xl shadow-lg">
        <div class="p-8">
            <form method="POST" action="" enctype="multipart/form-data" id="claimForm">
                <!-- Verification Type -->
                <div class="mb-6">
                    <label class="block font-bold text-gray-900 mb-4">
                        <i class="fas fa-check-circle text-green-600 mr-2"></i>Jenis Verifikasi
                        <span class="text-red-600">*</span>
                    </label>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <input class="mt-1 mr-3" type="radio" name="verification_type"
                                id="verification_document" value="document" required>
                            <label class="flex-1" for="verification_document">
                                <strong class="text-gray-900">Dokumen Usaha</strong>
                                <small class="block text-gray-600 mt-1">Upload SIUP, NIB, atau dokumen kepemilikan lainnya</small>
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input class="mt-1 mr-3" type="radio" name="verification_type"
                                id="verification_phone" value="phone">
                            <label class="flex-1" for="verification_phone">
                                <strong class="text-gray-900">Verifikasi Telepon</strong>
                                <small class="block text-gray-600 mt-1">Admin akan menghubungi Anda untuk verifikasi</small>
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input class="mt-1 mr-3" type="radio" name="verification_type"
                                id="verification_email" value="email">
                            <label class="flex-1" for="verification_email">
                                <strong class="text-gray-900">Verifikasi Email</strong>
                                <small class="block text-gray-600 mt-1">Konfirmasi melalui email bisnis</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Document Upload -->
                <div class="mb-6" id="documentUploadSection" style="display:none;">
                    <label for="business_document" class="block font-bold text-gray-900 mb-3">
                        <i class="fas fa-file-upload text-blue-600 mr-2"></i>Unggah Dokumen
                    </label>
                    <input type="file" class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 transition"
                        id="business_document" name="business_document" accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-gray-600">Format: PDF, JPG, PNG. Maksimal 5MB</small>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                    <label for="notes" class="block font-bold text-gray-900 mb-3">
                        <i class="fas fa-comment text-blue-500 mr-2"></i>Catatan Tambahan (Opsional)
                    </label>
                    <textarea class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                        id="notes" name="notes" rows="4"
                        placeholder="Tambahkan informasi yang dapat membantu verifikasi klaim Anda..."></textarea>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-6 py-4 gradient-primary text-white font-bold rounded-lg hover-lift transition">
                        <i class="fas fa-paper-plane mr-2"></i>Ajukan Klaim
                    </button>
                    <a href="<?php echo BASE_URL; ?>kedai/<?php echo $location_id; ?>"
                        class="px-6 py-4 border-2 border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-100 transition">
                        <i class="fas fa-times mr-2"></i>Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Info Card -->
    <div class="bg-white rounded-2xl shadow-lg mt-6 overflow-hidden border-2 border-blue-400">
        <div class="bg-blue-500 text-white p-4">
            <h6 class="mb-0 font-bold">
                <i class="fas fa-info-circle mr-2"></i>Informasi Penting
            </h6>
        </div>
        <div class="p-6">
            <p class="mb-3 font-bold text-gray-900">Manfaat Menjadi Verified Owner:</p>
            <ul class="mb-4 space-y-2 text-gray-700 ml-5 list-disc">
                <li>Badge "Verified Owner" di profil dan review Anda</li>
                <li>Prioritas dalam moderasi review</li>
                <li>Dapat membalas review pelanggan</li>
                <li>Akses statistik kedai Anda</li>
            </ul>
            <p class="mb-0 text-sm text-gray-600">
                <i class="fas fa-shield-alt mr-1"></i>
                Semua informasi yang Anda berikan akan dijaga kerahasiaannya dan hanya digunakan untuk verifikasi kepemilikan.
            </p>
        </div>
    </div>
</div>

<script>
    // Show/hide document upload based on verification type
    document.querySelectorAll('input[name="verification_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const docSection = document.getElementById('documentUploadSection');
            const docInput = document.getElementById('business_document');

            if (this.value === 'document') {
                docSection.style.display = 'block';
                docInput.required = true;
            } else {
                docSection.style.display = 'none';
                docInput.required = false;
            }
        });
    });
</script>

<?php include '../../includes/footer.php'; ?>