<?php

if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Redirect jika sudah login
if (is_logged_in()) {
    redirect('dashboard');
}

$errors = [];
$values = ['username' => '', 'email' => ''];

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Token CSRF tidak valid. Silakan muat ulang halaman.';
    } else {
        $username = clean_input($_POST['username'] ?? '');
        $email = clean_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        $values['username'] = $username;
        $values['email'] = $email;

        if ($username === '' || $email === '' || $password === '' || $password_confirm === '') {
            $errors[] = 'Semua field harus diisi.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email tidak valid.';
        }

        if ($password !== $password_confirm) {
            $errors[] = 'Password dan konfirmasi tidak cocok.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password minimal 6 karakter.';
        }

        // uniqueness checks
        if (get_user_by_username($username)) {
            $errors[] = 'Username sudah digunakan.';
        }

        if (get_user_by_email($email)) {
            $errors[] = 'Email sudah terdaftar.';
        }

        if (empty($errors)) {
            $password_hash = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
            $user_id = db_insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'role' => 'contributor'
            ]);

            if ($user_id) {
                // auto-login
                $_SESSION['user_id'] = (int)$user_id;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'contributor';

                set_flash('success', 'Pendaftaran berhasil. Selamat datang, ' . $username);
                redirect('dashboard');
            } else {
                $errors[] = 'Gagal membuat akun. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Daftar';
include '../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h3 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-bowl-food mr-2"></i>Mie Time
                </h3>
                <p class="text-gray-600">Buat akun baru</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
                    <ul class="space-y-1 text-red-700">
                        <?php foreach ($errors as $err): ?>
                            <li class="flex items-start">
                                <i class="fas fa-exclamation-circle mt-0.5 mr-2"></i>
                                <span><?php echo htmlspecialchars($err); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-5">
                <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="username" required value="<?php echo htmlspecialchars($values['username']); ?>"
                            class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($values['email']); ?>"
                            class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="Minimal 6 karakter"
                            class="pl-10 pr-12 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Konfirmasi Password</label>
                    <input type="password" name="password_confirm" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div>
                    <button type="submit" class="w-full gradient-primary text-white font-semibold py-3 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-user-plus mr-2"></i>Daftar
                    </button>
                </div>
            </form>

            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">Sudah punya akun? <a href="<?php echo BASE_URL; ?>login" class="text-blue-600 hover:text-blue-700 font-semibold">Masuk</a></p>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('togglePassword')?.addEventListener('click', function() {
        var pw = document.getElementById('password');
        var icon = this.querySelector('i');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            pw.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>