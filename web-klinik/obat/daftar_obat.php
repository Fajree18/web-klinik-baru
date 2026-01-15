<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

/* ================== PAGINATION ================== */
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* ================== FILTER ================== */
$filter = '';
$filter_val = '';
if (!empty($_GET['filter_obat'])) {
    $filter_val = mysqli_real_escape_string($conn, $_GET['filter_obat']);
    $filter = "WHERE o.kode_obat LIKE '%$filter_val%' 
               OR o.nama_obat LIKE '%$filter_val%'";
}

/* ================== TOTAL DATA ================== */
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM obat o $filter");
$total_row = mysqli_fetch_assoc($result_total)['total'];
$total_pages = ceil($total_row / $limit);

/* ================== SUMMARY 1 BULAN ================== */
$summary = ['masuk' => 0, 'keluar' => 0];

$qSummary = mysqli_query($conn, "SELECT tipe, SUM(jumlah) AS total
    FROM stok_log
    WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    GROUP BY tipe
");

if ($qSummary) {
    while ($s = mysqli_fetch_assoc($qSummary)) {
        if ($s['tipe'] === 'masuk') {
            $summary['masuk'] = (int)$s['total'];
        }
        if ($s['tipe'] === 'keluar') {
            $summary['keluar'] = (int)$s['total'];
        }
    }
}

/* ================== DATA OBAT ================== */
$query = mysqli_query($conn, "SELECT 
        o.kode_obat, o.nama_obat, o.satuan, o.stok, o.stok_minimum, 
        o.tanggal_kadaluarsa,
        masuk.max_tanggal AS tanggal_masuk, 
        keluar.max_tanggal AS tanggal_keluar
    FROM obat o
    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal
        FROM stok_log WHERE tipe='masuk' GROUP BY kode_obat
    ) masuk ON masuk.kode_obat = o.kode_obat
    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal
        FROM stok_log WHERE tipe='keluar' GROUP BY kode_obat
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
        .stok-kritis { background:#ffcccc !important; }
        .stok-aman { background:#e9ffe9 !important; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body class="container mt-4">

<h3 class="fw-bold mb-3">📋 Daftar Obat</h3>

<!-- ================== SUMMARY CARD ================== -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <h6 class="fw-bold text-success mb-1">
                    Total Obat Masuk (30 Hari Terakhir)
                </h6>
                <h3 class="fw-bold text-success">
                    <?= number_format($summary['masuk']) ?>
                </h3>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-danger shadow-sm">
            <div class="card-body text-center">
                <h6 class="fw-bold text-danger mb-1">
                    Total Obat Keluar (30 Hari Terakhir)
                </h6>
                <h3 class="fw-bold text-danger">
                    <?= number_format($summary['keluar']) ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<!-- ================== FILTER ================== -->
<form method="GET" class="mb-3 row g-2 align-items-center">
    <div class="col-auto">
        <input type="text" name="filter_obat" class="form-control"
               placeholder="Cari kode / nama obat..."
               value="<?= htmlspecialchars($filter_val) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Cari</button>
        <a href="daftar_obat.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<!-- ================== TABLE ================== -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead class="table-light">
<tr>
    <th>Kode</th>
    <th>Nama</th>
    <th>Satuan</th>
    <th>Stok</th>
    <th>Min</th>
    <th>Kadaluarsa</th>
    <th>Masuk Terakhir</th>
    <th>Keluar Terakhir</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>

<?php if (mysqli_num_rows($query) == 0): ?>
<tr>
    <td colspan="9" class="text-center text-muted">Tidak ada data</td>
</tr>
<?php else: ?>
<?php while ($d = mysqli_fetch_assoc($query)):
    $kritis = ((int)$d['stok'] <= (int)$d['stok_minimum']);
?>
<tr class="<?= $kritis ? 'stok-kritis' : 'stok-aman' ?>">
    <td><?= htmlspecialchars($d['kode_obat']) ?></td>
    <td><?= htmlspecialchars($d['nama_obat']) ?></td>
    <td><?= htmlspecialchars($d['satuan']) ?></td>
    <td class="fw-bold text-center"><?= (int)$d['stok'] ?></td>
    <td class="text-center"><?= (int)$d['stok_minimum'] ?></td>
    <td><?= $d['tanggal_kadaluarsa'] ? date('d-m-Y', strtotime($d['tanggal_kadaluarsa'])) : '-' ?></td>
    <td><?= $d['tanggal_masuk'] ? date('d-m-Y H:i', strtotime($d['tanggal_masuk'])) : '-' ?></td>
    <td><?= $d['tanggal_keluar'] ? date('d-m-Y H:i', strtotime($d['tanggal_keluar'])) : '-' ?></td>
    <td>
        <a href="edit_obat.php?kode_obat=<?= urlencode($d['kode_obat']) ?>"
           class="btn btn-warning btn-sm">Edit</a>
    </td>
</tr>
<?php endwhile; ?>
<?php endif; ?>

</tbody>
</table>
</div>

<!-- ================== PAGINATION (MAX 10) ================== -->
<nav>
<ul class="pagination justify-content-center">

<?php
$max_links = 10;
$half = floor($max_links / 2);

$start = max(1, $page - $half);
$end = min($total_pages, $start + $max_links - 1);

if ($end - $start + 1 < $max_links) {
    $start = max(1, $end - $max_links + 1);
}

if ($page > 1) {
    echo '<li class="page-item">
            <a class="page-link" href="?page='.($page-1).'&filter_obat='.urlencode($filter_val).'">« Sebelumnya</a>
          </li>';
}

for ($p = $start; $p <= $end; $p++) {
    $active = ($p == $page) ? 'active' : '';
    echo '<li class="page-item '.$active.'">
            <a class="page-link" href="?page='.$p.'&filter_obat='.urlencode($filter_val).'">'.$p.'</a>
          </li>';
}

if ($page < $total_pages) {
    echo '<li class="page-item">
            <a class="page-link" href="?page='.($page+1).'&filter_obat='.urlencode($filter_val).'">Berikutnya »</a>
          </li>';
}
?>

</ul>
</nav>

<a href="../dashboard.php" class="btn btn-secondary mt-3">⬅ Kembali ke Dashboard</a>

</body>
</html>
