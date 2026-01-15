<?php
include "../koneksi.php";

// (Opsional) Biar aman kalau ada output sebelumnya
if (ob_get_length()) ob_end_clean();

// Query pakai JOIN agregat (lebih ringan daripada subquery per baris)
$sql = "SELECT 
    o.kode_obat,
    o.nama_obat,
    o.satuan,
    o.stok,
    o.stok_minimum,
    o.tanggal_kadaluarsa,
    masuk.max_tanggal AS tgl_masuk_terakhir,
    keluar.max_tanggal AS tgl_keluar_terakhir
FROM obat o
LEFT JOIN (
    SELECT kode_obat, MAX(tanggal) AS max_tanggal
    FROM stok_log
    WHERE tipe = 'masuk'
    GROUP BY kode_obat
) masuk ON masuk.kode_obat = o.kode_obat
LEFT JOIN (
    SELECT kode_obat, MAX(tanggal) AS max_tanggal
    FROM stok_log
    WHERE tipe = 'keluar'
    GROUP BY kode_obat
) keluar ON keluar.kode_obat = o.kode_obat
ORDER BY o.nama_obat ASC
";

$query = mysqli_query($conn, $sql);
if (!$query) {
    // kalau query gagal, stop biar tidak download file kosong
    http_response_code(500);
    die("Query export gagal: " . mysqli_error($conn));
}

/**
 * Excel sering lebih aman pakai:
 * - UTF-8 BOM
 * - delimiter ';'
 */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Daftar_Obat.csv"');

// UTF-8 BOM biar Excel kebaca karakter lokal
echo "\xEF\xBB\xBF";

// Output stream
$output = fopen('php://output', 'w');

// Pakai delimiter ';' (umum di Excel Indonesia)
$delimiter = ';';

// Header kolom
fputcsv($output, [
    'Kode Obat',
    'Nama Obat',
    'Satuan',
    'Stok Akhir',
    'Stok Minimum',
    'Status Stok',
    'Tanggal Masuk Terakhir',
    'Tanggal Keluar Terakhir',
    'Tanggal Kadaluarsa'
], $delimiter);

// Data rows
while ($data = mysqli_fetch_assoc($query)) {

    $tgl_masuk = !empty($data['tgl_masuk_terakhir'])
        ? date('d-m-Y H:i', strtotime($data['tgl_masuk_terakhir']))
        : '-';

    $tgl_keluar = !empty($data['tgl_keluar_terakhir'])
        ? date('d-m-Y H:i', strtotime($data['tgl_keluar_terakhir']))
        : '-';

    $tgl_expired = !empty($data['tanggal_kadaluarsa'])
        ? date('d-m-Y', strtotime($data['tanggal_kadaluarsa']))
        : '-';

    $stok = (int)($data['stok'] ?? 0);
    $min  = (int)($data['stok_minimum'] ?? 0);

    // Status stok (tanpa emoji biar Excel aman)
    $status_stok = ($stok <= $min) ? 'Di bawah minimum' : 'Aman';

    fputcsv($output, [
        $data['kode_obat'] ?? '',
        $data['nama_obat'] ?? '',
        $data['satuan'] ?? '',
        $stok,
        $min,
        $status_stok,
        $tgl_masuk,
        $tgl_keluar,
        $tgl_expired
    ], $delimiter);
}

fclose($output);
exit;
?>
