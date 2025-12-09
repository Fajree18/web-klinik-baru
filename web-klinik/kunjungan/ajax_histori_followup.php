<?php
include "../koneksi.php";

if (!isset($_POST['id_pasien'])) exit;

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);

// ====== AMBIL DATA PASIEN ======
$p = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id_pasien'");
if (mysqli_num_rows($p) == 0) {
    echo "<div class='alert alert-danger'>Pasien tidak ditemukan.</div>";
    exit;
}
$pasien = mysqli_fetch_assoc($p);

// ====== HITUNG UMUR ======
function hitungUmur($tgl) {
    if (!$tgl || $tgl == '0000-00-00') return '-';
    $lahir = new DateTime($tgl);
    $now = new DateTime();
    return $now->diff($lahir)->y . " tahun";
}
$umur = hitungUmur($pasien['tanggal_lahir']);

// ====== DATA PASIEN ======
echo '
<h5 class="mb-3 fw-bold">Data Pasien</h5>
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6"><strong>Nama:</strong><br>' . htmlspecialchars($pasien['nama']) . '</div>
      <div class="col-md-6"><strong>ID Pasien (NIK):</strong><br>' . htmlspecialchars($pasien['id_pasien']) . '</div>
      <div class="col-md-6"><strong>No. RM:</strong><br>' . htmlspecialchars($pasien['no_rm']) . '</div>
      <div class="col-md-6"><strong>Jabatan:</strong><br>' . htmlspecialchars($pasien['jabatan']) . '</div>
      <div class="col-md-6"><strong>Departemen:</strong><br>' . htmlspecialchars($pasien['departemen']) . '</div>
      <div class="col-md-6"><strong>Jenis Kelamin:</strong><br>' . htmlspecialchars($pasien['jenis_kelamin']) . '</div>
      <div class="col-md-6"><strong>Tanggal Lahir:</strong><br>' . date('d M Y', strtotime($pasien['tanggal_lahir'])) . '</div>
      <div class="col-md-6"><strong>Umur:</strong><br>' . $umur . '</div>
      <div class="col-12"><strong>Riwayat Sakit:</strong><br>' . nl2br(htmlspecialchars($pasien['riwayat_sakit'])) . '</div>
    </div>
  </div>
</div>
';

// ====== MCU MULTI FILE (HANYA TAMPIL) ======
echo "
<div class='card mt-4 shadow-sm'>
  <div class='card-header bg-light'>
    <h6 class='mb-0 fw-bold'>📋 Data MCU Pasien</h6>
  </div>
  <div class='card-body'>
";

$mcu_list = mysqli_query($conn, "SELECT * FROM pasien_mcu WHERE id_pasien='$id_pasien' ORDER BY tahun DESC");

if (mysqli_num_rows($mcu_list) > 0) {
    $totalMCU = mysqli_num_rows($mcu_list);
    echo "<div class='mb-3'>
            <span class='badge bg-primary'>Total $totalMCU File MCU</span>
          </div>";

    echo "
    <div class='table-responsive'>
      <table class='table table-bordered align-middle mb-0'>
        <thead class='table-secondary'>
          <tr class='text-center'>
            <th width='80'>Tahun</th>
            <th>File MCU</th>
            <th width='180'>Tanggal Upload</th>
          </tr>
        </thead>
        <tbody>";
    while ($m = mysqli_fetch_assoc($mcu_list)) {
        $filePath = "../uploads/mcu/" . $m['file_mcu'];
        echo "
        <tr>
          <td class='text-center'>" . htmlspecialchars($m['tahun']) . "</td>
          <td>" . 
            (file_exists($filePath)
                ? "<a href='$filePath' target='_blank' class='text-decoration-none'>📄 " . htmlspecialchars($m['file_mcu']) . "</a>"
                : "<span class='text-danger'>File tidak ditemukan</span>") . "
          </td>
          <td class='text-center'>" . date('d M Y H:i', strtotime($m['tanggal_upload'])) . "</td>
        </tr>";
    }
    echo "</tbody></table></div>";
} else {
    echo "<p class='text-muted mb-0'>Belum ada data MCU untuk pasien ini.</p>";
}

echo "</div></div>";

// ====== RIWAYAT KUNJUNGAN ======
$kunjungan = mysqli_query($conn, "
    SELECT * FROM kunjungan 
    WHERE id_pasien = '$id_pasien' 
    ORDER BY tanggal_kunjungan DESC
");

if (mysqli_num_rows($kunjungan) == 0) {
    echo "<div class='alert alert-warning mt-4'>Belum ada riwayat kunjungan.</div>";
    exit;
}

$totalKunjungan = mysqli_num_rows($kunjungan);
$jmlFollowup = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) AS jml FROM kunjungan 
    WHERE id_pasien='$id_pasien' AND status_kunjungan='followup'
"))['jml'];

if ($jmlFollowup > 0) {
    echo "<div class='alert alert-info mb-3 mt-4'>🩵 Pasien masih dalam proses follow-up.</div>";
}

echo "<p><strong>Total Kunjungan:</strong> {$totalKunjungan} kali";
if ($jmlFollowup > 0) echo " ({$jmlFollowup} Follow-Up)";
echo "</p>";

echo "<h5>Riwayat Kunjungan</h5>
<div class='table-responsive'>
<table class='table table-bordered table-striped'>
<thead class='table-light'>
<tr>
  <th>Tanggal</th>
  <th>Status</th>
  <th>Diagnosa</th>
  <th>Tindakan</th>
  <th>Istirahat</th>
  <th>Obat + Dosis</th>
</tr>
</thead><tbody>";

while ($k = mysqli_fetch_assoc($kunjungan)) {
    $warna = ($k['status_kunjungan'] == 'followup')
        ? 'bg-warning text-dark'
        : 'bg-info text-white';

    echo "<tr>
        <td>" . date('d M Y H:i', strtotime($k['tanggal_kunjungan'])) . "</td>
        <td><span class='badge $warna'>" . ucfirst($k['status_kunjungan']) . "</span></td>
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
