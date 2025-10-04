<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

$id_kunjungan = (int)($_GET['id_kunjungan'] ?? 0);
if ($id_kunjungan <= 0) {
    die("ID kunjungan tidak valid.");
}

$q = mysqli_query($conn, "SELECT k.*, p.nama, p.no_rm, p.departemen, p.tanggal_lahir, p.jenis_kelamin, p.id_pasien, p.jabatan FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien WHERE k.id_kunjungan = $id_kunjungan");
$data = mysqli_fetch_assoc($q);
if (!$data) {
    die("Data kunjungan tidak ditemukan.");
}

$obat_q = mysqli_query($conn, "SELECT o.nama_obat, r.jumlah FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat WHERE r.id_kunjungan = $id_kunjungan");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Surat Keterangan Sakit</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .border-box { border: 1px solid #000; padding: 20px; }
    </style>
</head>
<body class="container mt-5">
    <div class="border-box">
        <h5 class="text-center mb-4">SURAT KETERANGAN SAKIT</h5>
        <p>Yang bertanda tangan di bawah ini menyatakan bahwa:</p>
        <table class="table table-borderless">
            <tr><td style="width:200px">Nama</td><td>: <?= htmlspecialchars($data['nama']) ?></td></tr>
            <tr><td>No. Rekam Medis</td><td>: <?= htmlspecialchars($data['no_rm']) ?></td></tr>
            <tr><td>NIK</td><td>: <?= htmlspecialchars($data['id_pasien']) ?></td></tr>
            <tr><td>Jabatan</td><td>: <?= htmlspecialchars($data['jabatan']) ?></td></tr>
            <tr><td>Departemen</td><td>: <?= htmlspecialchars($data['departemen']) ?></td></tr>
            <tr><td>Jenis Kelamin</td><td>: <?= htmlspecialchars($data['jenis_kelamin']) ?></td></tr>
            <tr><td>Tanggal Lahir</td><td>: <?= htmlspecialchars($data['tanggal_lahir']) ?></td></tr>
        </table>

        <p>Setelah dilakukan pemeriksaan pada tanggal <strong>
            <?= htmlspecialchars($data['tanggal_kunjungan']) ?></strong>, pasien tersebut perlu istirahat selama <strong> <?= (int)$data['istirahat'] ?> hari</strong> karena alasan medis dengan diagnosa:
        <strong><?= nl2br(htmlspecialchars($data['diagnosa'])) ?> </strong> Tindakan medis: <strong><?= nl2br(htmlspecialchars($data['tindakan'])) ?></strong></p>

        <p><strong>Obat yang diberikan:</strong></p>
        <?php if(mysqli_num_rows($obat_q) > 0): ?>
            <ul>
                <?php while ($obat = mysqli_fetch_assoc($obat_q)): ?>
                    <li><?= htmlspecialchars($obat['nama_obat']) ?> (Jumlah: <?= (int)$obat['jumlah'] ?>)</li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>Tidak ada obat yang diberikan.</p>
        <?php endif; ?>

        <br>
        <p>Demikian surat ini dibuat untuk keperluan yang bersangkutan.</p>
        <p class="text-end mt-4">Dokter Pemeriksa</p>
        <br><br>
        <p class="text-end">__________________________</p>
    </div>
    <script>window.print();</script>
</body>
</html>
