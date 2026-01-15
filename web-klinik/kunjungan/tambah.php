<?php
/**
 * ================== ANTI-500 DEBUG GUARD ==================
 * Kalau ada fatal error, tampilkan pesan errornya di layar.
 * Nanti kalau sudah beres, kamu bisa hapus blok ini.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo "<pre style='background:#111;color:#0f0;padding:14px;white-space:pre-wrap'>";
        echo "FATAL ERROR:\n";
        print_r($e);
        echo "\n\nTips cepat:\n- Cek path include koneksi.php\n- Cek nama tabel/kolom (pasien/obat/diagnosa)\n- Cek PHP error log Apache\n";
        echo "</pre>";
        exit;
    }
});
/** ======================================================== */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}

include "../koneksi.php";

// pastikan $conn ada
if (!isset($conn) || !$conn) {
    die("<pre style='background:#300;color:#fff;padding:14px;'>KONEKSI DB GAGAL / \$conn tidak terbaca. Cek file koneksi.php</pre>");
}

// helper query aman (kalau error, tampilkan jelas)
function safeQuery($conn, $sql, $label) {
    $q = mysqli_query($conn, $sql);
    if (!$q) {
        echo "<pre style='background:#300;color:#fff;padding:14px;white-space:pre-wrap'>";
        echo "QUERY ERROR ($label)\n\n$sql\n\nMYSQL ERROR:\n" . mysqli_error($conn);
        echo "</pre>";
        exit;
    }
    return $q;
}

/**
 * ====== SESUAIKAN NAMA TABEL & KOLOM ======
 * pasien: id_pasien, nama, no_rm
 * obat: kode_obat, nama_obat, stok
 * diagnosa: id_diagnosa, nama_diagnosa
 */
$pasien = safeQuery($conn, "SELECT id_pasien, nama, no_rm FROM pasien ORDER BY nama ASC", "pasien");
$obat   = safeQuery($conn, "SELECT kode_obat, nama_obat, stok FROM obat ORDER BY nama_obat ASC", "obat");

/**
 * Diagnosa dibuat aman:
 * - kalau tabel diagnosa belum ada -> dropdown tetap tampil + muncul warning
 */
$diagnosa_data = [];
$diagnosa_error = null;

$qDiag = mysqli_query($conn, "SELECT id_diagnosa, nama_diagnosa FROM diagnosa ORDER BY nama_diagnosa ASC");
if (!$qDiag) {
    $diagnosa_error = mysqli_error($conn);
} else {
    while ($d = mysqli_fetch_assoc($qDiag)) {
        $diagnosa_data[] = $d;
    }
}
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
                <option value="<?= htmlspecialchars($row['id_pasien']) ?>" data-rm="<?= htmlspecialchars($row['no_rm']) ?>">
                    <?= htmlspecialchars($row['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- STATUS -->
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

        <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" id="kunjungan_pascacuti" name="kunjungan_status" value="pasca_cuti" onclick="cekCheckbox(this)">
            <label class="form-check-label" for="kunjungan_pascacuti">Karyawan Pasca Cuti</label>
        </div>
    </div>

    <!-- KETERANGAN (muncul untuk followup & pasca cuti) -->
    <div class="mb-3" id="keterangan_followup_container" style="display:none;">
        <label for="keterangan_followup" class="form-label">Keterangan</label>
        <textarea name="keterangan_followup" id="keterangan_followup" class="form-control" placeholder="Masukkan keterangan"></textarea>
    </div>

    <!-- No RM (tetap tampil) -->
    <div class="mb-3">
        <label>No. Rekam Medis</label>
        <input type="text" id="no_rm" class="form-control" readonly>
    </div>

    <!-- ====== FIELD YANG DIHIDE SAAT PASCA CUTI ====== -->
    <div id="field_kunjungan_normal">

        <div class="mb-3" id="field_keluhan">
            <label>Keluhan</label>
            <textarea name="keluhan" id="keluhan" class="form-control"></textarea>
        </div>

        <div class="mb-3" id="field_diagnosa">
            <label>Diagnosa</label>

            <?php if ($diagnosa_error): ?>
                <div class="alert alert-warning">
                    Diagnosa tidak bisa dimuat: <?= htmlspecialchars($diagnosa_error) ?>
                    <br>Silakan cek apakah tabel <strong>diagnosa</strong> dan kolom <strong>id_diagnosa, nama_diagnosa</strong> sudah ada.
                </div>
            <?php endif; ?>

            <select name="diagnosa" id="diagnosa" class="form-control">
                <option value="">-- Pilih Diagnosa --</option>

                <?php if (!empty($diagnosa_data)): ?>
                    <?php foreach ($diagnosa_data as $d): ?>
                        <option value="<?= (int)$d['id_diagnosa'] ?>">
                            <?= htmlspecialchars($d['nama_diagnosa']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">(Data diagnosa belum ada)</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="mb-3" id="field_tindakan">
            <label>Tindakan</label>
            <textarea name="tindakan" id="tindakan" class="form-control"></textarea>
        </div>

        <div class="mb-3" id="field_istirahat">
            <label for="istirahat" class="form-label">Istirahat (hari)</label>
            <input type="number" name="istirahat" id="istirahat" class="form-control" min="0">
        </div>

        <!-- Resep -->
        <div class="mb-3" id="field_resep">
            <label>Resep Obat</label>
            <div id="resep-obat">
                <div class="row g-2 mb-2 align-items-center resep-row">
                    <div class="col-md-4">
                        <select name="obat[]" class="form-control">
                            <option value="">-- Pilih Obat --</option>
                            <?php mysqli_data_seek($obat, 0); while($o = mysqli_fetch_assoc($obat)): ?>
                                <option value="<?= htmlspecialchars($o['kode_obat']) ?>">
                                    <?= htmlspecialchars($o['nama_obat']) ?> (Stok: <?= (int)$o['stok'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="dosis[]" class="form-control" placeholder="Dosis">
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="jumlah[]" class="form-control" placeholder="Jumlah" min="1">
                    </div>
                    <div class="col-md-2 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger btn-remove" onclick="hapusObat(this)">✕</button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="tambahObat()">+ Tambah Obat</button>
        </div>

    </div>
    <!-- ====== END FIELD NORMAL ====== -->

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

function applyStatusUI(status) {
    const containerKeterangan = document.getElementById('keterangan_followup_container');
    const keterangan = document.getElementById('keterangan_followup');
    const normalFields = document.getElementById('field_kunjungan_normal');

    containerKeterangan.style.display = 'none';
    keterangan.required = false;

    normalFields.style.display = 'block';
    setNormalRequired(true);

    if (status === 'followup') {
        containerKeterangan.style.display = 'block';
        keterangan.required = true;
        normalFields.style.display = 'block';
        setNormalRequired(true);
    }

    if (status === 'pasca_cuti') {
        containerKeterangan.style.display = 'block';
        keterangan.required = true;

        normalFields.style.display = 'none';
        setNormalRequired(false);
        clearNormalValues();
    }
}

function setNormalRequired(isRequired) {
    document.getElementById('keluhan').required = isRequired;
    document.getElementById('diagnosa').required = isRequired;
    document.getElementById('tindakan').required = isRequired;
    document.getElementById('istirahat').required = isRequired;

    const resepFirstRow = document.querySelector('#resep-obat .resep-row');
    if (resepFirstRow) {
        const selObat = resepFirstRow.querySelector("select[name='obat[]']");
        const dosis = resepFirstRow.querySelector("input[name='dosis[]']");
        const jumlah = resepFirstRow.querySelector("input[name='jumlah[]']");
        if (selObat) selObat.required = isRequired;
        if (dosis) dosis.required = isRequired;
        if (jumlah) jumlah.required = isRequired;
    }
}

function clearNormalValues() {
    document.getElementById('keluhan').value = '';
    document.getElementById('diagnosa').value = '';
    document.getElementById('tindakan').value = '';
    document.getElementById('istirahat').value = '';

    const resepContainer = document.getElementById('resep-obat');
    const rows = resepContainer.querySelectorAll('.resep-row');
    rows.forEach((r, idx) => { if (idx > 0) r.remove(); });
    const firstRow = resepContainer.querySelector('.resep-row');
    if (firstRow) {
        firstRow.querySelectorAll('input, select').forEach(el => el.value = '');
    }
}

function cekCheckbox(clicked) {
    const pertama = document.getElementById('kunjungan_pertama');
    const followup = document.getElementById('kunjungan_followup');
    const pasca = document.getElementById('kunjungan_pascacuti');

    if (clicked.checked) {
        if (clicked.id !== 'kunjungan_pertama') pertama.checked = false;
        if (clicked.id !== 'kunjungan_followup') followup.checked = false;
        if (clicked.id !== 'kunjungan_pascacuti') pasca.checked = false;
    }

    let status = '';
    if (pertama.checked) status = 'pertama';
    if (followup.checked) status = 'followup';
    if (pasca.checked) status = 'pasca_cuti';

    applyStatusUI(status);
}

function validasiCheckbox() {
    const pertama = document.getElementById('kunjungan_pertama').checked;
    const followup = document.getElementById('kunjungan_followup').checked;
    const pasca = document.getElementById('kunjungan_pascacuti').checked;
    const ket = document.getElementById('keterangan_followup').value.trim();

    if (!pertama && !followup && !pasca) {
        alert('Mohon pilih salah satu status kunjungan.');
        return false;
    }

    if ((followup || pasca) && !ket) {
        alert('Mohon isi keterangan.');
        return false;
    }

    return true;
}

setNormalRequired(false);
</script>
</body>
</html>
