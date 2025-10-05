<?php
include "../koneksi.php";


$tgl_awal = $_GET['tanggal_awal'] ?? '';
$tgl_akhir = $_GET['tanggal_akhir'] ?? '';

if (!$tgl_awal || !$tgl_akhir) {
    die("Tanggal filter tidak valid.");
}


$filename = "Data_Kunjungan_{$tgl_awal}_sd_{$tgl_akhir}.xls";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

$query = "SELECT 
        k.id_kunjungan,
        k.tanggal_kunjungan,
        k.keluhan,
        k.diagnosa,
        k.tindakan,
        k.istirahat,
        k.status_kunjungan,
        p.nama,
        p.no_rm
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    WHERE DATE(k.tanggal_kunjungan) BETWEEN '$tgl_awal' AND '$tgl_akhir'
    ORDER BY k.tanggal_kunjungan ASC
";

$result = mysqli_query($conn, $query);

echo "<table border='1'>";
echo "<tr style='background:#e0e0e0; font-weight:bold;'>
        <th>No</th>
        <th>Tanggal</th>
        <th>Jam</th>
        <th>No RM</th>
        <th>Nama Pasien</th>
        <th>Keluhan</th>
        <th>Diagnosa</th>
        <th>Tindakan</th>
        <th>Istirahat (hari)</th>
        <th>Status</th>
        <th>Obat & Dosis</th>
      </tr>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $id_kunjungan = $row['id_kunjungan'];

 
    $tanggal = date('d-m-Y', strtotime($row['tanggal_kunjungan']));
    $jam = date('H:i:s', strtotime($row['tanggal_kunjungan']));

 
    $resep_query = mysqli_query($conn, "SELECT o.nama_obat, r.dosis, r.jumlah
        FROM resep r
        JOIN obat o ON o.kode_obat = r.kode_obat
        WHERE r.id_kunjungan = '$id_kunjungan'
    ");

    $resep_list = [];
    while ($r = mysqli_fetch_assoc($resep_query)) {
        $resep_list[] = htmlspecialchars($r['nama_obat']) . " (" . htmlspecialchars($r['dosis']) . ")";
    }
    $resep_str = implode(", ", $resep_list);

    echo "<tr>
            <td>{$no}</td>
            <td>{$tanggal}</td>
            <td>{$jam}</td>
            <td>" . htmlspecialchars($row['no_rm']) . "</td>
            <td>" . htmlspecialchars($row['nama']) . "</td>
            <td>" . htmlspecialchars($row['keluhan']) . "</td>
            <td>" . htmlspecialchars($row['diagnosa']) . "</td>
            <td>" . htmlspecialchars($row['tindakan']) . "</td>
            <td>" . (int)$row['istirahat'] . "</td>
            <td>" . ucfirst(htmlspecialchars($row['status_kunjungan'])) . "</td>
            <td>{$resep_str}</td>
          </tr>";
    $no++;
}
echo "</table>";
?>
