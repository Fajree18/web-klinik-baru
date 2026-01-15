<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";

$pasien = mysqli_query($conn, "SELECT id_pasien, nama, no_rm FROM pasien ORDER BY nama ASC");
if (!$pasien) die("Query pasien gagal: " . mysqli_error($conn));

$obat = mysqli_query($conn, "SELECT kode_obat, nama_obat, stok FROM obat ORDER BY nama_obat ASC");
if (!$obat) die("Query obat gagal: " . mysqli_error($conn));

// ==================== DIAGNOSA DROPDOWN (AUTO) ====================
// Kalau tabel diagnosa ada -> ambil dari DB
// Kalau tidak ada -> pakai list manual (biar gak 500)
$diagnosa_options = [];
$diagnosa_error = null;
$use_diagnosa_table = false;

$cekDiag = mysqli_query($conn, "SHOW TABLES LIKE 'diagnosa'");
if ($cekDiag && mysqli_num_rows($cekDiag) > 0) {
    $use_diagnosa_table = true;
    $qd = mysqli_query($conn, "SELECT id_diagnosa, nama_diagnosa FROM diagnosa ORDER BY nama_diagnosa ASC");
    if ($qd) {
        while ($d = mysqli_fetch_assoc($qd)) {
            $diagnosa_options[] = $d;
        }
    } else {
        $diagnosa_error = mysqli_error($conn);
        $use_diagnosa_table = false;
    }
}

$diagnosa_manual = [
    "ISPA",
    "Demam",
    "Flu",
    "Hipertensi",
    "Maag",
    "Sakit Kepala",
    "Diare",
    "Asma",
    "Alergi",
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Follow-Up Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <style>
        body { font-size: 14px; }
        .btn-sm { font-size: 12px; padding: 4px 8px; }
        .remove-btn { height: 38px; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body class="container mt-4">

<h4>Form Kunjungan Follow-Up Pasien</h4>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
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
                <option value="<?= htmlspecialchars($row['id_pasien']) ?>" data-rm="<?= htmlspecialchars($row['no_rm']) ?>">
                    <?= htmlspecialchars($row['nama']) ?>
                </option>
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

    <!-- DIAGNOSA DROPDOWN -->
    <div class="mb-3">
        <label>Diagnosa</label>

        <?php if ($diagnosa_error): ?>
            <div class="alert alert-warning">
                Diagnosa dari tabel tidak bisa dimuat: <?= htmlspecialchars($diagnosa_error) ?>
            </div>
        <?php endif; ?>

        <select name="diagnosa" id="diagnosa" class="form-control" required>
            <option value="">-- Pilih Diagnosa --</option>

            <?php if ($use_diagnosa_table && !empty($diagnosa_options)): ?>
                <?php foreach ($diagnosa_options as $d): ?>
                    <option value="<?= (int)$d['id_diagnosa'] ?>">
                        <?= htmlspecialchars($d['nama_diagnosa']) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($diagnosa_manual as $txt): ?>
                    <option value="<?= htmlspecialchars($txt) ?>"><?= htmlspecialchars($txt) ?></option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <div class="form-text">
            <?php if ($use_diagnosa_table): ?>
                Diagnosa diambil dari master diagnosa (tabel diagnosa).
            <?php else: ?>
                Diagnosa memakai list manual (tabel diagnosa belum ada).
            <?php endif; ?>
        </div>
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

    <!-- RESEP -->
    <div class="mb-3">
        <label>Resep Obat</label>

        <div id="resep-obat">
            <div class="row g-2 mb-2 align-items-center resep-row">
                <div class="col-md-4">
                    <select name="obat[]" class="form-control" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php mysqli_data_seek($obat, 0); while($o = mysqli_fetch_assoc($obat)): ?>
                            <option value="<?= htmlspecialchars($o['kode_obat']) ?>">
                                <?= htmlspecialchars($o['nama_obat']) ?> (Stok: <?= (int)$o['stok'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="dosis[]" class="form-control" placeholder="Contoh: 3x1 / 500mg" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="jumlah[]" class="form-control" placeholder="Jumlah" min="1" required>
                </div>
                <div class="col-md-2 d-flex justify-content-center">
                    <button type="button" class="btn btn-outline-danger remove-btn" onclick="hapusObat(this)">✕</button>
                </div>
            </div>
        </div>

        <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahObat()">+ Tambah Obat</button>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-success">Simpan Follow-Up</button>
        <a href="../dashboard.php" class="btn btn-secondary me-2">Kembali ke Dashboard</a>
    </div>
</form>

<script>
function isiRM(select) {
    const rm = select.options[select.selectedIndex].getAttribute('data-rm');
    document.getElementById('no_rm').value = rm || '';
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
</script>

</body>
</html>
