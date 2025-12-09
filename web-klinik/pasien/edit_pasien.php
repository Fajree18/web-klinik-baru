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

    // Validasi ID unik
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
    exit;
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
<form method="POST" enctype="multipart/form-data" class="mb-4">
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

<!-- === Bagian MCU multi-upload (terpisah dari form utama) === -->
<div class="card mt-4 shadow-sm">
  <div class="card-header bg-light">
    <h6 class="mb-0 fw-bold">📋 File MCU (Medical Check-Up)</h6>
  </div>
  <div class="card-body">
    <?php
    $mcu_list = mysqli_query($conn, "SELECT * FROM pasien_mcu WHERE id_pasien='$id' ORDER BY tahun DESC");
    if (mysqli_num_rows($mcu_list) > 0): ?>
        <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-secondary">
                    <tr class="text-center">
                        <th width="80">Tahun</th>
                        <th>File MCU</th>
                        <th width="180">Tanggal Upload</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($m = mysqli_fetch_assoc($mcu_list)): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($m['tahun']) ?></td>
                            <td>
                                <a href="../uploads/mcu/<?= htmlspecialchars($m['file_mcu']) ?>" target="_blank" class="text-decoration-none">
                                    📄 <?= htmlspecialchars($m['file_mcu']) ?>
                                </a>
                            </td>
                            <td class="text-center"><?= date('d M Y H:i', strtotime($m['tanggal_upload'])) ?></td>
                            <td class="text-center">
                                <a href="hapus_mcu_multi.php?id=<?= $m['id_mcu'] ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Yakin hapus file MCU ini?')">
                                   Hapus
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted mb-3">Belum ada file MCU yang diupload untuk pasien ini.</p>
    <?php endif; ?>

    <!-- Form upload MCU (terpisah) -->
    <form action="upload_mcu_multi.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_pasien" value="<?= $data['id_pasien'] ?>">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <label class="form-label mb-1">Tahun MCU</label>
                <input type="number" name="tahun" class="form-control" 
                       min="2000" max="<?= date('Y') ?>" value="<?= date('Y') ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label mb-1">Upload File PDF</label>
                <input type="file" name="file_mcu" accept="application/pdf" class="form-control" required>
            </div>
            <div class="col-md-3 d-grid">
                <label class="form-label mb-1 invisible">Upload</label>
                <button type="submit" class="btn btn-primary">
                    📤 Upload MCU
                </button>
            </div>
        </div>
        <p class="text-muted small mt-2 mb-0">
            * Hanya file <strong>PDF</strong> yang diperbolehkan. 
            File MCU baru akan disimpan tanpa menimpa file sebelumnya.
        </p>
    </form>
  </div>
</div>

</body>
</html>
