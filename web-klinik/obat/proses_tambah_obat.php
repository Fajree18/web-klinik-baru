<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_obat = mysqli_real_escape_string($conn, $_POST['kode_obat']);
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $stok = (int)$_POST['stok'];
    $tanggal_kadaluarsa = mysqli_real_escape_string($conn, $_POST['tanggal_kadaluarsa']);
    $tanggal = date('Y-m-d H:i:s');

    
    $cek = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat = '$kode_obat'");
    if (mysqli_num_rows($cek) > 0) {
       
        $row = mysqli_fetch_assoc($cek);
        $stok_baru = $row['stok'] + $stok;
        $update = mysqli_query($conn, "UPDATE obat SET nama_obat='$nama_obat', satuan='$satuan', stok=$stok_baru, tanggal_kadaluarsa='$tanggal_kadaluarsa' WHERE kode_obat='$kode_obat'");
        if ($update) {
           
            mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal) VALUES ('$kode_obat', 'masuk', $stok, '$tanggal')");
            $_SESSION['message'] = "Obat sudah ada. Stok berhasil diperbarui.";
        } else {
            $_SESSION['message'] = "Gagal memperbarui stok obat.";
        }
    } else {
        
        $insert = mysqli_query($conn, "INSERT INTO obat (kode_obat, nama_obat, satuan, stok, tanggal_kadaluarsa) VALUES ('$kode_obat', '$nama_obat', '$satuan', $stok, '$tanggal_kadaluarsa')");
        if ($insert) {
            mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal) VALUES ('$kode_obat', 'masuk', $stok, '$tanggal')");
            $_SESSION['message'] = "Obat berhasil ditambahkan.";
        } else {
            $_SESSION['message'] = "Gagal menambahkan obat.";
        }
    }
} else {
    $_SESSION['message'] = "Metode request tidak valid.";
}

header("Location: tambah.php");
exit;
?>