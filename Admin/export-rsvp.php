<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin();

require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query(
        'SELECT name, attendance, created_at
         FROM rsvps
         ORDER BY created_at DESC, id DESC'
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data-rsvp-undangan.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    $out = fopen('php://output', 'w');

    if ($out === false) {
        throw new RuntimeException('Gagal membuka output CSV.');
    }

    // UTF-8 BOM untuk kompatibilitas Excel
    fwrite($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'No',
        'Nama',
        'Kehadiran',
        'Waktu Konfirmasi'
    ]);

    // Proteksi CSV Injection
    $safeCsv = static function ($value): string {
        $value = (string) $value;

        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $value)) {
            return "'" . $value;
        }

        return $value;
    };

    $i = 1;

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $i++,
            $safeCsv($r['name']),
            $safeCsv($r['attendance']),
            $safeCsv($r['created_at'])
        ]);
    }

    fclose($out);
    exit;

} catch (Throwable $e) {
    error_log($e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
    }

    exit('Gagal mengekspor data RSVP.');
}
