<?php
include "../koneksi.php";


$departemen_list = mysqli_query($conn, "SELECT DISTINCT departemen FROM pasien WHERE departemen IS NOT NULL AND departemen != '' ORDER BY departemen ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Export Kunjungan Berdasarkan Departemen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h4 class="mb-4">Export Kunjungan (Filter per Departemen)</h4>
    <form method="GET" action="export_histori_filter_excel.php">
        <div class="mb-3">
            <label class="form-label">Pilih Departemen</label>
            <select name="departemen" class="form-select" required>
                <option value="">-- Pilih Departemen --</option>
                <?php while ($d = mysqli_fetch_assoc($departemen_list)): ?>
                    <option value="<?= $d['departemen'] ?>"><?= $d['departemen'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-success">Export Excel</button>
        <a href="../dashboard.php" class="btn btn-secondary">Kembali</a>
    </form>
</body>
</html>
