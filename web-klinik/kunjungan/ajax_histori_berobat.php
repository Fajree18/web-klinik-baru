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

echo "<h5>Data Pasien</h5><ul>
<li><strong>Nama:</strong> {$pasien['nama']}</li>
<li><strong>ID Pasien (NIK):</strong> {$pasien['id_pasien']}</li>
<li><strong>No. RM:</strong> {$pasien['no_rm']}</li>
<li><strong>Jabatan:</strong> {$pasien['jabatan']}</li>
</ul>";

$kunjungan = mysqli_query($conn, "
    SELECT * FROM kunjungan 
    WHERE id_pasien = '$id_pasien' 
    ORDER BY tanggal_kunjungan DESC
");

if (mysqli_num_rows($kunjungan) == 0) {
    echo "<div class='alert alert-warning'>Belum ada histori kunjungan.</div>";
    exit;
}

echo "<h5 class='mt-4'>Riwayat Kunjungan</h5>";
echo "<div class='table-responsive'>
<table class='table table-bordered table-striped'>
<thead class='table-light'>
<tr>
<th>Tanggal</th><th>Status</th><th>Diagnosa</th><th>Tindakan</th><th>Istirahat</th><th>Obat + Dosis</th>
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
        FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat 
        WHERE r.id_kunjungan = '$id_kunjungan'
    ");
    while ($r = mysqli_fetch_assoc($resep)) {
        echo "<li>" . htmlspecialchars($r['nama_obat']) . " - " . htmlspecialchars($r['dosis']) . " (" . (int)$r['jumlah'] . ")</li>";
    }

    echo "</ul></td></tr>";
}

echo "</tbody></table></div>";