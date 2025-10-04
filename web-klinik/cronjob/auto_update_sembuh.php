<?php
include "../koneksi.php";

$query = "UPDATE kunjungan
          SET status_kunjungan = 'sembuh'
          WHERE status_kunjungan = 'followup'
          AND DATEDIFF(NOW(), tanggal_kunjungan) >= 7";

$result = mysqli_query($conn, $query);

if ($result) {
    $affected = mysqli_affected_rows($conn);
    echo "✅ Status berhasil diperbarui: $affected pasien follow-up ditandai sembuh.";
} else {
    echo "❌ Gagal memperbarui status: " . mysqli_error($conn);
}
?>