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
    <title>Input Kunjungan Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body { font-size: 14px; }
        .resep-row .form-control { font-size: 13px; }
        .resep-row .btn-remove {
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 10px;
        }
    </style>
</head>
<body class="container mt-4">

<h4>Form Input Kunjungan Pasien</h4>

<div class="mb-3">
    <label>Scan Barcode (NIK|Nama|Jabatan)</label>
    <input type="text" id="barcode_input" class="form-control" oninput="isiDariBarcode(this.value)" autofocus>
</div>

<form action="proses_tambah.php" method="POST" onsubmit="return validasiCheckbox()">
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
        <label>Status Kunjungan</label><br>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="kunjungan_pertama" name="kunjungan_status" value="pertama" onclick="cekCheckbox(this)">
            <label class="form-check-label" for="kunjungan_pertama">Kunjungan Pertama</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="kunjungan_followup" name="kunjungan_status" value="followup" onclick="cekCheckbox(this)">
            <label class="form-check-label" for="kunjungan_followup">Kunjungan Followup</label>
        </div>
    </div>

    <div class="mb-3" id="keterangan_followup_container" style="display: none;">
        <label for="keterangan_followup" class="form-label">Keterangan Followup</label>
        <textarea name="keterangan_followup" id="keterangan_followup" class="form-control" placeholder="Masukkan keterangan followup jika ada"></textarea>
    </div>

    <div class="mb-3">
        <label>No. Rekam Medis</label>
        <input type="text" id="no_rm" class="form-control" readonly>
    </div>

    <div class="mb-3">
        <label>Keluhan</label>
        <textarea name="keluhan" class="form-control" required></textarea>
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
        <label for="istirahat" class="form-label">Istirahat (hari)</label>
        <input type="number" name="istirahat" id="istirahat" class="form-control" min="0" required>
    </div>

    <!-- Bagian Resep Obat -->
    <div class="mb-3">
        <label>Resep Obat</label>
        <div id="resep-obat">
            <div class="row g-2 mb-2 align-items-center resep-row">
                <div class="col-md-4">
                    <select name="obat[]" class="form-control" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php mysqli_data_seek($obat, 0); while($o = mysqli_fetch_assoc($obat)): ?>
                            <option value="<?= $o['kode_obat'] ?>"><?= htmlspecialchars($o['nama_obat']) ?> (Stok: <?= $o['stok'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="dosis[]" class="form-control" placeholder="Contoh: Paracetamol 500mg" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="jumlah[]" class="form-control" placeholder="Jumlah" min="1" required>
                </div>
                <div class="col-md-2 d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-danger btn-remove" onclick="hapusObat(this)">✕</button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="tambahObat()">+ Tambah Obat</button>
    </div>
    <!-- end resep -->

    <button type="submit" class="btn btn-success">Simpan Kunjungan</button>
    <a href="../dashboard.php" class="btn btn-secondary ms-2">Kembali ke Dashboard</a>
</form>

<!-- Riwayat Kunjungan -->
<hr class="my-4">
<h5>Riwayat Kunjungan Sebelumnya</h5>
<div id="riwayat_pasien" class="mt-3"></div>

<script>
    function isiRM(select) {
        const rm = select.options[select.selectedIndex].getAttribute('data-rm');
        document.getElementById('no_rm').value = rm || '';

        const id_pasien = select.value;
        tampilkanHistori(id_pasien);
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

function tampilkanHistori(id_pasien) {
    if (!id_pasien) return;
    $.post("ajax_histori_berobat.php", { id_pasien: id_pasien }, function(res) {
        $('#riwayat_pasien').html(res);
    });
}

function tambahObat() {
    const resepContainer = document.getElementById('resep-obat');
    const firstRow = resepContainer.querySelector('.resep-row');
    const clone = firstRow.cloneNode(true);

    // Reset semua input/select di baris baru
    clone.querySelectorAll('input, select').forEach(el => el.value = '');
    resepContainer.appendChild(clone);
}

function hapusObat(btn) {
    const allRows = document.querySelectorAll('#resep-obat .resep-row');
    if (allRows.length > 1) {
        btn.closest('.resep-row').remove();
    } else {
        alert("Minimal harus ada satu resep obat!");
    }
}

function cekCheckbox(clicked) {
    const pertama = document.getElementById('kunjungan_pertama');
    const followup = document.getElementById('kunjungan_followup');
    const keteranganFollowupContainer = document.getElementById('keterangan_followup_container');
    const keteranganFollowup = document.getElementById('keterangan_followup');

    if (clicked.checked) {
        if (clicked.id === 'kunjungan_pertama') {
            followup.checked = false;
            keteranganFollowupContainer.style.display = 'none';
            keteranganFollowup.value = '';
        } else {
            pertama.checked = false;
            keteranganFollowupContainer.style.display = 'block';
        }
    }
}

function validasiCheckbox() {
    const pertama = document.getElementById('kunjungan_pertama').checked;
    const followup = document.getElementById('kunjungan_followup').checked;
    const keteranganFollowup = document.getElementById('keterangan_followup').value;

    if (!pertama && !followup) {
        alert('Mohon pilih salah satu status kunjungan.');
        return false;
    }

    if (followup && !keteranganFollowup) {
        alert('Mohon isi keterangan followup.');
        return false;
    }

    return true;
}
</script>
</body>
</html>
