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

$error = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        // Get user by email
        $user = get_user_by_email($email);

        if ($user && password_verify($password, $user['password_hash'])) {
            // Login success
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Remember me
            if ($remember) {
                setcookie('remember_token', $user['user_id'], time() + (86400 * 30), '/');
            }

            set_flash('success', 'Login berhasil! Selamat datang, ' . $user['username']);

            // Redirect
            if (isset($_GET['redirect'])) {
                redirect($_GET['redirect']);
            } else {
                redirect('dashboard');
            }
        } else {
            $error = 'Email atau password salah';
        }
    }
}

$page_title = 'Login';
include '../../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-bowl-food mr-2"></i>Mie Time
                </h2>
                <p class="text-gray-600">Masuk ke akun Anda</p>
            </div>

            <!-- Error Alert -->
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-3"></i>
                        <p class="text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" id="email" name="email"
                            class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="nama@email.com" required
                            value="<?php echo htmlspecialchars($email ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password"
                            class="pl-10 pr-12 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Masukkan password" required>
                        <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-700">
                        Ingat saya
                    </label>
                </div>

                <div>
                    <button type="submit" class="w-full gradient-primary text-white font-semibold py-3 rounded-lg hover:shadow-lg transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Masuk
                    </button>
                </div>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">atau</span>
                </div>
            </div>

            <!-- Social Login
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-danger" onclick="alert('Fitur dalam pengembangan')">
                            <i class="fab fa-google me-2"></i>Masuk dengan Google
                        </button>
                        <button class="btn btn-outline-primary" onclick="alert('Fitur dalam pengembangan')">
                            <i class="fab fa-facebook me-2"></i>Masuk dengan Facebook
                        </button>
                    </div> -->

            <!-- Links -->
            <div class="text-center space-y-2">
                <p class="text-sm text-gray-600">
                    <a href="#" class="text-blue-600 hover:text-blue-700 font-medium">Lupa password?</a>
                </p>
                <p class="text-sm text-gray-600">
                    Belum punya akun?
                    <a href="<?php echo BASE_URL; ?>register" class="text-blue-600 hover:text-blue-700 font-semibold">
                        Daftar sekarang
                    </a>
                </p>
            </div>
        </div>


    </div>
</div>

<script>
    // Toggle password visibility
    document.getElementById('togglePassword')?.addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>