<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Metode request tidak valid.";
    header("Location: followup.php");
    exit;
}

// ====================== AMBIL INPUT ======================
$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien'] ?? '');
$catatan   = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
$tindakan  = mysqli_real_escape_string($conn, trim($_POST['tindakan'] ?? ''));
$istirahat = isset($_POST['istirahat']) ? (int)$_POST['istirahat'] : 0;
$status_followup = mysqli_real_escape_string($conn, $_POST['status_followup'] ?? '');

$diagnosa_input_raw = trim((string)($_POST['diagnosa'] ?? ''));

// validasi minimal
if ($id_pasien === '' || $catatan === '' || $tindakan === '' || $diagnosa_input_raw === '') {
    $_SESSION['error'] = "Mohon lengkapi data (pasien, catatan, diagnosa, tindakan).";
    header("Location: followup.php");
    exit;
}

if (!in_array($status_followup, ['Proses', 'Sembuh'], true)) {
    $_SESSION['error'] = "Status follow-up tidak valid.";
    header("Location: followup.php");
    exit;
}

$tanggal_kunjungan = date('Y-m-d H:i:s');

// ====================== HANDLE DIAGNOSA DROPDOWN ======================
// Diagnosa bisa:
// - angka (id_diagnosa) jika tabel diagnosa ada
// - teks (fallback manual)
$diagnosa_text = '';

$is_numeric_diag = ctype_digit($diagnosa_input_raw);

if ($is_numeric_diag) {
    $id_diagnosa = (int)$diagnosa_input_raw;

    // cek tabel diagnosa ada atau tidak (biar gak 500)
    $cek = mysqli_query($conn, "SHOW TABLES LIKE 'diagnosa'");
    if ($cek && mysqli_num_rows($cek) > 0) {
        $qd = mysqli_query($conn, "SELECT nama_diagnosa FROM diagnosa WHERE id_diagnosa = $id_diagnosa LIMIT 1");
        if ($qd && mysqli_num_rows($qd) > 0) {
            $rowd = mysqli_fetch_assoc($qd);
            $diagnosa_text = $rowd['nama_diagnosa'];
        } else {
            // ID tidak ketemu -> fallback simpan input raw saja
            $diagnosa_text = $diagnosa_input_raw;
        }
    } else {
        // tabel diagnosa belum ada -> fallback simpan input raw
        $diagnosa_text = $diagnosa_input_raw;
    }
} else {
    // diagnosa berupa teks
    $diagnosa_text = $diagnosa_input_raw;
}

$diagnosa_text = mysqli_real_escape_string($conn, $diagnosa_text);

// ====================== CARI KUNJUNGAN AWAL TERAKHIR ======================
$q_awal = mysqli_query($conn, "SELECT id_kunjungan 
    FROM kunjungan
    WHERE id_pasien = '$id_pasien'
      AND status_kunjungan = 'pertama'
    ORDER BY tanggal_kunjungan DESC
    LIMIT 1
");

$id_kunjungan_awal = "NULL";
if ($q_awal && ($awal = mysqli_fetch_assoc($q_awal))) {
    $id_kunjungan_awal = (int)$awal['id_kunjungan'];
}

// ====================== INSERT KUNJUNGAN FOLLOWUP ======================
$insert = "INSERT INTO kunjungan
        (id_pasien, tanggal_kunjungan, keluhan, diagnosa, tindakan, istirahat, status_kunjungan, id_kunjungan_awal)
    VALUES
        ('$id_pasien', '$tanggal_kunjungan', '$catatan', '$diagnosa_text', '$tindakan', $istirahat, 'followup', $id_kunjungan_awal)
";

$result = mysqli_query($conn, $insert);

if (!$result) {
    $_SESSION['error'] = "Gagal menyimpan data follow-up: " . mysqli_error($conn);
    header("Location: followup.php");
    exit;
}

$id_kunjungan = (int)mysqli_insert_id($conn);

// ====================== SIMPAN RESEP & UPDATE STOK ======================
$obat_arr   = $_POST['obat'] ?? [];
$dosis_arr  = $_POST['dosis'] ?? [];
$jumlah_arr = $_POST['jumlah'] ?? [];

if (is_array($obat_arr) && is_array($dosis_arr) && is_array($jumlah_arr)) {
    foreach ($obat_arr as $i => $kode_obat) {
        $kode_obat = trim((string)$kode_obat);
        $dosis     = trim((string)($dosis_arr[$i] ?? ''));
        $jumlah    = isset($jumlah_arr[$i]) ? (int)$jumlah_arr[$i] : 0;

        if ($kode_obat === '' || $dosis === '' || $jumlah <= 0) continue;

        $kode_obat_safe = mysqli_real_escape_string($conn, $kode_obat);
        $dosis_safe     = mysqli_real_escape_string($conn, $dosis);

        // Insert resep
        mysqli_query($conn, "
            INSERT INTO resep (id_kunjungan, kode_obat, dosis, jumlah)
            VALUES ($id_kunjungan, '$kode_obat_safe', '$dosis_safe', $jumlah)
        ");

        // Kurangi stok
        mysqli_query($conn, "
            UPDATE obat
            SET stok = stok - $jumlah
            WHERE kode_obat = '$kode_obat_safe'
        ");

        // Log stok keluar
        mysqli_query($conn, "
            INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan)
            VALUES ('$kode_obat_safe', 'keluar', $jumlah, NOW(), $id_kunjungan)
        ");
    }
}

// ====================== (OPSIONAL) STATUS FOLLOWUP ======================
// Kamu punya field status_followup tapi belum disimpan ke tabel.
// Kalau di tabel kunjungan ada kolom status_followup, aktifkan ini:
//
// mysqli_query($conn, "UPDATE kunjungan SET status_followup='$status_followup' WHERE id_kunjungan=$id_kunjungan");

$_SESSION['success'] = "Follow-up pasien berhasil disimpan.";
header("Location: daftar_kunjungan.php");
exit;
?>
