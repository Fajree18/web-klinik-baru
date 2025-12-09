<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

// Pagination
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter pencarian
$filter = '';
$filter_val = '';
if (!empty($_GET['filter_obat'])) {
    $filter_val = mysqli_real_escape_string($conn, $_GET['filter_obat']);
    $filter = "WHERE o.kode_obat LIKE '%$filter_val%' OR o.nama_obat LIKE '%$filter_val%'";
}

// Hitung total data
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM obat o $filter");
$total_row = mysqli_fetch_assoc($result_total)['total'];
$total_pages = ceil($total_row / $limit);

// Query data obat
$query = mysqli_query($conn, "
    SELECT o.kode_obat, o.nama_obat, o.satuan, o.stok, o.stok_minimum, o.tanggal_kadaluarsa, 
           masuk.max_tanggal AS tanggal_masuk, keluar.max_tanggal AS tanggal_keluar
    FROM obat o 
    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal 
        FROM stok_log WHERE tipe = 'masuk' GROUP BY kode_obat
    ) masuk ON masuk.kode_obat = o.kode_obat
    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal 
        FROM stok_log WHERE tipe = 'keluar' GROUP BY kode_obat
    ) keluar ON keluar.kode_obat = o.kode_obat
    $filter 
    ORDER BY o.kode_obat ASC 
    LIMIT $limit OFFSET $offset
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stok-kritis {
            background-color: #ffcccc !important;
        }
        .stok-aman {
            background-color: #e9ffe9 !important;
        }
        .table td, .table th {
            vertical-align: middle;
        }
    </style>
</head>
<body class="container mt-4">

<h3 class="fw-bold mb-3">📋 Daftar Obat</h3>

<form method="GET" class="mb-3 row g-2 align-items-center">
    <div class="col-auto">
        <input type="text" name="filter_obat" class="form-control" 
               placeholder="Cari Kode atau Nama Obat..." value="<?= htmlspecialchars($filter_val) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Cari</button>
        <a href="daftar_obat.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
    <thead class="table-light">
        <tr>
            <th>Kode Obat</th>
            <th>Nama Obat</th>
            <th>Satuan</th>
            <th>Stok</th>
            <th>Stok Minimum</th>
            <th>Tanggal Kadaluarsa</th>
            <th>Tanggal Masuk Terakhir</th>
            <th>Tanggal Keluar Terakhir</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if (mysqli_num_rows($query) == 0): ?>
            <tr><td colspan="9" class="text-center text-muted">Tidak ada data obat</td></tr>
        <?php else: ?>
            <?php while ($data = mysqli_fetch_assoc($query)): 
                $kritis = ((int)$data['stok'] <= (int)$data['stok_minimum']);
                $row_class = $kritis ? 'stok-kritis' : 'stok-aman';
            ?>
            <tr class="<?= $row_class ?>">
                <td><?= htmlspecialchars($data['kode_obat']) ?></td>
                <td><?= htmlspecialchars($data['nama_obat']) ?></td>
                <td><?= htmlspecialchars($data['satuan']) ?></td>
                <td class="text-center fw-bold"><?= (int)$data['stok'] ?></td>
                <td class="text-center"><?= (int)$data['stok_minimum'] ?></td>
                <td><?= $data['tanggal_kadaluarsa'] ? date('d-m-Y', strtotime($data['tanggal_kadaluarsa'])) : '-' ?></td>
                <td><?= $data['tanggal_masuk'] ? date('d-m-Y H:i', strtotime($data['tanggal_masuk'])) : '-' ?></td>
                <td><?= $data['tanggal_keluar'] ? date('d-m-Y H:i', strtotime($data['tanggal_keluar'])) : '-' ?></td>
                <td>
                    <a href="edit_obat.php?kode_obat=<?= urlencode($data['kode_obat']) ?>" 
                       class="btn btn-sm btn-warning">Edit</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

<!-- Pagination -->
<nav aria-label="Page navigation example">
  <ul class="pagination justify-content-center">
    <?php if ($page > 1): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&filter_obat=<?= urlencode($filter_val) ?>">« Sebelumnya</a></li>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $total_pages; $p++): ?>
      <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $p ?>&filter_obat=<?= urlencode($filter_val) ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>

    <?php if ($page < $total_pages): ?>
      <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&filter_obat=<?= urlencode($filter_val) ?>">Berikutnya »</a></li>
    <?php endif; ?>
  </ul>
</nav>

<a href="../dashboard.php" class="btn btn-secondary mt-3">⬅ Kembali ke Dashboard</a>

</body>
</html>
