<?php
include "../koneksi.php";

if (ob_get_length()) ob_end_clean();

// ====== helper cek tabel/kolom (biar future-proof diagnosa) ======
function hasTable($conn, $table) {
    $q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($q && mysqli_num_rows($q) > 0);
}
function hasColumn($conn, $table, $column) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($q && mysqli_num_rows($q) > 0);
}

$use_diag_join = hasTable($conn, 'diagnosa') && hasColumn($conn, 'kunjungan', 'id_diagnosa');

// Nama file saat diunduh
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"Data_Kunjungan_Pasien.xls\"");
header("Pragma: no-cache");
header("Expires: 0");

// ====== ambil semua kunjungan + pasien (diagnosa join kalau ada) ======
$sql = "SELECT 
        k.id_kunjungan,
        k.tanggal_kunjungan,
        k.status_kunjungan,
        k.keluhan,
        k.diagnosa,
        k.tindakan,
        k.istirahat,
        p.no_rm,
        p.id_pasien,
        p.nama,
        p.departemen,
        p.jabatan
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    ORDER BY k.tanggal_kunjungan DESC
";

if ($use_diag_join) {
    $sql = "SELECT 
            k.id_kunjungan,
            k.tanggal_kunjungan,
            k.status_kunjungan,
            k.keluhan,
            k.diagnosa,
            k.tindakan,
            k.istirahat,
            k.id_diagnosa,
            d.nama_diagnosa,
            p.no_rm,
            p.id_pasien,
            p.nama,
            p.departemen,
            p.jabatan
        FROM kunjungan k
        JOIN pasien p ON p.id_pasien = k.id_pasien
        LEFT JOIN diagnosa d ON d.id_diagnosa = k.id_diagnosa
        ORDER BY k.tanggal_kunjungan DESC
    ";
}

$query = mysqli_query($conn, $sql);
if (!$query) {
    http_response_code(500);
    die("Query export kunjungan gagal: " . mysqli_error($conn));
}

// ====== ambil semua resep sekaligus (biar cepat) ======
$resepMap = []; // [id_kunjungan] => "obat, obat, ..."
$resepQ = mysqli_query($conn, "
    SELECT r.id_kunjungan, o.nama_obat, r.dosis, r.jumlah
    FROM resep r
    JOIN obat o ON o.kode_obat = r.kode_obat
    ORDER BY r.id_kunjungan ASC
");
if ($resepQ) {
    while ($r = mysqli_fetch_assoc($resepQ)) {
        $idk = (int)$r['id_kunjungan'];
        $item = ($r['nama_obat'] ?? '-') . " " . ($r['dosis'] ?? '-') . " (" . (int)($r['jumlah'] ?? 0) . ")";
        if (!isset($resepMap[$idk])) $resepMap[$idk] = [];
        $resepMap[$idk][] = $item;
    }
}

// ====== mulai output tabel ======
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

while ($row = mysqli_fetch_assoc($query)) {

    $id_kunjungan = (int)$row['id_kunjungan'];

    // Diagnosa tampil: join > fallback diagnosa text
    $diag_tampil = $row['diagnosa'] ?? '';
    if ($use_diag_join) {
        if (!empty($row['nama_diagnosa'])) {
            $diag_tampil = $row['nama_diagnosa'];
        } elseif (!empty($row['diagnosa'])) {
            $diag_tampil = $row['diagnosa'];
        } else {
            $diag_tampil = '';
        }
    }

    // Obat string
    $obat_str = '-';
    if (isset($resepMap[$id_kunjungan]) && count($resepMap[$id_kunjungan]) > 0) {
        $obat_str = implode(", ", $resepMap[$id_kunjungan]);
    }

    // Format tanggal
    $tgl = !empty($row['tanggal_kunjungan'])
        ? date('d-m-Y H:i', strtotime($row['tanggal_kunjungan']))
        : '-';

    echo "<tr>";
    echo "<td>" . htmlspecialchars($tgl) . "</td>";
    echo "<td>" . htmlspecialchars($row['no_rm'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['id_pasien'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['nama'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['departemen'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['jabatan'] ?? '-') . "</td>";
    echo "<td>" . htmlspecialchars($row['keluhan'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($diag_tampil ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['tindakan'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars((string)($row['istirahat'] ?? 0)) . "</td>";
    echo "<td>" . htmlspecialchars($obat_str) . "</td>";
    echo "<td>" . htmlspecialchars(ucfirst($row['status_kunjungan'] ?? '-')) . "</td>";
    echo "</tr>";
}

echo "</tbody></table>";
exit;
?>
