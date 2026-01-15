<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

/**
 * ====== SESUAIKAN NAMA TABEL & KOLOM DI BAWAH INI ======
 * tabel kunjungan misal: kunjungan
 * kolom minimal:
 * - id_pasien
 * - kunjungan_status (pertama/followup/pasca_cuti)
 * - keterangan_followup
 * - keluhan
 * - id_diagnosa
 * - tindakan
 * - istirahat
 * - tanggal_kunjungan (opsional)
 *
 * tabel resep misal: resep_obat
 * kolom minimal:
 * - id_kunjungan
 * - kode_obat
 * - dosis
 * - jumlah
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: input_kunjungan.php");
    exit;
}

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien'] ?? '');
$kunjungan_status = $_POST['kunjungan_status'] ?? '';
$keterangan_followup = mysqli_real_escape_string($conn, $_POST['keterangan_followup'] ?? '');

$valid_status = ['pertama', 'followup', 'pasca_cuti'];
if (!in_array($kunjungan_status, $valid_status, true)) {
    $_SESSION['error'] = "Status kunjungan tidak valid.";
    header("Location: input_kunjungan.php");
    exit;
}

if (empty($id_pasien)) {
    $_SESSION['error'] = "Pasien wajib dipilih.";
    header("Location: input_kunjungan.php");
    exit;
}

// Field normal
$keluhan  = mysqli_real_escape_string($conn, $_POST['keluhan'] ?? '');
$tindakan = mysqli_real_escape_string($conn, $_POST['tindakan'] ?? '');
$istirahat = (int)($_POST['istirahat'] ?? 0);

// diagnosa dari dropdown -> id_diagnosa
$id_diagnosa = (int)($_POST['diagnosa'] ?? 0);

// Validasi keterangan untuk followup/pasca cuti
if (($kunjungan_status === 'followup' || $kunjungan_status === 'pasca_cuti') && trim($keterangan_followup) === '') {
    $_SESSION['error'] = "Keterangan wajib diisi untuk Followup / Pasca Cuti.";
    header("Location: input_kunjungan.php");
    exit;
}

// Kalau pasca cuti: kosongkan field normal (biar aman)
if ($kunjungan_status === 'pasca_cuti') {
    $keluhan = '';
    $tindakan = '';
    $istirahat = 0;
    $id_diagnosa = 0; // atau NULL kalau kolomnya nullable
}

// Insert kunjungan
// NOTE: kalau kolom id_diagnosa di DB kamu nullable, lebih bagus simpan NULL saat 0.
// Di bawah ini versi simpel: simpan 0.
$sql = "INSERT INTO kunjungan
        (id_pasien, kunjungan_status, keterangan_followup, keluhan, id_diagnosa, tindakan, istirahat)
        VALUES
        ('$id_pasien', '$kunjungan_status', '$keterangan_followup', '$keluhan', '$id_diagnosa', '$tindakan', '$istirahat')";

$ok = mysqli_query($conn, $sql);
if (!$ok) {
    $_SESSION['error'] = "Gagal simpan kunjungan: " . mysqli_error($conn);
    header("Location: input_kunjungan.php");
    exit;
}

$id_kunjungan = mysqli_insert_id($conn);

// Kalau bukan pasca cuti -> simpan resep
if ($kunjungan_status !== 'pasca_cuti') {
    $obatArr   = $_POST['obat'] ?? [];
    $dosisArr  = $_POST['dosis'] ?? [];
    $jumlahArr = $_POST['jumlah'] ?? [];

    // insert per baris resep (skip yang kosong)
    for ($i = 0; $i < count($obatArr); $i++) {
        $kode_obat = trim($obatArr[$i] ?? '');
        $dosis     = mysqli_real_escape_string($conn, $dosisArr[$i] ?? '');
        $jumlah    = (int)($jumlahArr[$i] ?? 0);

        if ($kode_obat === '' || $jumlah <= 0) continue;

        $kode_obat_safe = mysqli_real_escape_string($conn, $kode_obat);

        // simpan ke tabel resep_obat (sesuaikan nama tabel)
        $sqlResep = "INSERT INTO resep_obat (id_kunjungan, kode_obat, dosis, jumlah)
                     VALUES ('$id_kunjungan', '$kode_obat_safe', '$dosis', '$jumlah')";
        mysqli_query($conn, $sqlResep);

        // OPTIONAL: kurangi stok obat (jika kamu butuh)
        // mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat_safe'");
    }
}

$_SESSION['success'] = "Kunjungan berhasil disimpan.";
header("Location: input_kunjungan.php");
exit;
