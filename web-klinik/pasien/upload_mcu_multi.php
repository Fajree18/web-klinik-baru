<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

// ================= PATH =================
$targetDir = dirname(__DIR__) . "/uploads/mcu/";

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// ================= VALIDASI =================
if (!isset($_POST['id_pasien']) || empty($_POST['id_pasien'])) {
    die("<div class='alert alert-danger'>ID pasien tidak ditemukan.</div>");
}

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
$tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
$file = $_FILES['file_mcu'] ?? null;

if (!$file || $file['error'] != 0) {
    die("<div class='alert alert-danger'>Gagal upload file MCU.</div>");
}

if (mime_content_type($file['tmp_name']) !== 'application/pdf') {
    die("<div class='alert alert-warning'>File harus PDF.</div>");
}

// ================= NAMA FILE ASLI =================
$namaFileAsli = basename($file['name']);
$namaFile = preg_replace('/[^A-Za-z0-9_.-]/', '_', $namaFileAsli);

// Hindari file tertimpa
$counter = 1;
$pathInfo = pathinfo($namaFile);
$namaTanpaExt = $pathInfo['filename'];
$ext = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

while (file_exists($targetDir . $namaFile)) {
    $namaFile = $namaTanpaExt . "_" . $counter . $ext;
    $counter++;
}

$targetPath = $targetDir . $namaFile;

// ================= SIMPAN FILE =================
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    die("<div class='alert alert-danger'>Gagal simpan file.</div>");
}

// ================= DATABASE =================
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pasien_mcu (
    id_mcu INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien VARCHAR(100) NOT NULL,
    tahun VARCHAR(10) NOT NULL,
    file_mcu VARCHAR(255) NOT NULL,
    tanggal_upload DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$insert = mysqli_query($conn, "INSERT INTO pasien_mcu (id_pasien, tahun, file_mcu, tanggal_upload)
    VALUES ('$id_pasien', '$tahun', '$namaFile', NOW())
");

if ($insert) {echo "<script>
        alert('✅ File MCU berhasil diupload!');
        window.location.href = 'edit_pasien.php?id=$id_pasien';
    </script>";
} else {
    echo "<div class='alert alert-danger'>Gagal simpan DB: " . mysqli_error($conn) . "</div>";
}
?>