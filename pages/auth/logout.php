<?php

if (!defined('MIE_TIME')) {
    define('MIE_TIME', true);
}

require_once '../../config.php';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session di server
@session_destroy();

// Hapus cookie remember-me jika digunakan
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
    unset($_COOKIE['remember_token']);
}

// Set flash message dan redirect ke halaman utama
set_flash('success', 'Anda telah berhasil logout');
redirect('');
