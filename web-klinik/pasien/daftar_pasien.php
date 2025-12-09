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
        $diff = $now->diff($lahir);
        return $diff->y . " tahun";
    } catch (Exception $e) {
        return '-';
    }
}

// === Handler AJAX Pencarian ===
if (isset($_POST['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_POST['keyword']);
    $data = mysqli_query($conn, "
        SELECT * FROM pasien
        WHERE nama LIKE '%$keyword%' 
           OR id_pasien LIKE '%$keyword%' 
           OR departemen LIKE '%$keyword%' 
           OR jabatan LIKE '%$keyword%'
        ORDER BY created_at DESC
        LIMIT 100
    ");

    if (mysqli_num_rows($data) === 0) {
        echo "<tr><td colspan='11' class='text-center text-muted'>Tidak ada hasil pencarian</td></tr>";
    } else {
        while ($row = mysqli_fetch_assoc($data)) {
            echo "<tr>
                <td>".htmlspecialchars($row['id_pasien'])."</td>
                <td>".htmlspecialchars($row['no_rm'])."</td>
                <td>".htmlspecialchars($row['nama'])."</td>
                <td>".htmlspecialchars($row['departemen'])."</td>
                <td>".htmlspecialchars($row['jabatan'])."</td>
                <td>".htmlspecialchars(date('d-m-Y', strtotime($row['tanggal_lahir'])))."</td>
                <td>".hitungUmur($row['tanggal_lahir'])."</td>
                <td>".htmlspecialchars($row['jenis_kelamin'])."</td>
                <td>".htmlspecialchars($row['telepon'])."</td>
                <td>".htmlspecialchars($row['alamat'])."</td>
                <td class='text-nowrap'>
                    <a href='edit_pasien.php?id=".urlencode($row['id_pasien'])."' class='btn btn-sm btn-warning mb-1'>Edit</a>
                </td>
            </tr>";
        }
    }
    exit;
}

// === Load awal (tanpa pencarian) ===
$data = mysqli_query($conn, "SELECT * FROM pasien ORDER BY created_at DESC LIMIT 100");
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
    </style>
</head>
<body class="container mt-4">

<h4 class="mb-4 fw-bold">Daftar Pasien Terdaftar</h4>

<!-- 🔍 Kolom Pencarian -->
<div class="mb-3 d-flex align-items-center gap-2">
    <input type="text" id="search" class="form-control" placeholder="🔍 Cari nama, NIK, jabatan, atau departemen..." autocomplete="off">
    <button class="btn btn-outline-secondary" type="button" onclick="$('#search').val(''); muatDataAwal();">
        <i class="bi bi-x-circle"></i> Reset
    </button>
</div>

<div class="table-responsive">
<table class="table table-bordered table-striped align-middle">
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
    <tbody id="hasil_tabel">
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

<div class="mt-3">
    <a href="../dashboard.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle"></i> Kembali ke Dashboard
    </a>
</div>

<script>
$(document).ready(function(){
    // Realtime search AJAX
    $("#search").on("input", function() {
        const keyword = $(this).val().trim();
        if (keyword.length > 0) {
            $.ajax({
                url: "daftar_pasien.php",
                type: "POST",
                data: { keyword: keyword },
                success: function(data) {
                    $("#hasil_tabel").html(data);
                }
            });
        } else {
            muatDataAwal();
        }
    });
});

function muatDataAwal() {
    $.ajax({
        url: "daftar_pasien.php",
        type: "POST",
        data: { keyword: "" },
        success: function(data) {
            $("#hasil_tabel").html(data);
        }
    });
}
</script>

</body>
</html>
