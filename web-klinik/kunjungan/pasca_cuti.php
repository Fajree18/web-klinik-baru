<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

// ===== FILTER =====
$keyword = trim($_GET['q'] ?? '');
$keyword_safe = mysqli_real_escape_string($conn, $keyword);

$where = "WHERE k.status_kunjungan = 'pasca_cuti'";
if ($keyword !== '') {
    $where .= " AND (
        p.nama LIKE '%$keyword_safe%' OR
        p.no_rm LIKE '%$keyword_safe%' OR
        p.id_pasien LIKE '%$keyword_safe%' OR
        p.departemen LIKE '%$keyword_safe%'
    )";
}

// ===== PAGINATION =====
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// total rows
$qTotal = mysqli_query($conn, "
    SELECT COUNT(*) AS total
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    $where
");
$total_rows = $qTotal ? (int)mysqli_fetch_assoc($qTotal)['total'] : 0;
$total_pages = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;

// data list
$list = mysqli_query($conn, "SELECT 
        k.id_kunjungan,
        k.tanggal_kunjungan,
        k.keterangan_followup,
        p.id_pasien,
        p.nama,
        p.no_rm,
        p.departemen,
        p.jabatan
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    $where
    ORDER BY k.tanggal_kunjungan DESC
    LIMIT $per_page OFFSET $offset
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pasien Pasca Cuti</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-size: 14px; }
        table td, table th { font-size: 13px; vertical-align: middle; }
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="container mt-4">

<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="mb-0 fw-bold">Pasien Pasca Cuti</h5>
    <a href="../dashboard.php" class="btn btn-secondary btn-sm">Kembali</a>
</div>

<form method="GET" class="row g-2 align-items-center mb-3">
    <div class="col-md-5">
        <input type="text" name="q" class="form-control"
               placeholder="Cari nama / NIK / No RM / departemen..."
               value="<?= htmlspecialchars($keyword) ?>">
    </div>
    <div class="col-auto">
        <button class="btn btn-primary">Cari</button>
        <a href="pasca_cuti.php" class="btn btn-outline-secondary">Reset</a>
    </div>
    <div class="col-auto text-muted small">
        Total: <?= (int)$total_rows ?> data
    </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th width="170">Tanggal</th>
            <th width="120">No RM</th>
            <th width="140">NIK</th>
            <th>Nama</th>
            <th width="160">Departemen</th>
            <th width="160">Jabatan</th>
            <th>Keterangan</th>
            <th width="150">Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$list): ?>
        <tr>
            <td colspan="8" class="text-center text-danger">
                Gagal memuat data: <?= htmlspecialchars(mysqli_error($conn)) ?>
            </td>
        </tr>
    <?php elseif (mysqli_num_rows($list) == 0): ?>
        <tr>
            <td colspan="8" class="text-center text-muted">Tidak ada data pasca cuti.</td>
        </tr>
    <?php else: ?>
        <?php while ($r = mysqli_fetch_assoc($list)): ?>
            <tr>
                <td><?= date('d M Y H:i', strtotime($r['tanggal_kunjungan'])) ?></td>
                <td><?= htmlspecialchars($r['no_rm']) ?></td>
                <td><?= htmlspecialchars($r['id_pasien']) ?></td>
                <td><?= htmlspecialchars($r['nama']) ?></td>
                <td><?= htmlspecialchars($r['departemen'] ?? '-') ?></td>
                <td><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
                <td>
                    <div class="text-truncate-2">
                        <?= !empty($r['keterangan_followup'])
                            ? nl2br(htmlspecialchars($r['keterangan_followup']))
                            : '-' ?>
                    </div>
                </td>
                <td class="text-nowrap">
                    <a href="edit_kunjungan.php?id_kunjungan=<?= (int)$r['id_kunjungan'] ?>" class="btn btn-warning btn-sm">Edit</a>
                    <a href="hapus_kunjungan.php?id_kunjungan=<?= (int)$r['id_kunjungan'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Hapus kunjungan pasca cuti ini?')">Hapus</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<!-- PAGINATION (maks 10 angka) -->
<nav class="mt-3">
  <ul class="pagination">
    <?php
    $base = "pasca_cuti.php?q=" . urlencode($keyword) . "&page=";

    // prev
    $prevDisabled = ($page <= 1) ? "disabled" : "";
    echo '<li class="page-item ' . $prevDisabled . '"><a class="page-link" href="' . $base . ($page-1) . '">«</a></li>';

    // window 10 halaman
    $window = 10;
    $start = max(1, $page - (int)floor($window/2));
    $end   = min($total_pages, $start + $window - 1);
    $start = max(1, $end - $window + 1);

    if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $base . '1">1</a></li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = ($i == $page) ? "active" : "";
        echo '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base . $i . '">' . $i . '</a></li>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
        echo '<li class="page-item"><a class="page-link" href="' . $base . $total_pages . '">' . $total_pages . '</a></li>';
    }

    // next
    $nextDisabled = ($page >= $total_pages) ? "disabled" : "";
    echo '<li class="page-item ' . $nextDisabled . '"><a class="page-link" href="' . $base . ($page+1) . '">»</a></li>';
    ?>
  </ul>
</nav>

</body>
</html>
