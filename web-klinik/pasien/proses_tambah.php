<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

$id_pasien = isset($_POST['id_pasien']) ? mysqli_real_escape_string($conn, $_POST['id_pasien']) : '';
$nama = isset($_POST['nama']) ? mysqli_real_escape_string($conn, $_POST['nama']) : '';
$departemen = isset($_POST['departemen']) ? mysqli_real_escape_string($conn, $_POST['departemen']) : '';
$jabatan = isset($_POST['jabatan']) ? mysqli_real_escape_string($conn, $_POST['jabatan']) : '';
$tgl = isset($_POST['tgl']) ? $_POST['tgl'] : '';
$bln = isset($_POST['bln']) ? $_POST['bln'] : '';
$thn = isset($_POST['thn']) ? $_POST['thn'] : '';
$tanggal_lahir = "$thn-" . str_pad($bln, 2, '0', STR_PAD_LEFT) . '-' . str_pad($tgl, 2, '0', STR_PAD_LEFT);
$jenis_kelamin = isset($_POST['jenis_kelamin']) ? mysqli_real_escape_string($conn, $_POST['jenis_kelamin']) : '';
$alamat = isset($_POST['alamat']) ? mysqli_real_escape_string($conn, $_POST['alamat']) : '';
$riwayat = isset($_POST['riwayat_sakit']) ? mysqli_real_escape_string($conn, $_POST['riwayat_sakit']) : '';
$telepon = isset($_POST['telepon']) ? mysqli_real_escape_string($conn, $_POST['telepon']) : '';
$no_rm = isset($_POST['no_rm']) ? $_POST['no_rm'] : '';

$cek = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id_pasien'");
if (mysqli_num_rows($cek) > 0) {
    echo "<script>alert('ID Pasien / NIK sudah terdaftar. Gunakan yang lain.'); window.location='tambah.php';</script>";
    exit;
}

mysqli_query($conn, "INSERT INTO pasien (id_pasien, nama, departemen, jabatan, tanggal_lahir, jenis_kelamin, alamat, riwayat_sakit, telepon, no_rm, created_at)
VALUES ('$id_pasien', '$nama', '$departemen', '$jabatan', '$tanggal_lahir', '$jenis_kelamin', '$alamat', '$riwayat', '$telepon', '$no_rm', NOW())");

header("Location: daftar_pasien.php");
exit;
?>
