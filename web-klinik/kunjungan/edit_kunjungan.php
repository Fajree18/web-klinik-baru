<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

$id_kunjungan = isset($_GET['id_kunjungan']) ? (int)$_GET['id_kunjungan'] : 0;
if ($id_kunjungan <= 0) {
    die("ID kunjungan tidak valid.");
}

$q = mysqli_query($conn, "SELECT * FROM kunjungan WHERE id_kunjungan = $id_kunjungan");
$data = mysqli_fetch_assoc($q);
if (!$data) {
    die("Data kunjungan tidak ditemukan.");
}

$pasien_list = mysqli_query($conn, "SELECT id_pasien, nama FROM pasien ORDER BY nama ASC");

$resep_lama_res = mysqli_query($conn, "SELECT kode_obat, dosis, jumlah FROM resep WHERE id_kunjungan = $id_kunjungan");
$resep_lama = [];
while ($r = mysqli_fetch_assoc($resep_lama_res)) {
    $resep_lama[$r['kode_obat']] = [
        'jumlah' => (int)$r['jumlah'],
        'dosis' => $r['dosis']
    ];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien']);
    $keluhan = mysqli_real_escape_string($conn, $_POST['keluhan']);
    $diagnosa = mysqli_real_escape_string($conn, $_POST['diagnosa']);
    $tindakan = mysqli_real_escape_string($conn, $_POST['tindakan']);
    $istirahat = isset($_POST['istirahat']) ? (int)$_POST['istirahat'] : 0;
    $status_kunjungan = $_POST['status_kunjungan'] ?? '';

    if (!in_array($status_kunjungan, ['pertama', 'followup'])) {
        $error = "Status kunjungan harus dipilih.";
    } else {
        $update = mysqli_query($conn, "UPDATE kunjungan SET 
            id_pasien='$id_pasien', 
            keluhan='$keluhan', 
            diagnosa='$diagnosa', 
            tindakan='$tindakan',
            istirahat=$istirahat,
            status_kunjungan='$status_kunjungan'
            WHERE id_kunjungan=$id_kunjungan");

        if (!$update) {
            $error = "Gagal memperbarui data kunjungan.";
        } else {
            $obat_baru = $_POST['obat'] ?? [];
            $jumlah_baru = $_POST['jumlah'] ?? [];
            $dosis_baru = $_POST['dosis'] ?? [];

            // Kembalikan stok lama
            foreach ($resep_lama as $kode_obat => $r) {
                mysqli_query($conn, "UPDATE obat SET stok = stok + {$r['jumlah']} WHERE kode_obat = '$kode_obat'");
                mysqli_query($conn, "DELETE FROM stok_log WHERE kode_obat = '$kode_obat' AND tipe='keluar' AND id_kunjungan = $id_kunjungan");
            }

            // Hapus resep lama
            mysqli_query($conn, "DELETE FROM resep WHERE id_kunjungan = $id_kunjungan");

            // Tambah resep baru
            foreach ($obat_baru as $i => $kode_obat) {
                $kode_obat = mysqli_real_escape_string($conn, $kode_obat);
                $jumlah = (int)$jumlah_baru[$i];
                $dosis = mysqli_real_escape_string($conn, $dosis_baru[$i]);

                if ($kode_obat && $jumlah > 0 && $dosis) {
                    mysqli_query($conn, "INSERT INTO resep (id_kunjungan, kode_obat, dosis, jumlah) 
                                         VALUES ($id_kunjungan, '$kode_obat', '$dosis', $jumlah)");
                    mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat'");
                    mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan) 
                                         VALUES ('$kode_obat', 'keluar', $jumlah, NOW(), $id_kunjungan)");
                }
            }

            $success = "Data kunjungan berhasil diperbarui.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Kunjungan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .remove-btn { cursor:pointer; font-weight:bold; }
    </style>
</head>
<body class="container mt-4">
<h4>Edit Kunjungan Pasien</h4>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="mb-3">
        <label>Pasien</label>
        <select name="id_pasien" class="form-select" required>
            <option value="">-- Pilih Pasien --</option>
            <?php while ($p = mysqli_fetch_assoc($pasien_list)): ?>
                <option value="<?= $p['id_pasien'] ?>" <?= ($p['id_pasien'] == $data['id_pasien']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Status Kunjungan</label><br>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_kunjungan" value="pertama" <?= $data['status_kunjungan'] == 'pertama' ? 'checked' : '' ?>> Pertama
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_kunjungan" value="followup" <?= $data['status_kunjungan'] == 'followup' ? 'checked' : '' ?>> Follow-Up
        </div>
    </div>

    <div class="mb-3">
        <label>Keluhan</label>
        <textarea name="keluhan" class="form-control" required><?= htmlspecialchars($data['keluhan']) ?></textarea>
    </div>

    <div class="mb-3">
        <label>Diagnosa</label>
        <textarea name="diagnosa" class="form-control" required><?= htmlspecialchars($data['diagnosa']) ?></textarea>
    </div>

    <div class="mb-3">
        <label>Tindakan</label>
        <textarea name="tindakan" class="form-control" required><?= htmlspecialchars($data['tindakan']) ?></textarea>
    </div>

    <div class="mb-3">
        <label>Istirahat (hari)</label>
        <input type="number" name="istirahat" class="form-control" min="0" required value="<?= (int)$data['istirahat'] ?>">
    </div>

    <div class="mb-3">
        <label>Resep Obat</label>
        <div id="resep-container">
            <?php
            $obat_list = mysqli_query($conn, "SELECT kode_obat, nama_obat FROM obat ORDER BY nama_obat ASC");
            $obat_arr = [];
            while ($o = mysqli_fetch_assoc($obat_list)) {
                $obat_arr[$o['kode_obat']] = $o['nama_obat'];
            }

            foreach ($resep_lama as $kode_obat => $r):
            ?>
            <div class="row mb-2 resep-item">
                <div class="col-md-4">
                    <select name="obat[]" class="form-select" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php foreach ($obat_arr as $ko => $nama): ?>
                            <option value="<?= $ko ?>" <?= $ko == $kode_obat ? 'selected' : '' ?>><?= htmlspecialchars($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="dosis[]" class="form-control" placeholder="Dosis" value="<?= htmlspecialchars($r['dosis']) ?>" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="jumlah[]" class="form-control" min="1" value="<?= (int)$r['jumlah'] ?>" required>
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button type="button" class="btn btn-danger btn-sm remove-btn">&times;</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="tambah-obat" class="btn btn-secondary btn-sm">+ Tambah Obat</button>
    </div>

    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    <a href="daftar_kunjungan.php" class="btn btn-secondary">Batal</a>
</form>

<script>
document.getElementById('tambah-obat').addEventListener('click', function () {
    const container = document.getElementById('resep-container');
    const row = document.createElement('div');
    row.className = 'row mb-2 resep-item';

    let options = '<option value="">-- Pilih Obat --</option>';
    <?php foreach ($obat_arr as $ko => $nama): ?>
    options += '<option value="<?= $ko ?>"><?= addslashes($nama) ?></option>';
    <?php endforeach; ?>

    row.innerHTML = `
        <div class="col-md-4">
            <select name="obat[]" class="form-select" required>${options}</select>
        </div>
        <div class="col-md-4">
            <input type="text" name="dosis[]" class="form-control" placeholder="Dosis" required>
        </div>
        <div class="col-md-3">
            <input type="number" name="jumlah[]" class="form-control" min="1" value="1" required>
        </div>
        <div class="col-md-1 d-flex align-items-center">
            <button type="button" class="btn btn-danger btn-sm remove-btn">&times;</button>
        </div>
    `;
    container.appendChild(row);
});

document.getElementById('resep-container').addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-btn')) {
        e.target.closest('.resep-item').remove();
    }
});
</script>
</body>
</html>