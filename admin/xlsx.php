<?php
declare(strict_types=1);

/**
 * Pembuat file Excel (.xlsx) sederhana tanpa library tambahan.
 * Membutuhkan ekstensi PHP "zip" (aktif secara default di Laragon/XAMPP).
 *
 * Teks disimpan sebagai inline string, sehingga isi sel tidak pernah
 * dievaluasi sebagai rumus (aman dari formula/CSV injection).
 */

function xlsx_column(int $index): string
{
    $name = '';
    $index++;

    while ($index > 0) {
        $mod = ($index - 1) % 26;
        $name = chr(65 + $mod) . $name;
        $index = intdiv($index - $mod - 1, 26);
    }

    return $name;
}

function xlsx_escape(string $value): string
{
    // Buang karakter kontrol yang tidak valid di XML 1.0.
    $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Mengirim file .xlsx ke browser sebagai unduhan.
 *
 * @param string   $filename Nama file unduhan, contoh: "data-rsvp.xlsx"
 * @param string   $sheet    Nama sheet
 * @param string[] $headers  Judul kolom
 * @param iterable $rows     Setiap baris berupa array nilai (urut sesuai kolom)
 * @param int[]    $widths   Lebar kolom (opsional)
 */
function send_xlsx(string $filename, string $sheet, array $headers, iterable $rows, array $widths = []): void
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('Ekstensi PHP zip belum aktif.');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');

    if ($tmp === false) {
        throw new RuntimeException('Gagal membuat file sementara.');
    }

    $cols = '';
    foreach ($widths as $i => $w) {
        $n = $i + 1;
        $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . (int)$w . '" customWidth="1"/>';
    }

    $rowXml = static function (array $cells, int $rowNo, int $style) : string {
        $xml = '<row r="' . $rowNo . '">';

        foreach (array_values($cells) as $c => $value) {
            $ref = xlsx_column($c) . $rowNo;

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
                    . xlsx_escape((string)$value) . '</t></is></c>';
            }
        }

        return $xml . '</row>';
    };

    $sheetData = $rowXml($headers, 1, 1);
    $rowNo = 2;

    foreach ($rows as $row) {
        $sheetData .= $rowXml($row, $rowNo++, 2);
    }

    $lastCol = xlsx_column(max(count($headers), 1) - 1);

    $files = [
        '[Content_Types].xml' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>',

        '_rels/.rels' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',

        'xl/workbook.xml' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . xlsx_escape(mb_substr($sheet, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>',

        'xl/_rels/workbook.xml.rels' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>',

        // Style 0: bawaan, 1: judul kolom (tebal + hijau), 2: isi (wrap text, rata atas)
        'xl/styles.xml' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1E3F32"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf>'
            . '</cellXfs>'
            . '</styleSheet>',

        'xl/worksheets/sheet1.xml' =>
            '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '<autoFilter ref="A1:' . $lastCol . '1"/>'
            . '</worksheet>',
    ];

    $zip = new ZipArchive();

    if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        throw new RuntimeException('Gagal membuat file Excel.');
    }

    foreach ($files as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store, no-cache, must-revalidate');

    readfile($tmp);
    @unlink($tmp);
    exit;
}
