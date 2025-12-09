<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

// --- Ambil data dari form ---
$kode_obat = mysqli_real_escape_string($conn, $_POST['kode_obat']);
$nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
$satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
$stok = (int) $_POST['stok'];
$stok_minimum = (int) $_POST['stok_minimum'];
$tanggal_kadaluarsa = mysqli_real_escape_string($conn, $_POST['tanggal_kadaluarsa']);

// --- Validasi dasar ---
if (empty($kode_obat) || empty($nama_obat) || empty($satuan) || empty($tanggal_kadaluarsa)) {
    echo "<script>alert('Semua field wajib diisi.'); history.back();</script>";
    exit;
}

// --- Cek duplikat kode obat ---
$cek = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat = '$kode_obat'");
if (mysqli_num_rows($cek) > 0) {
    echo "<script>alert('Kode obat sudah terdaftar!'); history.back();</script>";
    exit;
}

// --- Simpan ke database ---
$query = "
    INSERT INTO obat (kode_obat, nama_obat, satuan, stok, stok_minimum, tanggal_kadaluarsa, created_at)
    VALUES ('$kode_obat', '$nama_obat', '$satuan', '$stok', '$stok_minimum', '$tanggal_kadaluarsa', NOW())
";

if (mysqli_query($conn, $query)) {
    echo "<script>
        alert('Data obat berhasil disimpan.');
        window.location = 'daftar_obat.php';
    </script>";
} else {
    echo "<script>
        alert('Gagal menyimpan data: " . mysqli_error($conn) . "');
        history.back();
    </script>";
}
?>
