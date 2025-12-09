<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

// ============== CONFIG UPLOAD PATH ==============
$targetDir = dirname(__DIR__) . "/uploads/mcu/"; // otomatis ambil folder di atas 'pasien'

// Pastikan folder ada
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// ============== VALIDASI DATA ==============
if (!isset($_POST['id_pasien']) || empty($_POST['id_pasien'])) {
    die("<div class='alert alert-danger'>ID pasien tidak ditemukan.</div>");
}

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
$tahun = mysqli_real_escape_string($conn, $_POST['tahun']);
$file = $_FILES['file_mcu'] ?? null;

if (!$file || $file['error'] != 0) {
    die("<div class='alert alert-danger'>Gagal upload file MCU. Coba periksa kembali file PDF-nya.</div>");
}

if (mime_content_type($file['tmp_name']) !== 'application/pdf') {
    die("<div class='alert alert-warning'>File MCU harus berformat <strong>PDF</strong>.</div>");
}

// ============== PROSES SIMPAN FILE ==============
$namaFile = "MCU_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $id_pasien) . "_" . $tahun . "_" . time() . ".pdf";
$targetPath = $targetDir . $namaFile;

// Coba simpan file ke server
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    die("<div class='alert alert-danger'>Gagal menyimpan file MCU ke folder server. Cek permission folder uploads/mcu.</div>");
}

// ============== SIMPAN KE DATABASE ==============
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pasien_mcu (
    id_mcu INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien VARCHAR(100) NOT NULL,
    tahun VARCHAR(10) NOT NULL,
    file_mcu VARCHAR(255) NOT NULL,
    tanggal_upload DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$insert = mysqli_query($conn, "
    INSERT INTO pasien_mcu (id_pasien, tahun, file_mcu, tanggal_upload)
    VALUES ('$id_pasien', '$tahun', '$namaFile', NOW())
");

if ($insert) {
    echo "<script>
        alert('✅ File MCU berhasil diupload!');
        window.location.href = 'edit_pasien.php?id=$id_pasien';
    </script>";
} else {
    echo "<div class='alert alert-danger'>Gagal menyimpan data ke database: " . mysqli_error($conn) . "</div>";
}
?>
