<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "<script>alert('ID MCU tidak ditemukan.'); history.back();</script>";
    exit;
}

$id_mcu = mysqli_real_escape_string($conn, $_GET['id']);

// Ambil data MCU
$q = mysqli_query($conn, "SELECT * FROM pasien_mcu WHERE id_mcu = '$id_mcu'");
if (mysqli_num_rows($q) == 0) {
    echo "<script>alert('Data MCU tidak ditemukan di database.'); history.back();</script>";
    exit;
}

$mcu = mysqli_fetch_assoc($q);
$id_pasien = $mcu['id_pasien'];
$file_mcu = $mcu['file_mcu'];
$targetPath = "../uploads/mcu/" . $file_mcu;

// Hapus file fisik jika ada
if (file_exists($targetPath)) {
    unlink($targetPath);
}

// Hapus dari database
mysqli_query($conn, "DELETE FROM pasien_mcu WHERE id_mcu = '$id_mcu'");

// Redirect ke halaman edit pasien / histori
if (isset($_SERVER['HTTP_REFERER'])) {
    echo "<script>alert('File MCU berhasil dihapus.'); window.location='" . $_SERVER['HTTP_REFERER'] . "';</script>";
} else {
    header("Location: edit_pasien.php?id=$id_pasien");
}
exit;
?>
