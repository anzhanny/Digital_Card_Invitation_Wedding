<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

// Logout hanya boleh dilakukan melalui POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metode permintaan tidak diizinkan.');
}

// Pastikan pengguna sudah login
require_admin();

// Validasi CSRF token
$token = (string)($_POST['csrf'] ?? '');
$sessionToken = (string)($_SESSION['logout_csrf'] ?? '');

if (
    $sessionToken === '' ||
    !hash_equals($sessionToken, $token)
) {
    http_response_code(403);
    exit('Permintaan tidak valid.');
}

// Hapus seluruh data session
$_SESSION = [];

// Hapus cookie session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

// Hancurkan session
session_destroy();

header('Location: login.php');
exit;
