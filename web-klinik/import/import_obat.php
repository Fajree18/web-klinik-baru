<!DOCTYPE html>
<html>
<head>
    <title>Import Data Obat</title>
</head>
<body>
    <h3>Import Data Obat (CSV)</h3>
    <form action="proses_import_obat.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="file_csv" accept=".csv" required>
        <button type="submit">Upload dan Import</button>
    </form>
</body>
</html>
