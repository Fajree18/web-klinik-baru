<?php
session_start();
if (!isset($_SESSION['admin'])) header("Location: ../index.php");
include "../koneksi.php";

$prefix = "MED-" . date("Ym");
$last = mysqli_query($conn, "SELECT MAX(no_rm) as max_rm FROM pasien WHERE no_rm LIKE '$prefix%'");
$data_rm = mysqli_fetch_assoc($last);
$next_number = $data_rm['max_rm'] ? str_pad((int)substr($data_rm['max_rm'], -4) + 1, 4, '0', STR_PAD_LEFT) : "001";
$no_rm_auto = $prefix . $next_number;

$departemen_list = mysqli_query($conn, "SELECT nama FROM departemen ORDER BY nama ASC");

?>
<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
<h4>Form Registrasi Pasien</h4>

<div class="mb-2">
    <label>Scan Barcode (NIK|Nama|Jabatan)</label>
    <input type="text" class="form-control" id="barcode_scan" oninput="isiDataBarcode(this.value)" autofocus>
</div>

<form action="proses_tambah.php" method="POST">
    <div class="mb-2">
        <label>NIK Karyawan (ID Pasien)</label>
        <input name="id_pasien" id="id_pasien" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>No. Rekam Medis</label>
        <input type="text" name="no_rm" class="form-control" value="<?= $no_rm_auto ?>" readonly>
    </div>
    <div class="mb-2">
        <label>Nama</label>
        <input name="nama" id="nama" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Departemen</label>
        <select name="departemen" id="departemen" class="form-control" required>
            <option value="">-- Pilih Departemen --</option>
            <?php while ($d = mysqli_fetch_assoc($departemen_list)): ?>
                <option value="<?= $d['nama'] ?>"><?= $d['nama'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Jabatan</label>
        <input name="jabatan" id="jabatan" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Tanggal Lahir</label>
        <div class="row">
            <div class="col">
                <select name="tgl" class="form-control" required>
                    <option value="">Tgl</option>
                    <?php for ($i = 1; $i <= 31; $i++) echo "<option value='$i'>$i</option>"; ?>
                </select>
            </div>
            <div class="col">
                <select name="bln" class="form-control" required>
                    <option value="">Bulan</option>
                    <?php $bulan = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                    foreach ($bulan as $k => $v) echo "<option value='$k'>$v</option>"; ?>
                </select>
            </div>
            <div class="col">
                <select name="thn" class="form-control" required>
                    <option value="">Tahun</option>
                    <?php for ($y = date("Y"); $y >= 1940; $y--) echo "<option value='$y'>$y</option>"; ?>
                </select>
            </div>
        </div>
    </div>
    <div class="mb-2">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" class="form-control" required>
            <option value="Laki-laki">Laki-laki</option>
            <option value="Perempuan">Perempuan</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Alamat</label>
        <textarea name="alamat" class="form-control" required></textarea>
    </div>
    <div class="mb-2">
        <label>Riwayat Sakit</label>
        <textarea name="riwayat_sakit" class="form-control" required></textarea>
    </div>
    <div class="mb-2">
        <label>No. Telepon</label>
        <input name="telepon" class="form-control" required>
    </div>
    <button class="btn btn-success">Simpan</button>
    <a href="../dashboard.php" class="btn btn-secondary">Kembali</a>
</form>

<script>
function isiDataBarcode(barcode) {
    const parts = barcode.split("|");
    document.getElementById("id_pasien").value = parts[0]?.trim() || "";
    document.getElementById("nama").value = parts[1]?.trim() || "";
    document.getElementById("jabatan").value = parts[4]?.trim() || "";

    const departemen = parts[3]?.trim() || "";
    const departemenSelect = document.getElementById("departemen");
    for (let i = 0; i < departemenSelect.options.length; i++) {
        if (departemenSelect.options[i].value === departemen) {
            departemenSelect.selectedIndex = i;
            break;
        }
    }
}
</script>
</body>
</html>
