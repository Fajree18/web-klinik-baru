<!DOCTYPE html>
<html>
<head>
    <title>Export Kunjungan Berdasarkan Tanggal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h4 class="mb-4">Export Kunjungan (Filter Tanggal)</h4>
    <form method="GET" action="export_histori_filter_excel.php">
        <div class="mb-3">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="tanggal_awal" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="tanggal_akhir" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Export Excel</button>
        <a href="../dashboard.php" class="btn btn-secondary">Kembali</a>
    </form>
</body>
</html>
