<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

$pasien = mysqli_query($conn, "SELECT id_pasien, nama, no_rm FROM pasien ORDER BY nama ASC");
$obat = mysqli_query($conn, "SELECT kode_obat, nama_obat, stok FROM obat ORDER BY nama_obat ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Follow-Up Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body class="container mt-4">

<h4>Form Kunjungan Follow-Up Pasien</h4>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="mb-3">
    <label>Scan Barcode (NIK|Nama|Jabatan)</label>
    <input type="text" id="barcode_input" class="form-control" oninput="isiDariBarcode(this.value)" autofocus>
</div>

<form action="proses_followup.php" method="POST">
    <div class="mb-3">
        <label>Nama Pasien</label>
        <select name="id_pasien" class="form-control" onchange="isiRM(this)" required>
            <option value="">-- Pilih Pasien --</option>
            <?php while ($row = mysqli_fetch_assoc($pasien)): ?>
                <option value="<?= $row['id_pasien'] ?>" data-rm="<?= $row['no_rm'] ?>"><?= htmlspecialchars($row['nama']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>No. Rekam Medis</label>
        <input type="text" id="no_rm" class="form-control" readonly>
    </div>

    <div class="mb-3">
        <label>Catatan/Keluhan Follow-Up</label>
        <textarea name="catatan" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
        <label>Diagnosa</label>
        <textarea name="diagnosa" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
        <label>Tindakan</label>
        <textarea name="tindakan" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
        <label>Istirahat (hari)</label>
        <input type="number" name="istirahat" class="form-control" min="0" required>
    </div>

    <div class="mb-3">
        <label>Status Follow-Up</label>
        <select name="status_followup" class="form-control" required>
            <option value="Proses">Proses</option>
            <option value="Sembuh">Sembuh</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Resep Obat</label>
        <div id="resep-obat">
            <div class="row mb-2 align-items-end">
                <div class="col-md-4">
                    <select name="obat[]" class="form-control" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php mysqli_data_seek($obat, 0); while($o = mysqli_fetch_assoc($obat)): ?>
                            <option value="<?= $o['kode_obat'] ?>"><?= htmlspecialchars($o['nama_obat']) ?> (Stok: <?= $o['stok'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="dosis[]" class="form-control" placeholder="Contoh: 500mg" required>
                </div>
                <div class="col-md-4">
                    <input type="number" name="jumlah[]" class="form-control" placeholder="Jumlah" min="1" required>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahObat()">+ Tambah Obat</button>
    </div>

  
   <div class="mt-4">
    <button type="submit" class="btn btn-success">Simpan Follow-Up</button>
    <a href="../dashboard.php" class="btn btn-secondary me-2">Kembali ke Dashboard</a>
</div>

<script>
function isiRM(select) {
    const rm = select.options[select.selectedIndex].getAttribute('data-rm');
    document.getElementById('no_rm').value = rm || '';
}

function tambahObat() {
    const resep = document.querySelector('#resep-obat .row.mb-2');
    const clone = resep.cloneNode(true);
    clone.querySelectorAll('input, select').forEach(el => el.value = '');
    document.getElementById('resep-obat').appendChild(clone);
}

function isiDariBarcode(code) {
    const [nik] = code.split("|");
    const select = document.querySelector("select[name='id_pasien']");
    for (let i = 0; i < select.options.length; i++) {
        if (select.options[i].value === nik.trim()) {
            select.selectedIndex = i;
            isiRM(select);
            break;
        }
    }
}
</script>
</body>
</html>