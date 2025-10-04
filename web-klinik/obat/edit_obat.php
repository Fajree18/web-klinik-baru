<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

$kode_obat = $_GET['kode_obat'] ?? '';
if (!$kode_obat) {
    die("Kode obat tidak ditemukan.");
}

$kode_obat = mysqli_real_escape_string($conn, $kode_obat);
$query = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat = '$kode_obat'");
$data = mysqli_fetch_assoc($query);
if (!$data) {
    die("Obat tidak ditemukan.");
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_obat = mysqli_real_escape_string($conn, $_POST['nama_obat']);
    $satuan = mysqli_real_escape_string($conn, $_POST['satuan']);
    $stok = (int)$_POST['stok'];
    $tanggal_kadaluarsa = mysqli_real_escape_string($conn, $_POST['tanggal_kadaluarsa']);

    
    $update = mysqli_query($conn, "UPDATE obat SET nama_obat='$nama_obat', satuan='$satuan', stok=$stok, tanggal_kadaluarsa='$tanggal_kadaluarsa' WHERE kode_obat='$kode_obat'");

    if ($update) {
        $success = "Data obat berhasil diperbarui.";
        
        $query = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat = '$kode_obat'");
        $data = mysqli_fetch_assoc($query);
    } else {
        $error = "Gagal memperbarui data obat.";
    }
}


$satuan_options = ['Tablet', 'Botol', 'Ampul', 'Kapsul', 'Syrup', 'Tetes', 'Salep', 'Suppositoria', 'Pcs', 'Vial'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">

<h3>Edit Data Obat</h3>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <div class="mb-3">
        <label for="kode_obat" class="form-label">Kode Obat</label>
        <input type="text" id="kode_obat" class="form-control" value="<?= htmlspecialchars($data['kode_obat']) ?>" disabled>
    </div>

    <div class="mb-3">
        <label for="nama_obat" class="form-label">Nama Obat</label>
        <input type="text" name="nama_obat" id="nama_obat" class="form-control" value="<?= htmlspecialchars($data['nama_obat']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="satuan" class="form-label">Satuan Obat</label>
        <select name="satuan" id="satuan" class="form-select" required>
            <option value="">-- Pilih Satuan --</option>
            <?php foreach ($satuan_options as $satuan): ?>
                <option value="<?= $satuan ?>" <?= ($data['satuan'] === $satuan) ? 'selected' : '' ?>><?= $satuan ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="stok" class="form-label">Stok</label>
        <input type="number" name="stok" id="stok" class="form-control" min="0" value="<?= (int)$data['stok'] ?>" required>
    </div>

    <div class="mb-3">
        <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa</label>
        <input type="date" name="tanggal_kadaluarsa" id="tanggal_kadaluarsa" class="form-control" value="<?= htmlspecialchars($data['tanggal_kadaluarsa']) ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Update Data</button>
    <a href="daftar_obat.php" class="btn btn-secondary ms-2">Batal</a>
</form>

</body>
</html>