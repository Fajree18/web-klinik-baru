<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

// fungsi bantu hitung umur
function hitungUmur($tgl_lahir) {
    if (!$tgl_lahir || $tgl_lahir === '0000-00-00') return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $now = new DateTime();
        $diff = $now->diff($lahir);
        return $diff->y . " tahun";
    } catch (Exception $e) {
        return '-';
    }
}

// --- Pagination ---
$per_page = 20; // batas 20 data per halaman
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// hitung total pasien
$total_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pasien");
$total_rows = mysqli_fetch_assoc($total_result)['total'];
$total_pages = ceil($total_rows / $per_page);

// ambil data pasien per halaman
$data = mysqli_query($conn, "
    SELECT * FROM pasien 
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table td, .table th { vertical-align: middle; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body class="container mt-4">

<h4 class="mb-4">Daftar Pasien Terdaftar</h4>

<div class="table-responsive">
<table class="table table-bordered table-striped">
    <thead class="table-light">
        <tr>
            <th class="nowrap">NIK</th>
            <th class="nowrap">No. RM</th>
            <th>Nama</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th class="nowrap">Tgl Lahir</th>
            <th>Umur</th>
            <th class="nowrap">JK</th>
            <th>Telepon</th>
            <th>Alamat</th>
            <th class="nowrap">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($data) === 0): ?>
            <tr><td colspan="11" class="text-center text-muted">Tidak ada data pasien</td></tr>
        <?php else: ?>
            <?php while ($row = mysqli_fetch_assoc($data)): ?>
            <tr>
                <td><?= htmlspecialchars($row['id_pasien']) ?></td>
                <td><?= htmlspecialchars($row['no_rm']) ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['departemen']) ?></td>
                <td><?= htmlspecialchars($row['jabatan']) ?></td>
                <td><?= htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_lahir']))) ?></td>
                <td><?= hitungUmur($row['tanggal_lahir']) ?></td>
                <td><?= htmlspecialchars($row['jenis_kelamin']) ?></td>
                <td><?= htmlspecialchars($row['telepon']) ?></td>
                <td><?= htmlspecialchars($row['alamat']) ?></td>
                <td class="text-nowrap">
                    <a href="edit_pasien.php?id=<?= urlencode($row['id_pasien']) ?>" class="btn btn-sm btn-warning mb-1">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<nav aria-label="Pagination" class="mt-3">
    <ul class="pagination">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>">« Sebelumnya</a>
        </li>
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>">Berikutnya »</a>
        </li>
    </ul>
</nav>

<a href="../dashboard.php" class="btn btn-secondary mt-3">⬅ Kembali ke Dashboard</a>
</body>
</html>
