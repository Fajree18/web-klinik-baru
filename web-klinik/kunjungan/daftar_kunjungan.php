<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

// Sorting
$allowed_sorts = [
    'tanggal'   => 'k.tanggal_kunjungan',
    'nama'      => 'p.nama',
    'no_rm'     => 'p.no_rm',
    'status'    => 'k.status_kunjungan',
    'istirahat' => 'k.istirahat'
];
$sort_key = $_GET['sort_by'] ?? 'tanggal';
$sort_column = $allowed_sorts[$sort_key] ?? $allowed_sorts['tanggal'];

// Pagination
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $per_page;

// Hitung total data
$count_result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM kunjungan");
if (!$count_result) {
    die("Query count gagal: " . mysqli_error($conn));
}
$total_rows = (int)mysqli_fetch_assoc($count_result)['total'];
$total_pages = (int)ceil($total_rows / $per_page);
if ($total_pages < 1) $total_pages = 1;

// Guard page biar gak lewat total_pages
if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

// Query data dengan limit dan offset
$kunjungan = mysqli_query($conn, "SELECT 
        k.id_kunjungan, k.tanggal_kunjungan, k.keluhan, k.diagnosa, 
        k.tindakan, k.istirahat, k.status_kunjungan, p.nama, p.no_rm
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    ORDER BY $sort_column DESC
    LIMIT $per_page OFFSET $offset
");
if (!$kunjungan) {
    die("Query kunjungan gagal: " . mysqli_error($conn));
}

// Helper buat bikin URL pagination yang tetap bawa sort_by
function pageUrl($pageNum, $sort_key) {
    return "?page=" . urlencode($pageNum) . "&sort_by=" . urlencode($sort_key);
}
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
        .pagination { flex-wrap: wrap; gap: 2px; }
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
        <?php if (mysqli_num_rows($kunjungan) == 0): ?>
            <tr>
                <td colspan="10" class="text-center text-muted">Belum ada data kunjungan.</td>
            </tr>
        <?php endif; ?>

        <?php while ($row = mysqli_fetch_assoc($kunjungan)): ?>
        <?php $id_kunjungan = (int)$row['id_kunjungan']; ?>
        <tr>
            <td>
                <?php
                // biar tampil rapi: 2026-01-14 00:24:28 -> 14 Jan 2026 00:24
                $tgl = $row['tanggal_kunjungan'];
                echo htmlspecialchars(date('d M Y H:i', strtotime($tgl)));
                ?>
            </td>
            <td><?= htmlspecialchars($row['no_rm']) ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><?= htmlspecialchars($row['keluhan']) ?></td>
            <td><?= htmlspecialchars($row['diagnosa']) ?></td>
            <td><?= htmlspecialchars($row['tindakan']) ?></td>
            <td><?= (int)$row['istirahat'] ?> hr</td>
            <td>
                <?php
                $items = [];
                $resep = mysqli_query($conn, "SELECT o.nama_obat, r.dosis, r.jumlah
                    FROM resep r
                    JOIN obat o ON o.kode_obat = r.kode_obat
                    WHERE r.id_kunjungan = '$id_kunjungan'
                ");
                if ($resep) {
                    while ($r = mysqli_fetch_assoc($resep)) {
                        $items[] = htmlspecialchars($r['nama_obat']) . " (" . htmlspecialchars($r['dosis']) . ")";
                    }
                }
                echo !empty($items) ? implode("<br>", $items) : "<span class='text-muted'>-</span>";
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

<!-- Pagination: tampil maksimal 10 nomor -->
<?php
$max_links = 10;
$half = (int)floor($max_links / 2);

$start = max(1, $page - $half);
$end   = min($total_pages, $start + $max_links - 1);

// kalau jumlah link kurang dari max_links, geser start ke kiri
if (($end - $start + 1) < $max_links) {
    $start = max(1, $end - $max_links + 1);
}

$prev_page = max(1, $page - 1);
$next_page = min($total_pages, $page + 1);
?>

<nav aria-label="Pagination" class="mt-3">
    <ul class="pagination">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page <= 1 ? '#' : pageUrl($prev_page, $sort_key) ?>">« Sebelumnya</a>
        </li>

        <?php for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                <a class="page-link" href="<?= pageUrl($i, $sort_key) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="<?= $page >= $total_pages ? '#' : pageUrl($next_page, $sort_key) ?>">Berikutnya »</a>
        </li>
    </ul>
</nav>

<a href="../dashboard.php" class="btn btn-secondary btn-sm mt-3">Kembali ke Dashboard</a>

</body>
</html>
