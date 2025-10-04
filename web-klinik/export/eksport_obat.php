<?php
include "../koneksi.php";

// Query untuk mengambil data obat
$query = mysqli_query($conn, "SELECT o.kode_obat, o.nama_obat, o.satuan, o.stok, 
        o.tanggal_kadaluarsa,
        (SELECT MAX(tanggal) FROM stok_log WHERE kode_obat = o.kode_obat AND tipe = 'masuk') AS tgl_masuk_terakhir 
        FROM obat o ORDER BY o.nama_obat ASC");

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="Daftar_Obat.csv"');
$output = fopen('php://output', 'w');

// Menulis header CSV
fputcsv($output, ['Kode Obat', 'Nama Obat', 'Satuan', 'Stok Akhir', 'Tanggal Masuk Terakhir', 'Tanggal Kadaluarsa']);

// Menulis data obat ke dalam CSV
while ($data = mysqli_fetch_assoc($query)) {
    // Format tanggal kadaluarsa dan tanggal masuk terakhir
    $tgl_masuk = $data['tgl_masuk_terakhir'] ? date('d-m-Y', strtotime($data['tgl_masuk_terakhir'])) : '-';
    $tanggal_expired = $data['tanggal_kadaluarsa'] ? date('d-m-Y', strtotime($data['tanggal_kadaluarsa'])) : '-'; // Tanggal expired

    // Menulis data ke dalam CSV
    fputcsv($output, [
        $data['kode_obat'],
        $data['nama_obat'],
        $data['satuan'],        
        $data['stok'],
        $tgl_masuk,
        $tanggal_expired, // Menambahkan kolom tanggal expired
    ]);
}

fclose($output);
exit;
?>