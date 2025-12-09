<?php
include "../koneksi.php";
if (!isset($_POST['id_pasien'])) exit;

$id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
$mcu = mysqli_query($conn, "SELECT * FROM pasien_mcu WHERE id_pasien='$id_pasien' ORDER BY tahun DESC");

echo '<div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0 fw-bold">📋 Data MCU Pasien</h6>
        </div>
        <div class="card-body">';

if (mysqli_num_rows($mcu) > 0) {
    echo '<div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-secondary">
                    <tr class="text-center">
                        <th width="80">Tahun</th>
                        <th>File MCU</th>
                        <th width="180">Tanggal Upload</th>
                    </tr>
                </thead>
                <tbody>';
    while($r = mysqli_fetch_assoc($mcu)) {
        echo '<tr>
                <td class="text-center">'.htmlspecialchars($r['tahun']).'</td>
                <td>
                    <a href="../uploads/mcu/'.htmlspecialchars($r['file_mcu']).'" target="_blank" class="text-decoration-none">
                        📄 '.htmlspecialchars($r['file_mcu']).'
                    </a>
                </td>
                <td class="text-center">'.date('d M Y H:i', strtotime($r['tanggal_upload'])).'</td>
              </tr>';
    }
    echo '</tbody></table></div>';
} else {
    echo '<p class="text-muted mb-0">Belum ada data MCU untuk pasien ini.</p>';
}

echo '</div></div>';
?>
