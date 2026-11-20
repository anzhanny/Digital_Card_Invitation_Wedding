<?php
declare(strict_types=1);

// Konfigurasi session sebelum session dimulai.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);

    session_start();
}

/**
 * Memeriksa apakah pengguna sudah login sebagai admin.
 */
function require_admin(): void
{
    // Pastikan admin_id berupa bilangan positif.
    if (
        !isset($_SESSION['admin_id']) ||
        !is_numeric($_SESSION['admin_id']) ||
        (int)$_SESSION['admin_id'] <= 0
    ) {
        $_SESSION = [];
        header('Location: login.php');
        exit;
    }

    // Periksa batas waktu session tidak aktif: 30 menit.
    $timeout = 1800;
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);

    if (
        $lastActivity <= 0 ||
        (time() - $lastActivity) > $timeout
    ) {
        $_SESSION = [];
        session_regenerate_id(true);

        header('Location: login.php?expired=1');
        exit;
    }

    // Perbarui waktu aktivitas terakhir.
    $_SESSION['last_activity'] = time();
}
