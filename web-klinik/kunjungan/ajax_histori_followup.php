<?php
include "../koneksi.php";

if (isset($_POST['id_pasien'])) {
    $id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);

    // Cari data pasien
    $q = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id_pasien'");
    if (mysqli_num_rows($q) == 0) {
        echo "<div class='alert alert-danger'>Pasien tidak ditemukan.</div>";
        exit;
    }

    $pasien = mysqli_fetch_assoc($q);

    echo "<h5>Data Pasien</h5>";
    echo "<ul>
        <li><strong>Nama:</strong> {$pasien['nama']}</li>
        <li><strong>NIK (id_pasien):</strong> {$pasien['id_pasien']}</li>
        <li><strong>No. RM:</strong> {$pasien['no_rm']}</li>
        <li><strong>Jabatan:</strong> {$pasien['jabatan']}</li>
    </ul>";

    // Cari kunjungan pertama terakhir
    $q_awal = mysqli_query($conn, "SELECT * FROM kunjungan 
        WHERE id_pasien = '$id_pasien' AND status_kunjungan = 'pertama'
        ORDER BY tanggal_kunjungan DESC LIMIT 1");

    if (mysqli_num_rows($q_awal) == 0) {
        echo "<div class='alert alert-warning'>Pasien belum memiliki kunjungan pertama.</div>";
        exit;
    }

    $kunjungan_awal = mysqli_fetch_assoc($q_awal);
    $id_awal = $kunjungan_awal['id_kunjungan'];

    // Cek apakah sudah ada follow-up dengan diagnosa mengandung kata "sembuh"
    $cek_sembuh = mysqli_query($conn, "SELECT * FROM kunjungan 
        WHERE id_kunjungan_awal = '$id_awal' AND status_kunjungan = 'followup' 
        AND diagnosa LIKE '%sembuh%'");

    $sudah_sembuh = mysqli_num_rows($cek_sembuh) > 0;

    if ($sudah_sembuh) {
        echo "<div class='alert alert-success'>✅ Pasien sudah dinyatakan sembuh.</div>";
    } else {
        echo "<div class='alert alert-info'>🩺 Pasien masih dalam proses follow-up.</div>";
    }

    echo "<h5 class='mt-4'>Riwayat Kunjungan</h5>";
    echo "<table class='table table-bordered'>
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

    // Kunjungan pertama
    echo "<tr>
        <td>" . htmlspecialchars($kunjungan_awal['tanggal_kunjungan']) . "</td>
        <td><strong>Pertama</strong></td>
        <td>" . htmlspecialchars($kunjungan_awal['diagnosa']) . "</td>
        <td>" . htmlspecialchars($kunjungan_awal['tindakan']) . "</td>
        <td>" . (int)$kunjungan_awal['istirahat'] . " hari</td>
        <td>";

    $resep_awal = mysqli_query($conn, "SELECT o.nama_obat, r.dosis, r.jumlah 
        FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat 
        WHERE r.id_kunjungan = '{$kunjungan_awal['id_kunjungan']}'");

    echo "<ul>";
    while ($r = mysqli_fetch_assoc($resep_awal)) {
        echo "<li>" . htmlspecialchars($r['nama_obat']) . " - " . htmlspecialchars($r['dosis']) . " (" . (int)$r['jumlah'] . ")</li>";
    }
    echo "</ul></td></tr>";

    // Semua follow-up dari kunjungan awal
    $followup = mysqli_query($conn, "SELECT * FROM kunjungan 
        WHERE id_kunjungan_awal = '$id_awal' AND status_kunjungan = 'followup'
        ORDER BY tanggal_kunjungan ASC");

    while ($row = mysqli_fetch_assoc($followup)) {
        echo "<tr>
            <td>" . htmlspecialchars($row['tanggal_kunjungan']) . "</td>
            <td><strong>Follow-Up</strong></td>
            <td>" . htmlspecialchars($row['diagnosa']) . "</td>
            <td>" . htmlspecialchars($row['tindakan']) . "</td>
            <td>" . (int)$row['istirahat'] . " hari</td>
            <td>";

        $id_kunjungan = $row['id_kunjungan'];
        $resep = mysqli_query($conn, "SELECT o.nama_obat, r.dosis, r.jumlah 
            FROM resep r JOIN obat o ON o.kode_obat = r.kode_obat 
            WHERE r.id_kunjungan = '$id_kunjungan'");

        echo "<ul>";
        while ($r = mysqli_fetch_assoc($resep)) {
            echo "<li>" . htmlspecialchars($r['nama_obat']) . " - " . htmlspecialchars($r['dosis']) . " (" . (int)$r['jumlah'] . ")</li>";
        }
        echo "</ul></td></tr>";
    }

    echo "</tbody></table>";
}
?>