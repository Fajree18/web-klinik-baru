<?php
include "../koneksi.php";

$query = mysqli_query($conn, "SELECT p.no_rm, p.id_pasien, p.nama, p.departemen, p.jabatan,k.keluhan, k.diagnosa, k.tindakan, k.istirahat FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien ORDER BY k.diagnosa ASC
");

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="Eksport by Diagnosa.csv"');
$output = fopen('php://output', 'w');

fputcsv($output, ['No RM', 'NIK', 'Nama Pasien', 'Departemen', 'Jabatan', 'Keluhan', 'Diagnosa', 'Tindakan', 'Lama Istirahat']);

while ($data = mysqli_fetch_assoc($query)) {
    fputcsv($output, [
        $data['no_rm'], 
        $data['id_pasien'], 
        $data['nama'], 
        $data['departemen'], 
        $data['jabatan'], 
        $data['keluhan'], 
        $data['diagnosa'], 
        $data['tindakan'], 
        $data['istirahat']
    ]);
}

fclose($output);
exit;
?>
