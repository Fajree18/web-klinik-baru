<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
    $keluhan = mysqli_real_escape_string($conn, $_POST['keluhan']);
    $diagnosa = mysqli_real_escape_string($conn, $_POST['diagnosa']);
    $tindakan = mysqli_real_escape_string($conn, $_POST['tindakan']);
    $istirahat = (int)$_POST['istirahat'];

    
    $valid_status = ['pertama', 'followup'];
    $kunjungan_status = $_POST['kunjungan_status'] ?? '';
    if (!in_array($kunjungan_status, $valid_status)) {
        $_SESSION['error'] = "Status kunjungan harus dipilih.";
        header("Location: tambah.php");
        exit;
    }

    $tanggal_kunjungan = date('Y-m-d H:i:s');

  
    $sql = "INSERT INTO kunjungan (id_pasien, tanggal_kunjungan, keluhan, diagnosa, tindakan, istirahat, status_kunjungan)
            VALUES ('$id_pasien', '$tanggal_kunjungan', '$keluhan', '$diagnosa', '$tindakan', $istirahat, '$kunjungan_status')";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        $_SESSION['error'] = "Gagal menyimpan data kunjungan: " . mysqli_error($conn);
        header("Location: tambah.php");
        exit;
    }

    $id_kunjungan = mysqli_insert_id($conn);

    // Simpan resep obat
    if (isset($_POST['obat']) && isset($_POST['jumlah'])) {
        foreach ($_POST['obat'] as $index => $kode_obat) {
            $kode_obat = mysqli_real_escape_string($conn, $kode_obat);
            $jumlah = (int)$_POST['jumlah'][$index];
            if ($kode_obat && $jumlah > 0) {
                mysqli_query($conn, "INSERT INTO resep (id_kunjungan, kode_obat, jumlah) VALUES ($id_kunjungan, '$kode_obat', $jumlah)");
                mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat'");
                mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan) VALUES ('$kode_obat', 'keluar', $jumlah, NOW(), $id_kunjungan)");
            }
        }
    }

    $_SESSION['success'] = "Kunjungan berhasil disimpan.";
    header("Location: daftar_kunjungan.php");
    exit;
} else {
    $_SESSION['error'] = "Metode request tidak valid.";
    header("Location: tambah.php");
    exit;
}
?>