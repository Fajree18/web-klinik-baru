<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id'"));
$departemen_list = mysqli_query($conn, "SELECT nama FROM departemen ORDER BY nama ASC");

if (isset($_POST['simpan'])) {
    $id_pasien_baru = mysqli_real_escape_string($conn, $_POST['id_pasien']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $departemen = mysqli_real_escape_string($conn, $_POST['departemen']);
    $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $riwayat = mysqli_real_escape_string($conn, $_POST['riwayat_sakit']);
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon']);

    // Validasi NIK tidak boleh sama dengan pasien lain
    $cek = mysqli_query($conn, "SELECT * FROM pasien WHERE id_pasien = '$id_pasien_baru' AND id_pasien != '$id'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('ID Pasien / NIK sudah digunakan oleh pasien lain.'); window.location='edit_pasien.php?id=$id';</script>";
        exit;
    }

    mysqli_query($conn, "UPDATE pasien SET 
        id_pasien = '$id_pasien_baru',
        nama = '$nama',
        departemen = '$departemen',
        jabatan = '$jabatan',
        tanggal_lahir = '$tanggal_lahir',
        jenis_kelamin = '$jenis_kelamin',
        alamat = '$alamat',
        riwayat_sakit = '$riwayat',
        telepon = '$telepon'
        WHERE id_pasien = '$id'");

    header("Location: daftar_pasien.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h4>Edit Data Pasien</h4>
<form method="POST">
    <div class="mb-2">
        <label>ID Pasien (NIK)</label>
        <input name="id_pasien" class="form-control" value="<?= $data['id_pasien'] ?>" required>
    </div>
    <div class="mb-2">
        <label>No. RM</label>
        <input type="text" class="form-control" value="<?= $data['no_rm'] ?>" readonly>
    </div>
    <div class="mb-2">
        <label>Nama</label>
        <input name="nama" class="form-control" value="<?= $data['nama'] ?>" required>
    </div>
    <div class="mb-2">
        <label>Departemen</label>
        <select name="departemen" class="form-control" required>
            <option value="">-- Pilih Departemen --</option>
            <?php while ($d = mysqli_fetch_assoc($departemen_list)): ?>
                <option value="<?= $d['nama'] ?>" <?= $data['departemen'] == $d['nama'] ? 'selected' : '' ?>>
                    <?= $d['nama'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Jabatan</label>
        <input name="jabatan" class="form-control" value="<?= $data['jabatan'] ?>" required>
    </div>
    <div class="mb-2">
        <label>Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" class="form-control" value="<?= $data['tanggal_lahir'] ?>" required>
    </div>
    <div class="mb-2">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-control" required>
            <option value="Laki-laki" <?= $data['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
            <option value="Perempuan" <?= $data['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Alamat</label>
        <textarea name="alamat" class="form-control"><?= $data['alamat'] ?></textarea>
    </div>
    <div class="mb-2">
        <label>Riwayat Sakit</label>
        <textarea name="riwayat_sakit" class="form-control"><?= $data['riwayat_sakit'] ?></textarea>
    </div>
    <div class="mb-2">
        <label>No. Telepon</label>
        <input name="telepon" class="form-control" value="<?= $data['telepon'] ?>">
    </div>
    <button name="simpan" class="btn btn-success">Simpan Perubahan</button>
    <a href="daftar_pasien.php" class="btn btn-secondary ms-2">Batal</a>
</form>
</body>
</html>
