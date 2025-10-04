<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

// Sorting
$allowed_sorts = [
    'tanggal' => 'k.tanggal_kunjungan',
    'nama' => 'p.nama',
    'no_rm' => 'p.no_rm',
    'status' => 'k.status_kunjungan',
    'istirahat' => 'k.istirahat'
];
$sort_key = $_GET['sort_by'] ?? 'tanggal';
$sort_column = $allowed_sorts[$sort_key] ?? $allowed_sorts['tanggal'];

$kunjungan = mysqli_query($conn, "SELECT 
        k.id_kunjungan,k.tanggal_kunjungan,k.keluhan,k.diagnosa,k.tindakan,k.istirahat,k.status_kunjungan,p.nama,p.no_rm
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    ORDER BY $sort_column DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Kunjungan Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        table tr td, table tr th {
            font-size: 13px;
            vertical-align: middle;
            padding: 6px 8px !important;
        }
        .table-compact th { white-space: nowrap; }
        .badge-status { font-size: 12px; padding: 4px 8px; }
        .btn-sm { font-size: 12px; padding: 4px 8px; }
    </style>
</head>
<body class="container mt-4">

<h5 class="mb-3">Daftar Kunjungan Pasien</h5>

<form method="GET" class="mb-3 d-flex align-items-center gap-2">
    <label for="sort_by" class="form-label mb-0">Urutkan Berdasarkan:</label>
    <select name="sort_by" id="sort_by" class="form-select w-auto" onchange="this.form.submit()">
        <option value="tanggal" <?= $sort_key == 'tanggal' ? 'selected' : '' ?>>Tanggal Kunjungan</option>
        <option value="nama" <?= $sort_key == 'nama' ? 'selected' : '' ?>>Nama Pasien</option>
        <option value="no_rm" <?= $sort_key == 'no_rm' ? 'selected' : '' ?>>No. RM</option>
        <option value="status" <?= $sort_key == 'status' ? 'selected' : '' ?>>Status Kunjungan</option>
        <option value="istirahat" <?= $sort_key == 'istirahat' ? 'selected' : '' ?>>Istirahat (hari)</option>
    </select>
</form>

<table class="table table-bordered table-striped table-compact">
    <thead class="table-light">
        <tr>
            <th>Tgl</th>
            <th>No RM</th>
            <th>Nama</th>
            <th>Keluhan</th>
            <th>Diagnosa</th>
            <th>Tindakan</th>
            <th>Istirahat</th>
            <th>Obat</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($kunjungan)): ?>
        <tr>
            <td><?= htmlspecialchars($row['tanggal_kunjungan']) ?></td>
            <td><?= htmlspecialchars($row['no_rm']) ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['keluhan']) ?></td>
            <td><?= htmlspecialchars($row['diagnosa']) ?></td>
            <td><?= htmlspecialchars($row['tindakan']) ?></td>
            <td><?= (int)$row['istirahat'] ?> hr</td>
            <td>
                <?php
                $id_kunjungan = $row['id_kunjungan'];
                $items = [];
                $resep = mysqli_query($conn, "
                    SELECT o.nama_obat, r.dosis, r.jumlah 
                    FROM resep r 
                    JOIN obat o ON o.kode_obat = r.kode_obat 
                    WHERE r.id_kunjungan = '$id_kunjungan'
                ");
                while ($r = mysqli_fetch_assoc($resep)) {
                    $items[] = htmlspecialchars($r['nama_obat']) . " (" . htmlspecialchars($r['dosis']) . ")";
                }
                echo implode("<br>", $items);
                ?>
            </td>
            <td><span class="badge bg-info badge-status"><?= ucfirst($row['status_kunjungan']) ?></span></td>
            <td class="text-nowrap">
                <a href="edit_kunjungan.php?id_kunjungan=<?= $id_kunjungan ?>" class="btn btn-warning btn-sm mb-1">Edit</a>
                <a href="hapus_kunjungan.php?id_kunjungan=<?= $id_kunjungan ?>" class="btn btn-danger btn-sm mb-1" onclick="return confirm('Hapus kunjungan ini?')">Hapus</a>
                <a href="print_rujukan.php?id_kunjungan=<?= $id_kunjungan ?>" target="_blank" class="btn btn-primary btn-sm">Print</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<a href="../dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali ke Dashboard</a>

</body>
</html>