<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

$id_kunjungan = isset($_GET['id_kunjungan']) ? (int)$_GET['id_kunjungan'] : 0;

if ($id_kunjungan <= 0) {
    die("ID kunjungan tidak valid.");
}


mysqli_query($conn, "DELETE FROM resep WHERE id_kunjungan = $id_kunjungan");


$hapus = mysqli_query($conn, "DELETE FROM kunjungan WHERE id_kunjungan = $id_kunjungan");

if ($hapus) {
    header("Location: daftar_kunjungan.php?success=1");
    exit;
} else {
    die("Gagal menghapus data kunjungan.");
}
