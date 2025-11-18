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

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-3">
                        <h3 class="fw-bold text-primary"><i class="fas fa-bowl-food me-2"></i>Mie Time</h3>
                        <p class="text-muted">Buat akun baru</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo htmlspecialchars($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="<?php echo CSRF_TOKEN_NAME; ?>" value="<?php echo generate_csrf_token(); ?>">

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" name="username" class="form-control" required value="<?php echo htmlspecialchars($values['username']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($values['email']); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" required placeholder="Minimal 6 karakter">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirm" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-user-plus me-2"></i>Daftar</button>
                        </div>
                    </form>

                    <div class="text-center mt-3">
                        <p class="text-muted mb-0">Sudah punya akun? <a href="<?php echo BASE_URL; ?>login" class="fw-bold">Masuk</a></p>
                    </div>
                </div>
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