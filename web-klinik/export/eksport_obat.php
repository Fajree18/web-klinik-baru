<?php
include "../koneksi.php";

// Hindari output sebelumnya
if (ob_get_length()) ob_end_clean();

/* ================== QUERY ================== */
$sql = "SELECT o.kode_obat,
    o.nama_obat,
    o.satuan,
    o.stok,
    o.stok_minimum,
    o.tanggal_kadaluarsa,

    masuk.max_tanggal AS tgl_masuk_terakhir,
    keluar.max_tanggal AS tgl_keluar_terakhir,

    COALESCE(log.total_masuk, 0) AS total_masuk,
    COALESCE(log.total_keluar, 0) AS total_keluar

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

LEFT JOIN (
    SELECT 
        kode_obat,
        SUM(CASE WHEN tipe='masuk' THEN jumlah ELSE 0 END) AS total_masuk,
        SUM(CASE WHEN tipe='keluar' THEN jumlah ELSE 0 END) AS total_keluar
    FROM stok_log
    WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY kode_obat
) log ON log.kode_obat = o.kode_obat

ORDER BY o.nama_obat ASC
";

$query = mysqli_query($conn, $sql);

if (!$query) {
    http_response_code(500);
    die("Query export gagal: " . mysqli_error($conn));
}

/* ================== HEADER CSV ================== */
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Daftar_Obat.csv"');

// UTF-8 BOM agar Excel terbaca normal
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
$delimiter = ';';

/* ================== HEADER KOLOM ================== */
fputcsv($output, [
    'Kode Obat',
    'Nama Obat',
    'Satuan',
    'Stok Akhir',
    'Stok Minimum',
    'Status Stok',
    'Total Masuk (30 Hari)',
    'Total Keluar (30 Hari)',
    'Tanggal Masuk Terakhir',
    'Tanggal Keluar Terakhir',
    'Tanggal Kadaluarsa'
], $delimiter);

/* ================== DATA ================== */
while ($data = mysqli_fetch_assoc($query)) {

    $stok = (int)($data['stok'] ?? 0);
    $min  = (int)($data['stok_minimum'] ?? 0);

    $status_stok = ($stok <= $min) ? 'Di bawah minimum' : 'Aman';

    $tgl_masuk = !empty($data['tgl_masuk_terakhir'])
        ? date('d-m-Y H:i', strtotime($data['tgl_masuk_terakhir']))
        : '-';

    $tgl_keluar = !empty($data['tgl_keluar_terakhir'])
        ? date('d-m-Y H:i', strtotime($data['tgl_keluar_terakhir']))
        : '-';

    $tgl_expired = !empty($data['tanggal_kadaluarsa'])
        ? date('d-m-Y', strtotime($data['tanggal_kadaluarsa']))
        : '-';

    $total_masuk  = (int)($data['total_masuk'] ?? 0);
    $total_keluar = (int)($data['total_keluar'] ?? 0);

    fputcsv($output, [
        $data['kode_obat'] ?? '',
        $data['nama_obat'] ?? '',
        $data['satuan'] ?? '',
        $stok,
        $min,
        $status_stok,
        $total_masuk,
        $total_keluar,
        $tgl_masuk,
        $tgl_keluar,
        $tgl_expired
    ], $delimiter);
}

fclose($output);
exit;
?>