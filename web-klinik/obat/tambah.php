<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";


$obat_list = mysqli_query($conn, "SELECT kode_obat, nama_obat FROM obat ORDER BY kode_obat ASC");
$satuan_options = ['Tablet', 'Botol', 'Ampul', 'Kapsul', 'Syrup', 'Tetes', 'Salep', 'Suppositoria', 'Pcs', 'Vial'];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Data Obat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h3>Tambah Data Obat</h3>

<form action="proses_tambah_obat.php" method="POST" autocomplete="off">
    <div class="mb-3">
        <label for="kode_obat" class="form-label">Kode Obat (Cari dan pilih)</label>
        <input list="list_kode_obat" name="kode_obat" id="kode_obat" class="form-control" required>
        <datalist id="list_kode_obat">
            <?php while ($row = mysqli_fetch_assoc($obat_list)): ?>
                <option data-nama="<?= htmlspecialchars($row['nama_obat']) ?>" value="<?= htmlspecialchars($row['kode_obat']) ?>"></option>
            <?php endwhile; ?>
        </datalist>
    </div>

    <div class="mb-3">
        <label for="nama_obat" class="form-label">Nama Obat</label>
        <input type="text" name="nama_obat" id="nama_obat" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="satuan" class="form-label">Satuan Obat</label>
        <select name="satuan" id="satuan" class="form-select" required>
            <option value="">-- Pilih Satuan --</option>
            <?php foreach ($satuan_options as $satuan): ?>
                <option value="<?= $satuan ?>"><?= $satuan ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="stok" class="form-label">Stok</label>
        <input type="number" name="stok" id="stok" class="form-control" min="0" required>
    </div>

    <div class="mb-3">
        <label for="tanggal_kadaluarsa" class="form-label">Tanggal Kadaluarsa</label>
        <input type="date" name="tanggal_kadaluarsa" id="tanggal_kadaluarsa" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Obat</button>
    <a href="../dashboard.php" class="btn btn-secondary ms-2">Batal</a>
</form>

<script>
  
    document.getElementById('kode_obat').addEventListener('input', function() {
        const val = this.value;
        const options = document.getElementById('list_kode_obat').options;
        let namaObat = '';
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === val) {
                namaObat = options[i].getAttribute('data-nama');
                break;
            }
        }
        document.getElementById('nama_obat').value = namaObat || '';
    });
</script>

</body>
</html>