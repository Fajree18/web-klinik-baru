<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

if (!isset($_GET['kode_obat'])) {
    die("Kode obat tidak ditemukan");
}

$kode_obat = mysqli_real_escape_string($conn, $_GET['kode_obat']);

// Ambil data obat
$q = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat='$kode_obat'");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    die("Data obat tidak ditemukan");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Stok Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h4 class="mb-3">➕ Tambah Stok Obat</h4>

<form method="POST" action="">
    <div class="mb-3">
        <label>Kode Obat</label>
        <input type="text" class="form-control" value="<?= $data['kode_obat'] ?>" readonly>
    </div>

    <div class="mb-3">
        <label>Nama Obat</label>
        <input type="text" class="form-control" value="<?= $data['nama_obat'] ?>" readonly>
    </div>

    <div class="mb-3">
        <label>Stok Saat Ini</label>
        <input type="text" class="form-control" value="<?= $data['stok'] ?>" readonly>
    </div>

    <div class="mb-3">
        <label>Jumlah Tambah</label>
        <input type="number" name="jumlah" class="form-control" required min="1">
    </div>

    <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
    <a href="daftar_obat.php" class="btn btn-secondary">Kembali</a>
</form>

</body>
</html>

<?php
if (isset($_POST['simpan'])) {
    $jumlah = (int)$_POST['jumlah'];

    if ($jumlah <= 0) {
        echo "<script>alert('Jumlah tidak valid');</script>";
        exit;
    }

    // Update stok
    mysqli_query($conn, "UPDATE obat 
                         SET stok = stok + $jumlah 
                         WHERE kode_obat='$kode_obat'");

    // Simpan log
    mysqli_query($conn, "INSERT INTO stok_log 
        (kode_obat, tipe, jumlah, tanggal) 
        VALUES 
        ('$kode_obat', 'masuk', $jumlah, NOW())");

    echo "<script>
        alert('Stok berhasil ditambahkan');
        window.location='daftar_obat.php';
    </script>";
}
?>