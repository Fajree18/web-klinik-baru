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
$filter_sql = '';
$filter_val = '';
if (!empty($_GET['filter_obat'])) {
    $filter_val = trim($_GET['filter_obat']);
    $filter_safe = mysqli_real_escape_string($conn, $filter_val);
    $filter_sql = "WHERE o.kode_obat LIKE '%$filter_safe%' OR o.nama_obat LIKE '%$filter_safe%'";
}

/* ================== TOTAL DATA ================== */
$result_total = mysqli_query($conn, "SELECT COUNT(*) AS total FROM obat o $filter_sql");
$total_row = 0;
if ($result_total) {
    $row_total = mysqli_fetch_assoc($result_total);
    $total_row = isset($row_total['total']) ? (int)$row_total['total'] : 0;
}
$total_pages = ($total_row > 0) ? (int)ceil($total_row / $limit) : 1;
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

/* ================== SUMMARY TOTAL (SEMUA OBAT) 30 HARI ================== */
$summary = ['masuk' => 0, 'keluar' => 0];

$qSummary = mysqli_query($conn, "SELECT tipe, COALESCE(SUM(jumlah),0) AS total
    FROM stok_log
    WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND tipe IN ('masuk','keluar')
    GROUP BY tipe
");
if ($qSummary) {
    while ($s = mysqli_fetch_assoc($qSummary)) {
        if ($s['tipe'] === 'masuk') $summary['masuk'] = (int)$s['total'];
        if ($s['tipe'] === 'keluar') $summary['keluar'] = (int)$s['total'];
    }
}

/* ================== DATA OBAT + TOTAL MASUK/KELUAR PER OBAT (30 HARI) ==================
   NOTE:
   - log30: agregasi stok_log 30 hari terakhir, per kode_obat
   - tetap ambil masuk/keluar terakhir pakai MAX(tanggal)
*/
$sql = "SELECT 
        o.kode_obat,
        o.nama_obat,
        o.satuan,
        o.stok,
        o.stok_minimum,
        o.tanggal_kadaluarsa,

        masuk.max_tanggal AS tanggal_masuk,
        keluar.max_tanggal AS tanggal_keluar,

        COALESCE(log30.total_masuk_30, 0)  AS total_masuk_30,
        COALESCE(log30.total_keluar_30, 0) AS total_keluar_30

    FROM obat o

    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal
        FROM stok_log
        WHERE tipe='masuk'
        GROUP BY kode_obat
    ) masuk ON masuk.kode_obat = o.kode_obat

    LEFT JOIN (
        SELECT kode_obat, MAX(tanggal) AS max_tanggal
        FROM stok_log
        WHERE tipe='keluar'
        GROUP BY kode_obat
    ) keluar ON keluar.kode_obat = o.kode_obat

    LEFT JOIN (
        SELECT 
            kode_obat,
            SUM(CASE WHEN tipe='masuk'  THEN jumlah ELSE 0 END) AS total_masuk_30,
            SUM(CASE WHEN tipe='keluar' THEN jumlah ELSE 0 END) AS total_keluar_30
        FROM stok_log
        WHERE tanggal >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND tipe IN ('masuk','keluar')
        GROUP BY kode_obat
    ) log30 ON log30.kode_obat = o.kode_obat

    $filter_sql

    ORDER BY o.kode_obat ASC
    LIMIT $limit OFFSET $offset
";

$query = mysqli_query($conn, $sql);
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
        .pagination .page-link { min-width: 42px; text-align:center; }
        .nowrap { white-space: nowrap; }
    </style>
</head>
<body class="container mt-4">

<h3 class="fw-bold mb-3">📋 Daftar Obat</h3>

<!-- ================== SUMMARY CARD ================== -->
<div class="row mb-4">
    <div class="col-md-6 mb-2">
        <div class="card border-success shadow-sm">
            <div class="card-body text-center">
                <div class="fw-bold text-success mb-1">Total Obat Masuk (30 Hari Terakhir)</div>
                <div class="display-6 fw-bold text-success"><?= number_format($summary['masuk']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-2">
        <div class="card border-danger shadow-sm">
            <div class="card-body text-center">
                <div class="fw-bold text-danger mb-1">Total Obat Keluar (30 Hari Terakhir)</div>
                <div class="display-6 fw-bold text-danger"><?= number_format($summary['keluar']) ?></div>
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
        <button class="btn btn-primary" type="submit">Cari</button>
        <a href="daftar_obat.php" class="btn btn-secondary">Reset</a>
    </div>
</form>

<!-- ================== TABLE ================== -->
<div class="table-responsive">
<table class="table table-bordered table-striped table-sm">
<thead class="table-light">
<tr>
    <th class="nowrap">Kode</th>
    <th>Nama</th>
    <th class="nowrap">Satuan</th>
    <th class="text-center nowrap">Stok</th>
    <th class="text-center nowrap">Min</th>
    <th class="nowrap">Kadaluarsa</th>

    <!-- kolom baru -->
    <th class="text-center nowrap">Masuk (Total 1 Bulan)</th>
    <th class="text-center nowrap">Keluar (Total 1 Bulan)</th>

    <th class="nowrap">Masuk Terakhir</th>
    <th class="nowrap">Keluar Terakhir</th>
    <th class="nowrap">Aksi</th>
</tr>
</thead>
<tbody>

<?php if (!$query || mysqli_num_rows($query) == 0): ?>
<tr>
    <td colspan="11" class="text-center text-muted">Tidak ada data</td>
</tr>
<?php else: ?>
<?php while ($d = mysqli_fetch_assoc($query)):
    $kritis = ((int)$d['stok'] <= (int)$d['stok_minimum']);
?>
<tr class="<?= $kritis ? 'stok-kritis' : 'stok-aman' ?>">
    <td class="nowrap"><?= htmlspecialchars($d['kode_obat']) ?></td>
    <td><?= htmlspecialchars($d['nama_obat']) ?></td>
    <td class="nowrap"><?= htmlspecialchars($d['satuan']) ?></td>

    <td class="fw-bold text-center nowrap"><?= (int)$d['stok'] ?></td>
    <td class="text-center nowrap"><?= (int)$d['stok_minimum'] ?></td>
    <td class="nowrap"><?= !empty($d['tanggal_kadaluarsa']) ? date('d-m-Y', strtotime($d['tanggal_kadaluarsa'])) : '-' ?></td>

    <!-- nilai kolom baru -->
    <td class="text-center fw-semibold nowrap"><?= number_format((int)$d['total_masuk_30']) ?></td>
    <td class="text-center fw-semibold nowrap"><?= number_format((int)$d['total_keluar_30']) ?></td>

    <td class="nowrap"><?= !empty($d['tanggal_masuk']) ? date('d-m-Y H:i', strtotime($d['tanggal_masuk'])) : '-' ?></td>
    <td class="nowrap"><?= !empty($d['tanggal_keluar']) ? date('d-m-Y H:i', strtotime($d['tanggal_keluar'])) : '-' ?></td>

    <td class="nowrap">
        <a href="edit_obat.php?kode_obat=<?= urlencode($d['kode_obat']) ?>" class="btn btn-warning btn-sm">Edit</a>
    </td>
</tr>
<?php endwhile; ?>
<?php endif; ?>

</tbody>
</table>
</div>

<!-- ================== PAGINATION (MAX 10 + DOTS) ================== -->
<nav class="mt-3">
    <ul class="pagination justify-content-center flex-wrap">
        <?php
        $qs_filter = '&filter_obat=' . urlencode($filter_val);

        // Prev
        $prev_disabled = ($page <= 1) ? 'disabled' : '';
        $prev_page = ($page > 1) ? $page - 1 : 1;
        echo '<li class="page-item '.$prev_disabled.'">
                <a class="page-link" href="?page='.$prev_page.$qs_filter.'">«</a>
              </li>';

        $max_links = 10;
        $half = (int)floor($max_links / 2);

        $start = max(1, $page - $half);
        $end = min($total_pages, $start + $max_links - 1);

        if (($end - $start + 1) < $max_links) {
            $start = max(1, $end - $max_links + 1);
        }

        // First + dots
        if ($start > 1) {
            echo '<li class="page-item"><a class="page-link" href="?page=1'.$qs_filter.'">1</a></li>';
            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }

        for ($p = $start; $p <= $end; $p++) {
            $active = ($p == $page) ? 'active' : '';
            echo '<li class="page-item '.$active.'">
                    <a class="page-link" href="?page='.$p.$qs_filter.'">'.$p.'</a>
                  </li>';
        }

        // Dots + last
        if ($end < $total_pages) {
            if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
            echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.$qs_filter.'">'.$total_pages.'</a></li>';
        }

        // Next
        $next_disabled = ($page >= $total_pages) ? 'disabled' : '';
        $next_page = ($page < $total_pages) ? $page + 1 : $total_pages;
        echo '<li class="page-item '.$next_disabled.'">
                <a class="page-link" href="?page='.$next_page.$qs_filter.'">»</a>
              </li>';
        ?>
    </ul>
</nav>

<a href="../dashboard.php" class="btn btn-secondary mt-3">⬅ Kembali ke Dashboard</a>

</body>
</html>
