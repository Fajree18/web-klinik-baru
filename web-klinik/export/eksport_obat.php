<?php
include "../koneksi.php";

// Query lengkap dengan stok minimum dan tanggal masuk terakhir
$query = mysqli_query($conn, "SELECT 
        o.kode_obat, 
        o.nama_obat, 
        o.satuan, 
        o.stok, 
        o.stok_minimum,
        o.tanggal_kadaluarsa,
        (SELECT MAX(tanggal) 
         FROM stok_log 
         WHERE kode_obat = o.kode_obat 
         AND tipe = 'masuk') AS tgl_masuk_terakhir
    FROM obat o
    ORDER BY o.nama_obat ASC
");

// Header untuk file CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="Daftar_Obat.csv"');

// Buat pointer output ke stream
$output = fopen('php://output', 'w');

// Tulis header kolom CSV
fputcsv($output, [
    'Kode Obat', 
    'Nama Obat', 
    'Satuan', 
    'Stok Akhir', 
    'Stok Minimum', 
    'Status Stok',
    'Tanggal Masuk Terakhir', 
    'Tanggal Kadaluarsa'
]);

// Tulis data obat ke CSV
while ($data = mysqli_fetch_assoc($query)) {
    // Format tanggal
    $tgl_masuk = $data['tgl_masuk_terakhir'] 
        ? date('d-m-Y', strtotime($data['tgl_masuk_terakhir'])) 
        : '-';
    $tanggal_expired = $data['tanggal_kadaluarsa'] 
        ? date('d-m-Y', strtotime($data['tanggal_kadaluarsa'])) 
        : '-';

    // Status stok
    $status_stok = ((int)$data['stok'] <= (int)$data['stok_minimum']) 
        ? '⚠️ Di Bawah Minimum' 
        : 'Aman';

    // Tulis ke CSV
    fputcsv($output, [
        $data['kode_obat'],
        $data['nama_obat'],
        $data['satuan'],
        (int)$data['stok'],
        (int)$data['stok_minimum'],
        $status_stok,
        $tgl_masuk,
        $tanggal_expired
    ]);
}

// Tutup output stream
fclose($output);
exit;
?>
