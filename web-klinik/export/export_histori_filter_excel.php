<?php
include "../koneksi.php";

if (ob_get_length()) ob_end_clean();

// ===================== helper cek tabel/kolom =====================
function hasTable($conn, $table) {
    $q = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    return ($q && mysqli_num_rows($q) > 0);
}
function hasColumn($conn, $table, $column) {
    $q = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($q && mysqli_num_rows($q) > 0);
}
function validDateYmd($date) {
    if (!$date) return false;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

$use_diag_join = hasTable($conn, 'diagnosa') && hasColumn($conn, 'kunjungan', 'id_diagnosa');

// ===================== ambil parameter =====================
$departemen = isset($_GET['departemen']) ? trim($_GET['departemen']) : '';
$tgl_awal   = $_GET['tanggal_awal'] ?? '';
$tgl_akhir  = $_GET['tanggal_akhir'] ?? '';

// validasi minimal harus ada salah satu filter
if ($departemen === '' && ($tgl_awal === '' || $tgl_akhir === '')) {
    die("Filter tidak valid. Isi departemen atau tanggal_awal & tanggal_akhir.");
}

// validasi tanggal kalau dipakai
$use_date_filter = false;
if ($tgl_awal !== '' || $tgl_akhir !== '') {
    if (!validDateYmd($tgl_awal) || !validDateYmd($tgl_akhir)) {
        die("Tanggal filter tidak valid. Format harus Y-m-d (contoh: 2026-01-15).");
    }
    $use_date_filter = true;
}

// escape departemen
$departemen_esc = ($departemen !== '') ? mysqli_real_escape_string($conn, $departemen) : '';

// ===================== buat kondisi WHERE =====================
$where = [];
if ($departemen !== '') {
    // pasien.departemen bisa NULL, jadi pakai = yang aman
    $where[] = "p.departemen = '$departemen_esc'";
}
if ($use_date_filter) {
    $tgl_awal_esc  = mysqli_real_escape_string($conn, $tgl_awal);
    $tgl_akhir_esc = mysqli_real_escape_string($conn, $tgl_akhir);
    $where[] = "DATE(k.tanggal_kunjungan) BETWEEN '$tgl_awal_esc' AND '$tgl_akhir_esc'";
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

// ===================== nama file =====================
$parts = [];
if ($departemen !== '') $parts[] = "Dept_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $departemen);
if ($use_date_filter) $parts[] = "{$tgl_awal}_sd_{$tgl_akhir}";
$suffix = !empty($parts) ? implode("_", $parts) : "All";

$filename = "Data_Kunjungan_{$suffix}.xls";

// ===================== header excel =====================
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// ===================== query kunjungan =====================
$sql = "SELECT
    k.id_kunjungan,
    k.tanggal_kunjungan,
    k.keluhan,
    k.diagnosa,
    k.tindakan,
    k.istirahat,
    k.status_kunjungan,
    p.nama,
    p.no_rm,
    p.departemen
FROM kunjungan k
JOIN pasien p ON p.id_pasien = k.id_pasien
{$where_sql}
ORDER BY k.tanggal_kunjungan ASC
";

if ($use_diag_join) {
    $sql = "SELECT
        k.id_kunjungan,
        k.tanggal_kunjungan,
        k.keluhan,
        k.diagnosa,
        k.id_diagnosa,
        d.nama_diagnosa,
        k.tindakan,
        k.istirahat,
        k.status_kunjungan,
        p.nama,
        p.no_rm,
        p.departemen
    FROM kunjungan k
    JOIN pasien p ON p.id_pasien = k.id_pasien
    LEFT JOIN diagnosa d ON d.id_diagnosa = k.id_diagnosa
    {$where_sql}
    ORDER BY k.tanggal_kunjungan ASC
    ";
}

$result = mysqli_query($conn, $sql);
if (!$result) {
    http_response_code(500);
    die("Query export gagal: " . mysqli_error($conn));
}

// ===================== ambil resep SEKALI (no N+1) =====================
$resepMap = []; // [id_kunjungan] => ["obat (dosis) (jumlah)", ...]
$resepSql = "SELECT r.id_kunjungan, o.nama_obat, r.dosis, r.jumlah
    FROM resep r
    JOIN obat o ON o.kode_obat = r.kode_obat
";
$resepQ = mysqli_query($conn, $resepSql);
if ($resepQ) {
    while ($r = mysqli_fetch_assoc($resepQ)) {
        $idk = (int)$r['id_kunjungan'];
        $item = ($r['nama_obat'] ?? '-') . " (" . ($r['dosis'] ?? '-') . ") x" . (int)($r['jumlah'] ?? 0);
        if (!isset($resepMap[$idk])) $resepMap[$idk] = [];
        $resepMap[$idk][] = $item;
    }
}

// ===================== output tabel =====================
echo "<table border='1'>";
echo "<tr style='background:#e0e0e0; font-weight:bold;'>
        <th>No</th>
        <th>Tanggal</th>
        <th>Jam</th>
        <th>No RM</th>
        <th>Nama Pasien</th>
        <th>Departemen</th>
        <th>Keluhan</th>
        <th>Diagnosa</th>
        <th>Tindakan</th>
        <th>Istirahat (hari)</th>
        <th>Status</th>
        <th>Obat & Dosis</th>
      </tr>";

$no = 1;
while ($row = mysqli_fetch_assoc($result)) {
    $id_kunjungan = (int)$row['id_kunjungan'];

    $tanggal = !empty($row['tanggal_kunjungan']) ? date('d-m-Y', strtotime($row['tanggal_kunjungan'])) : '-';
    $jam     = !empty($row['tanggal_kunjungan']) ? date('H:i:s', strtotime($row['tanggal_kunjungan'])) : '-';

    // diagnosa tampil: join > fallback text
    $diag_tampil = $row['diagnosa'] ?? '';
    if ($use_diag_join) {
        if (!empty($row['nama_diagnosa'])) $diag_tampil = $row['nama_diagnosa'];
        elseif (!empty($row['diagnosa'])) $diag_tampil = $row['diagnosa'];
        else $diag_tampil = '';
    }

    $resep_str = '-';
    if (isset($resepMap[$id_kunjungan]) && count($resepMap[$id_kunjungan]) > 0) {
        $resep_str = implode(", ", $resepMap[$id_kunjungan]);
    }

    echo "<tr>
            <td>{$no}</td>
            <td>" . htmlspecialchars($tanggal) . "</td>
            <td>" . htmlspecialchars($jam) . "</td>
            <td>" . htmlspecialchars($row['no_rm'] ?? '-') . "</td>
            <td>" . htmlspecialchars($row['nama'] ?? '-') . "</td>
            <td>" . htmlspecialchars($row['departemen'] ?? '-') . "</td>
            <td>" . htmlspecialchars($row['keluhan'] ?? '') . "</td>
            <td>" . htmlspecialchars($diag_tampil ?? '') . "</td>
            <td>" . htmlspecialchars($row['tindakan'] ?? '') . "</td>
            <td>" . (int)($row['istirahat'] ?? 0) . "</td>
            <td>" . htmlspecialchars(ucfirst($row['status_kunjungan'] ?? '-')) . "</td>
            <td>" . htmlspecialchars($resep_str) . "</td>
          </tr>";
    $no++;
}

echo "</table>";
exit;
?>
