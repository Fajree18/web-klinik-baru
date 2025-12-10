<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

// Ambil semua input dari form
$id_pasien       = mysqli_real_escape_string($conn, $_POST['id_pasien'] ?? '');
$nama            = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
$departemen      = mysqli_real_escape_string($conn, $_POST['departemen'] ?? '');
$jabatan         = mysqli_real_escape_string($conn, $_POST['jabatan'] ?? '');
$tgl             = $_POST['tgl'] ?? '';
$bln             = $_POST['bln'] ?? '';
$thn             = $_POST['thn'] ?? '';
$tanggal_lahir   = "$thn-" . str_pad($bln, 2, '0', STR_PAD_LEFT) . '-' . str_pad($tgl, 2, '0', STR_PAD_LEFT);
$jenis_kelamin   = mysqli_real_escape_string($conn, $_POST['jenis_kelamin'] ?? '');
$alamat          = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
$riwayat         = mysqli_real_escape_string($conn, $_POST['riwayat_sakit'] ?? '');
$telepon         = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');

// Cek NIK sudah ada belum
$cek = mysqli_query($conn, "SELECT 1 FROM pasien WHERE id_pasien = '$id_pasien' LIMIT 1");
if (mysqli_num_rows($cek) > 0) {
    echo "<script>alert('ID Pasien / NIK sudah terdaftar. Gunakan yang lain.'); window.location='tambah_pasien.php';</script>";
    exit;
}

// === Generate Nomor Rekam Medis Otomatis ===
$prefix = "MED-" . date("Ym");
$getLast = mysqli_query($conn, "SELECT MAX(no_rm) AS max_rm FROM pasien WHERE no_rm LIKE '$prefix%'");
$last = mysqli_fetch_assoc($getLast);
$nextNumber = "0001";

if ($last && $last['max_rm']) {
    $lastNum = (int)substr($last['max_rm'], -4);
    $nextNumber = str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
}

$no_rm = $prefix . $nextNumber;

// Simpan ke database
$sql = "INSERT INTO pasien 
    (id_pasien, nama, departemen, jabatan, tanggal_lahir, jenis_kelamin, alamat, riwayat_sakit, telepon, no_rm, created_at)
    VALUES 
    ('$id_pasien', '$nama', '$departemen', '$jabatan', '$tanggal_lahir', '$jenis_kelamin', '$alamat', '$riwayat', '$telepon', '$no_rm', NOW())";

if (mysqli_query($conn, $sql)) {
    header("Location: daftar_pasien.php");
    exit;
} else {
    echo "<script>alert('Gagal menyimpan data: " . mysqli_error($conn) . "'); history.back();</script>";
}
?>
