<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Metode permintaan tidak diizinkan.');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/xlsx.php';

try {
    $stmt = $pdo->query(
        'SELECT name, message, created_at
         FROM wishes
         ORDER BY created_at DESC, id DESC'
    );

    $rows = (static function () use ($stmt) {
        $i = 1;

        while ($r = $stmt->fetch()) {
            yield [$i++, $r['name'], $r['message'], $r['created_at']];
        }
    })();

    send_xlsx(
        'data-ucapan-undangan-' . date('Ymd-His') . '.xlsx',
        'Ucapan',
        ['No', 'Nama', 'Ucapan', 'Waktu'],
        $rows,
        [6, 30, 70, 22]
    );

} catch (Throwable $e) {
    error_log('Export wishes error: ' . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
    }

    exit('Gagal mengekspor data ucapan.');
}
