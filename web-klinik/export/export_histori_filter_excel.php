<?php
include "../koneksi.php";

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Eksport by Filter.xls");

$departemen = isset($_GET['departemen']) ? mysqli_real_escape_string($conn, $_GET['departemen']) : '';
$diagnosa = isset($_GET['diagnosa']) ? mysqli_real_escape_string($conn, $_GET['diagnosa']) : '';

$where = "WHERE 1";
if ($departemen !== '') {
    $where .= " AND p.departemen = '$departemen'";
}
if ($diagnosa !== '') {
    $where .= " AND k.diagnosa LIKE '%$diagnosa%'";
}

$query = mysqli_query($conn, "SELECT k.tanggal_kunjungan, p.id_pasien, p.no_rm, p.nama, p.departemen, p.jabatan, k.diagnosa, k.tindakan, k.istirahat, k.id_kunjungan FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien $where ORDER BY k.diagnosa ASC
");

?>

<table border="1">
    <thead>
        <tr>
            <th>No RM</th>
            <th>NIK</th>
            <th>Nama Pasien</th>
            <th>Departemen</th>
            <th>Jabatan</th>
            <th>Diagnosa</th>
            <th>Tindakan</th>
            <th>Obat Diberikan</th>
            <th>Lama Istirahat (Hari)</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($query)): ?>
        <tr>
            <td><?= $row['no_rm'] ?></td>
            <td><?= $row['id_pasien'] ?></td>
            <td><?= $row['nama'] ?></td>
            <td><?= $row['departemen'] ?></td>
            <td><?= $row['jabatan'] ?></td>
            <td><?= $row['diagnosa'] ?></td>
            <td><?= $row['tindakan'] ?></td>
            <td>
                <?php
                    // Ambil obat yang diberikan berdasarkan ID kunjungan
                    $id_kunjungan = $row['id_kunjungan'];
                    $obat_q = mysqli_query($conn, "
                        SELECT o.nama_obat, r.jumlah
                        FROM resep r
                        JOIN obat o ON o.kode_obat = r.kode_obat
                        WHERE r.id_kunjungan = $id_kunjungan
                    ");
                    $obat_list = [];
                    while ($obat = mysqli_fetch_assoc($obat_q)) {
                        $obat_list[] = $obat['nama_obat'] . " (" . $obat['jumlah'] . ")";
                    }
                    echo implode(", ", $obat_list);
                ?>
            </td>
            <td><?= $row['istirahat'] ?> hari</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php
exit;
?>
