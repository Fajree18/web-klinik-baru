<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

// Fungsi bantu hitung umur
function hitungUmur($tgl_lahir) {
    if (!$tgl_lahir || $tgl_lahir === '0000-00-00') return '-';
    try {
        $lahir = new DateTime($tgl_lahir);
        $now = new DateTime();
        return $now->diff($lahir)->y . " tahun";
    } catch (Exception $e) {
        return '-';
    }
}

// === Pagination setup ===
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $per_page;

// === Filter Pencarian (jika ada) ===
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';
$where = $keyword ? "
    WHERE nama LIKE '%$keyword%'
       OR id_pasien LIKE '%$keyword%'
       OR departemen LIKE '%$keyword%'
       OR jabatan LIKE '%$keyword%'
" : "";

// Hitung total data
$total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pasien $where");
$total_rows = mysqli_fetch_assoc($total_query)['total'];
$total_pages = ceil($total_rows / $per_page);

// Ambil data pasien sesuai halaman & pencarian
$data = mysqli_query($conn, "
    SELECT * FROM pasien 
    $where
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Daftar Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        .table td, .table th { vertical-align: middle; }
        .nowrap { white-space: nowrap; }
        #search { max-width: 400px; }
        .pagination { justify-content: center; }
    </style>
</head>
<body class="container mt-4">

<h4 class="mb-4 fw-bold">Daftar Pasien Terdaftar</h4>

<!-- 🔍 Kolom Pencarian -->
<form method="GET" class="mb-3 d-flex align-items-center gap-2">
    <input type="text" name="keyword" id="search" class="form-control" placeholder="🔍 Cari nama, NIK, jabatan, atau departemen..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Cari</button>
    <?php if ($keyword): ?>
        <a href="daftar_pasien.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Reset
        </a>
    <?php endif; ?>
</form>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th class="nowrap">#</th>
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
            <tr><td colspan="12" class="text-center text-muted">Tidak ada data pasien</td></tr>
        <?php else: ?>
            <?php 
            $no = $offset + 1;
            while ($row = mysqli_fetch_assoc($data)): ?>
                <tr>
                    <td><?= $no++ ?></td>
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
                        <a href="edit_pasien.php?id=<?= urlencode($row['id_pasien']) ?>" class="btn btn-sm btn-warning mb-1">
                            <i class="bi bi-pencil-square"></i> Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php endif; ?>
    </tbody>
</table>
</div>

<!-- 🔢 Pagination -->
<?php if ($total_pages > 1): ?>
<nav aria-label="Page navigation" class="mt-3">
    <ul class="pagination">
        <!-- Tombol Sebelumnya -->
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>">« Sebelumnya</a>
        </li>

        <!-- Nomor Halaman -->
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        for ($i = $start_page; $i <= $end_page; $i++): ?>
            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <!-- Tombol Berikutnya -->
        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
            <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>">Berikutnya »</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<div class="mt-3">
    <a href="../dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard
    </a>
</div>

</body>
</html>
