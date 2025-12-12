<?php
include "../koneksi.php";

if (!isset($_POST['id_pasien'])) {
    exit;
}

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
$p = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id_pasien'");
if (mysqli_num_rows($p) == 0) {
    echo "<div class='alert alert-danger'>Pasien tidak ditemukan.</div>";
    exit;
}
$pasien = mysqli_fetch_assoc($p);

// hitung umur
$umur = '-';
if (!empty($pasien['tanggal_lahir']) && $pasien['tanggal_lahir'] != '0000-00-00') {
    $tgl = new DateTime($pasien['tanggal_lahir']);
    $now = new DateTime();
    $umur = $now->diff($tgl)->y . " tahun";
}

echo '
<h5 class="mb-3">Data Pasien</h5>
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <strong>Nama:</strong><br>' . htmlspecialchars($pasien['nama']) . '
      </div>
      <div class="col-md-6">
        <strong>ID Pasien (NIK):</strong><br>' . htmlspecialchars($pasien['id_pasien']) . '
      </div>
      <div class="col-md-6">
        <strong>No. RM:</strong><br>' . htmlspecialchars($pasien['no_rm']) . '
      </div>
      <div class="col-md-6">
        <strong>Jabatan:</strong><br>' . htmlspecialchars($pasien['jabatan']) . '
      </div>
      <div class="col-md-6">
        <strong>Departemen:</strong><br>' . htmlspecialchars($pasien['departemen'] ?? '-') . '
      </div>
      <div class="col-md-6">
        <strong>Jenis Kelamin:</strong><br>' . htmlspecialchars($pasien['jenis_kelamin']) . '
      </div>
      <div class="col-md-6">
        <strong>Tanggal Lahir:</strong><br>' . 
        (!empty($pasien['tanggal_lahir']) && $pasien['tanggal_lahir'] != '0000-00-00'
          ? date('d M Y', strtotime($pasien['tanggal_lahir']))
          : '-') . '
      </div>
      <div class="col-md-6">
        <strong>Umur:</strong><br>' . $umur . '
      </div>
      <div class="col-md-6">
        <strong>No. Telepon:</strong><br>' . 
        (!empty($pasien['telepon']) ? htmlspecialchars($pasien['telepon']) : '-') . '
      </div>
      <div class="col-12">
        <strong>Riwayat Sakit:</strong><br>' . 
        (!empty($pasien['riwayat_sakit']) ? nl2br(htmlspecialchars($pasien['riwayat_sakit'])) : '-') . '
      </div>
    </div>
  </div>
</div>
';

// ======================== RIWAYAT KUNJUNGAN =============================
$kunjungan = mysqli_query($conn, "
    SELECT * FROM kunjungan 
    WHERE id_pasien = '$id_pasien' 
    ORDER BY tanggal_kunjungan DESC
");

if (mysqli_num_rows($kunjungan) == 0) {
    echo "<div class='alert alert-warning'>Belum ada histori kunjungan.</div>";
    exit;
}

echo "<h5 class='mt-4'>Riwayat Kunjungan</h5>
<div class='table-responsive'>
<table class='table table-bordered table-striped align-middle'>
<thead class='table-light'>
<tr>
    <th>Tanggal</th>
    <th>Status</th>
    <th>Diagnosa</th>
    <th>Tindakan</th>
    <th>Istirahat</th>
    <th>Obat + Dosis</th>
</tr>
</thead>
<tbody>";

while ($k = mysqli_fetch_assoc($kunjungan)) {
    echo "<tr>
        <td>" . date('d M Y H:i', strtotime($k['tanggal_kunjungan'])) . "</td>
        <td><span class='badge bg-info'>" . ucfirst($k['status_kunjungan']) . "</span></td>
        <td>" . htmlspecialchars($k['diagnosa']) . "</td>
        <td>" . htmlspecialchars($k['tindakan']) . "</td>
        <td>" . (int)$k['istirahat'] . " hari</td>
        <td><ul>";

    $id_kunjungan = $k['id_kunjungan'];
    $resep = mysqli_query($conn, "
        SELECT o.nama_obat, r.dosis, r.jumlah 
        FROM resep r 
        JOIN obat o ON o.kode_obat = r.kode_obat 
        WHERE r.id_kunjungan = '$id_kunjungan'
    ");
    while ($r = mysqli_fetch_assoc($resep)) {
        echo "<li>" . htmlspecialchars($r['nama_obat']) . " - " . htmlspecialchars($r['dosis']) . " (" . (int)$r['jumlah'] . ")</li>";
    }

    echo "</ul></td></tr>";
}

echo "</tbody></table></div>";
?>
