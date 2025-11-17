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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-primary">
                            <i class="fas fa-bowl-food me-2"></i>Mie Time
                        </h2>
                        <p class="text-muted">Masuk ke akun Anda</p>
                    </div>

                    <!-- Error Alert -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input type="email" class="form-control" id="email" name="email"
                                    placeholder="nama@email.com" required
                                    value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Masukkan password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Ingat saya
                            </label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </button>
                        </div>
                    </form>

                    <!-- Divider -->
                    <div class="text-center my-4">
                        <span class="text-muted">atau</span>
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
                    <div class="text-center mt-4">
                        <p class="text-muted mb-1">
                            <a href="#" class="text-decoration-none">Lupa password?</a>
                        </p>
                        <p class="text-muted">
                            Belum punya akun?
                            <a href="<?php echo BASE_URL; ?>register" class="text-decoration-none fw-bold">
                                Daftar sekarang
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Demo Credentials -->
            <div class="alert alert-info mt-3">
                <strong><i class="fas fa-info-circle me-2"></i>Demo Login:</strong><br>
                <small>
                    Email: admin@mietime.com<br>
                    Password: admin123
                </small>
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