<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
    $catatan   = mysqli_real_escape_string($conn, $_POST['catatan']);
    $diagnosa  = mysqli_real_escape_string($conn, $_POST['diagnosa']);
    $tindakan  = mysqli_real_escape_string($conn, $_POST['tindakan']);
    $istirahat = (int)$_POST['istirahat'];
    $status_followup = mysqli_real_escape_string($conn, $_POST['status_followup']);
    $tanggal_kunjungan = date('Y-m-d H:i:s');

    // Cari ID kunjungan pertama terakhir (yang belum dinyatakan sembuh)
    $q_awal = mysqli_query($conn, "SELECT id_kunjungan FROM kunjungan 
        WHERE id_pasien = '$id_pasien' 
        AND status_kunjungan = 'pertama'
        ORDER BY tanggal_kunjungan DESC LIMIT 1");

    $id_kunjungan_awal = "NULL";
    if ($awal = mysqli_fetch_assoc($q_awal)) {
        $id_kunjungan_awal = $awal['id_kunjungan'];
    }

    // Simpan kunjungan follow-up
    $insert = "INSERT INTO kunjungan 
        (id_pasien, tanggal_kunjungan, keluhan, diagnosa, tindakan, istirahat, status_kunjungan, id_kunjungan_awal)
        VALUES 
        ('$id_pasien', '$tanggal_kunjungan', '$catatan', '$diagnosa', '$tindakan', $istirahat, 'followup', $id_kunjungan_awal)";
    $result = mysqli_query($conn, $insert);

    if (!$result) {
        $_SESSION['error'] = "Gagal menyimpan data follow-up: " . mysqli_error($conn);
        header("Location: followup.php");
        exit;
    }

    $id_kunjungan = mysqli_insert_id($conn);

    // Simpan resep dan dosis
    if (isset($_POST['obat'], $_POST['dosis'], $_POST['jumlah'])) {
        foreach ($_POST['obat'] as $i => $kode_obat) {
            $kode_obat = mysqli_real_escape_string($conn, $kode_obat);
            $dosis     = mysqli_real_escape_string($conn, $_POST['dosis'][$i]);
            $jumlah    = (int)$_POST['jumlah'][$i];

            if ($kode_obat && $dosis && $jumlah > 0) {
                // Insert ke tabel resep
                mysqli_query($conn, "INSERT INTO resep (id_kunjungan, kode_obat, dosis, jumlah) 
                                     VALUES ($id_kunjungan, '$kode_obat', '$dosis', $jumlah)");

                // Kurangi stok obat
                mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat'");

                // Catat log stok keluar
                mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan)
                                     VALUES ('$kode_obat', 'keluar', $jumlah, NOW(), $id_kunjungan)");
            }
        }
    }

    $_SESSION['success'] = "Follow-up pasien berhasil disimpan.";
    header("Location: daftar_kunjungan.php");
    exit;

} else {
    $_SESSION['error'] = "Metode request tidak valid.";
    header("Location: followup.php");
    exit;
}
?>