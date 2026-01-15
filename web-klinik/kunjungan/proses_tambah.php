<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tambah.php");
    exit;
}

function hasColumn($conn, $table, $column) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($q && mysqli_num_rows($q) > 0);
}

// ===== Ambil input utama =====
$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien'] ?? '');
$status_kunjungan = $_POST['kunjungan_status'] ?? '';
$keterangan_followup = mysqli_real_escape_string($conn, $_POST['keterangan_followup'] ?? '');

$valid_status = ['pertama', 'followup', 'pasca_cuti'];
if (!$id_pasien) {
    $_SESSION['error'] = "Pasien wajib dipilih.";
    header("Location: tambah.php");
    exit;
}
if (!in_array($status_kunjungan, $valid_status, true)) {
    $_SESSION['error'] = "Status kunjungan tidak valid.";
    header("Location: tambah.php");
    exit;
}

// keterangan wajib untuk followup & pasca_cuti
if (($status_kunjungan === 'followup' || $status_kunjungan === 'pasca_cuti') && trim($keterangan_followup) === '') {
    $_SESSION['error'] = "Keterangan wajib diisi untuk Followup / Pasca Cuti.";
    header("Location: tambah.php");
    exit;
}

// ===== Field normal =====
$keluhan  = mysqli_real_escape_string($conn, $_POST['keluhan'] ?? '');
$tindakan = mysqli_real_escape_string($conn, $_POST['tindakan'] ?? '');
$istirahat = (int)($_POST['istirahat'] ?? 0);

// diagnosa dari dropdown (id)
$id_diagnosa = (int)($_POST['diagnosa'] ?? 0);

// kalau pasca cuti: kosongkan field normal
if ($status_kunjungan === 'pasca_cuti') {
    $keluhan = '';
    $tindakan = '';
    $istirahat = 0;
    $id_diagnosa = 0;
}

// ===== Deteksi kolom-kolom DB biar gak 500 =====
$col_keterangan = hasColumn($conn, 'kunjungan', 'keterangan_followup');
$col_id_diagnosa = hasColumn($conn, 'kunjungan', 'id_diagnosa');
$col_diagnosa_text = hasColumn($conn, 'kunjungan', 'diagnosa');
$col_status = hasColumn($conn, 'kunjungan', 'status_kunjungan'); // ini yang dipakai file lain
$col_tanggal = hasColumn($conn, 'kunjungan', 'tanggal_kunjungan');
$col_id_awal = hasColumn($conn, 'kunjungan', 'id_kunjungan_awal');

// ===== Start transaction =====
mysqli_begin_transaction($conn);

try {

    // ===== Tentukan diagnosa text fallback (kalau kolom diagnosa text masih dipakai) =====
    $diagnosa_text = '';
    if ($col_diagnosa_text && $id_diagnosa > 0) {
        // ambil nama diagnosa dari tabel diagnosa
        $qd = mysqli_query($conn, "SELECT nama_diagnosa FROM diagnosa WHERE id_diagnosa = $id_diagnosa LIMIT 1");
        if ($qd && mysqli_num_rows($qd) > 0) {
            $diagnosa_text = mysqli_fetch_assoc($qd)['nama_diagnosa'];
        }
        $diagnosa_text = mysqli_real_escape_string($conn, $diagnosa_text);
    }

    // ===== Untuk followup: cari id_kunjungan awal (kunjungan pertama terakhir) kalau kolom ada =====
    $id_kunjungan_awal = "NULL";
    if ($status_kunjungan === 'followup' && $col_id_awal) {
        $q_awal = mysqli_query($conn, "SELECT id_kunjungan
            FROM kunjungan
            WHERE id_pasien = '$id_pasien' AND status_kunjungan = 'pertama'
            ORDER BY tanggal_kunjungan DESC
            LIMIT 1
        ");
        if ($q_awal && mysqli_num_rows($q_awal) > 0) {
            $id_kunjungan_awal = (int)mysqli_fetch_assoc($q_awal)['id_kunjungan'];
        }
    }

    // ===== Build INSERT kunjungan dinamis sesuai kolom yang ada =====
    $cols = ["id_pasien"];
    $vals = ["'$id_pasien'"];

    if ($col_tanggal) {
        $cols[] = "tanggal_kunjungan";
        $vals[] = "NOW()";
    }

    // status kolom wajib di sistem kamu
    if ($col_status) {
        $cols[] = "status_kunjungan";
        $vals[] = "'" . mysqli_real_escape_string($conn, $status_kunjungan) . "'";
    } else {
        // kalau ternyata kolomnya beda (jarang), paksa gagal biar ketauan
        throw new Exception("Kolom status_kunjungan tidak ditemukan di tabel kunjungan.");
    }

    if ($col_keterangan) {
        $cols[] = "keterangan_followup";
        $vals[] = "'" . mysqli_real_escape_string($conn, $keterangan_followup) . "'";
    }

    // keluhan/tindakan/istirahat tetap kita insert kalau kolomnya ada
    if (hasColumn($conn, 'kunjungan', 'keluhan')) {
        $cols[] = "keluhan";
        $vals[] = "'" . $keluhan . "'";
    }
    if (hasColumn($conn, 'kunjungan', 'tindakan')) {
        $cols[] = "tindakan";
        $vals[] = "'" . $tindakan . "'";
    }
    if (hasColumn($conn, 'kunjungan', 'istirahat')) {
        $cols[] = "istirahat";
        $vals[] = (int)$istirahat;
    }

    // diagnosa: simpan id_diagnosa kalau kolom tersedia
    if ($col_id_diagnosa) {
        $cols[] = "id_diagnosa";
        $vals[] = ($id_diagnosa > 0) ? (int)$id_diagnosa : "NULL";
    }

    // fallback: simpan diagnosa text kalau kolom diagnosa ada
    if ($col_diagnosa_text) {
        $cols[] = "diagnosa";
        $vals[] = "'" . $diagnosa_text . "'";
    }

    // followup link ke kunjungan awal jika kolom ada
    if ($status_kunjungan === 'followup' && $col_id_awal) {
        $cols[] = "id_kunjungan_awal";
        $vals[] = ($id_kunjungan_awal === "NULL") ? "NULL" : (int)$id_kunjungan_awal;
    }

    $sqlInsert = "INSERT INTO kunjungan (" . implode(", ", $cols) . ") VALUES (" . implode(", ", $vals) . ")";
    $ok = mysqli_query($conn, $sqlInsert);
    if (!$ok) {
        throw new Exception("Gagal simpan kunjungan: " . mysqli_error($conn));
    }

    $id_kunjungan = mysqli_insert_id($conn);

    // ===== Kalau bukan pasca cuti, simpan resep + kurangi stok + stok_log =====
    if ($status_kunjungan !== 'pasca_cuti') {
        $obatArr   = $_POST['obat'] ?? [];
        $dosisArr  = $_POST['dosis'] ?? [];
        $jumlahArr = $_POST['jumlah'] ?? [];

        // minimal 1 resep boleh kosong? kalau kamu mau wajib, tinggal validasi di sini
        for ($i = 0; $i < count($obatArr); $i++) {
            $kode_obat = trim($obatArr[$i] ?? '');
            $dosis     = trim($dosisArr[$i] ?? '');
            $jumlah    = (int)($jumlahArr[$i] ?? 0);

            if ($kode_obat === '' || $dosis === '' || $jumlah <= 0) {
                continue;
            }

            $kode_obat_safe = mysqli_real_escape_string($conn, $kode_obat);
            $dosis_safe     = mysqli_real_escape_string($conn, $dosis);

            // cek stok cukup
            $cek = mysqli_query($conn, "SELECT stok FROM obat WHERE kode_obat = '$kode_obat_safe' LIMIT 1");
            if (!$cek || mysqli_num_rows($cek) == 0) {
                throw new Exception("Obat tidak ditemukan: $kode_obat_safe");
            }
            $stokNow = (int)mysqli_fetch_assoc($cek)['stok'];
            if ($stokNow < $jumlah) {
                throw new Exception("Stok obat tidak cukup untuk $kode_obat_safe. Stok: $stokNow, diminta: $jumlah");
            }

            // insert resep (tabel kamu: resep)
            $sqlResep = "INSERT INTO resep (id_kunjungan, kode_obat, dosis, jumlah)
                         VALUES ($id_kunjungan, '$kode_obat_safe', '$dosis_safe', $jumlah)";
            if (!mysqli_query($conn, $sqlResep)) {
                throw new Exception("Gagal simpan resep: " . mysqli_error($conn));
            }

            // kurangi stok
            if (!mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat_safe'")) {
                throw new Exception("Gagal update stok obat: " . mysqli_error($conn));
            }

            // catat log stok keluar
            if (hasColumn($conn, 'stok_log', 'id_kunjungan')) {
                $sqlLog = "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan)
                           VALUES ('$kode_obat_safe', 'keluar', $jumlah, NOW(), $id_kunjungan)";
            } else {
                // fallback kalau stok_log belum ada kolom id_kunjungan
                $sqlLog = "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal)
                           VALUES ('$kode_obat_safe', 'keluar', $jumlah, NOW())";
            }

            if (!mysqli_query($conn, $sqlLog)) {
                throw new Exception("Gagal simpan stok_log: " . mysqli_error($conn));
            }
        }
    }

    mysqli_commit($conn);

    $_SESSION['success'] = "Kunjungan berhasil disimpan.";
    header("Location: tambah.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
    header("Location: tambah.php");
    exit;
}
