<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
include "koneksi.php";

$total_pasien = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM pasien"))['total'];
$total_obat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM obat"))['total'];
$riwayat_kunjungan = mysqli_query($conn, "SELECT k.id_kunjungan, k.tanggal_kunjungan, k.diagnosa, k.tindakan, p.nama FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien ORDER BY k.tanggal_kunjungan DESC LIMIT 5");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-dark-blue { background-color: #1e2a38; }
        .card-hover:hover { box-shadow: 0 0 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark-blue shadow-sm">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Web Klinik PT Makmur Lestari Primatama</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Pasien</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="pasien/tambah.php">Tambah Pasien</a></li>
            <li><a class="dropdown-item" href="pasien/daftar_pasien.php">Lihat Daftar Pasien</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Kunjungan</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="kunjungan/tambah.php">Input Kunjungan Pasien</a></li>
            <li><a class="dropdown-item" href="kunjungan/daftar_kunjungan.php">Edit dan Print Surat</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">History</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="kunjungan/followup.php">Input Follow-up Pasien</a></li>
            <li><a class="dropdown-item" href="kunjungan/histori_followup.php">History Pasien</a></li>
            <li><a class="dropdown-item" href="kunjungan/pasca_cuti.php">Pasien Pasca Cuti</a></li>

            
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Manajemen Obat</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="obat/tambah.php">Tambah Obat</a></li>
            <li><a class="dropdown-item" href="obat/daftar_obat.php">Daftar Obat</a></li>
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Export</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="export/eksport_obat.php" >Stok Obat Terakhir</a></li>
            <li><a class="dropdown-item" href="export/eksport_departemen.php" >Eksport Filter by Departemen</a></li>
            <li><a class="dropdown-item" href="export/eksport_tanggal.php" >Eksport Filter by Tanggal</a></li>
            <li><a class="dropdown-item" href="export/eksport_pasien.php" >Eksport Filter Lengkap</a></li>
            
          </ul>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle fw-bold text-white" href="#" role="button" data-bs-toggle="dropdown">Import</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="import/import_obat.php" >Import Stock Obat (CSV) </a></li>
          </ul>
        </li>

        <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>

          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
            <a class="nav-link fw-bold text-danger" href="logout.php">Logout</a>
            </li>
        </ul>


    </div>
  </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4 fw-bold text-primary">Dashboard Admin</h2>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card text-white bg-primary card-hover shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Jumlah Pasien</h5>
                    <h2 class="card-text"><?= $total_pasien ?> pasien</h2>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-info card-hover shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Jumlah Obat</h5>
                    <h2 class="card-text"><?= $total_obat ?> jenis</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5">
        <h5 class="fw-semibold mb-3">5 Kunjungan Terakhir</h5>
        <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover bg-white shadow-sm">
            <thead class="table-light">
                <tr>
                    <th>Pasien</th>
                    <th>Tanggal</th>
                    <th>Diagnosa</th>
                    <th>Tindakan</th>
                    <th>Obat Diberikan</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($riwayat_kunjungan)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= $row['tanggal_kunjungan'] ?></td>
                    <td><?= htmlspecialchars($row['diagnosa']) ?></td>
                    <td><?= htmlspecialchars($row['tindakan']) ?></td>
                    <td>
                        <ul class="mb-0 ps-3">
                        <?php
                        $id_kunjungan = $row['id_kunjungan'];
                        $resep = mysqli_query($conn, "SELECT o.nama_obat, r.jumlah FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat WHERE r.id_kunjungan = $id_kunjungan
                        ");
                        while ($r = mysqli_fetch_assoc($resep)) {
                            echo "<li>" . htmlspecialchars($r['nama_obat']) . " (" . $r['jumlah'] . ")</li>";
                        }
                        ?>
                        </ul>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
