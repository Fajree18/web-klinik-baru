<?php
include "../koneksi.php";

// Nama file saat diunduh
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"Data_Kunjungan_Pasien.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// Mulai tabel
echo "<table border='1'>
<thead>
<tr>
    <th>Tanggal Kunjungan</th>
    <th>No RM</th>
    <th>NIK</th>
    <th>Nama Pasien</th>
    <th>Departemen</th>
    <th>Jabatan</th>
    <th>Keluhan</th>
    <th>Diagnosa</th>
    <th>Tindakan</th>
    <th>Istirahat (hari)</th>
    <th>Obat Diberikan</th>
    <th>Status Kunjungan</th>
</tr>
</thead><tbody>";

$query = mysqli_query($conn, "
    SELECT p.no_rm, p.id_pasien, p.nama, p.departemen, p.jabatan,
           k.keluhan, k.diagnosa, k.tindakan, k.istirahat,
           k.tanggal_kunjungan, k.status_kunjungan, k.id_kunjungan
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    ORDER BY k.tanggal_kunjungan DESC
");

while ($row = mysqli_fetch_assoc($query)) {
    // Ambil resep
    $id_kunjungan = $row['id_kunjungan'];
    $obat_q = mysqli_query($conn, "
        SELECT o.nama_obat, r.dosis, r.jumlah
        FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat
        WHERE r.id_kunjungan = $id_kunjungan
    ");
    $obat_list = [];
    while ($o = mysqli_fetch_assoc($obat_q)) {
        $obat_list[] = "{$o['nama_obat']} {$o['dosis']} ({$o['jumlah']})";
    }
    $obat_str = implode(", ", $obat_list);

    echo "<tr>";
    echo "<td>" . date('d-m-Y', strtotime($row['tanggal_kunjungan'])) . "</td>";
    echo "<td>" . $row['no_rm'] . "</td>";
    echo "<td>" . $row['id_pasien'] . "</td>";
    echo "<td>" . $row['nama'] . "</td>";
    echo "<td>" . $row['departemen'] . "</td>";
    echo "<td>" . $row['jabatan'] . "</td>";
    echo "<td>" . $row['keluhan'] . "</td>";
    echo "<td>" . $row['diagnosa'] . "</td>";
    echo "<td>" . $row['tindakan'] . "</td>";
    echo "<td>" . $row['istirahat'] . "</td>";
    echo "<td>" . $obat_str . "</td>";
    echo "<td>" . ucfirst($row['status_kunjungan']) . "</td>";
    echo "</tr>";
}
echo "</tbody></table>";
exit;