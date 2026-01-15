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

// ambil data kunjungan
$q = mysqli_query($conn, "SELECT * FROM kunjungan WHERE id_kunjungan = $id_kunjungan");
if (!$q) die("Query kunjungan gagal: " . mysqli_error($conn));

$data = mysqli_fetch_assoc($q);
if (!$data) {
    die("Data kunjungan tidak ditemukan.");
}

$pasien_list = mysqli_query($conn, "SELECT id_pasien, nama FROM pasien ORDER BY nama ASC");
if (!$pasien_list) die("Query pasien gagal: " . mysqli_error($conn));

// ==================== DIAGNOSA DROPDOWN (AUTO) ====================
// Mode A: Kalau tabel diagnosa ada -> ambil dari DB
// Mode B: Kalau tabel diagnosa tidak ada -> pakai list manual
$diagnosa_options = [];
$diagnosa_error = null;
$use_diagnosa_table = false;

// cek apakah tabel diagnosa ada
$cekDiag = mysqli_query($conn, "SHOW TABLES LIKE 'diagnosa'");
if ($cekDiag && mysqli_num_rows($cekDiag) > 0) {
    $use_diagnosa_table = true;
    $qd = mysqli_query($conn, "SELECT id_diagnosa, nama_diagnosa FROM diagnosa ORDER BY nama_diagnosa ASC");
    if ($qd) {
        while ($d = mysqli_fetch_assoc($qd)) {
            $diagnosa_options[] = $d; // ['id_diagnosa' => ..., 'nama_diagnosa' => ...]
        }
    } else {
        $diagnosa_error = mysqli_error($conn);
        $use_diagnosa_table = false;
    }
}

// fallback manual (kalau tabel diagnosa tidak ada)
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

// ==================================================================

$resep_lama_res = mysqli_query($conn, "SELECT kode_obat, dosis, jumlah FROM resep WHERE id_kunjungan = $id_kunjungan");
if (!$resep_lama_res) die("Query resep lama gagal: " . mysqli_error($conn));

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

    $id_pasien = mysqli_real_escape_string($conn, $_POST['id_pasien'] ?? '');
    $keluhan   = mysqli_real_escape_string($conn, $_POST['keluhan'] ?? '');
    $tindakan  = mysqli_real_escape_string($conn, $_POST['tindakan'] ?? '');
    $istirahat = isset($_POST['istirahat']) ? (int)$_POST['istirahat'] : 0;
    $status_kunjungan = $_POST['status_kunjungan'] ?? '';

    // diagnosa dari dropdown:
    // - kalau tabel diagnosa ada: value = id_diagnosa
    // - kalau tidak ada: value = teks diagnosa
    $diagnosa_post = $_POST['diagnosa'] ?? '';

    if (!in_array($status_kunjungan, ['pertama', 'followup'], true)) {
        $error = "Status kunjungan harus dipilih.";
    } else {

        // ===================== UPDATE KUNJUNGAN =====================
        // KASUS 1: tabel diagnosa belum ada => simpan ke kolom 'diagnosa' (TEXT) seperti sekarang
        // KASUS 2: tabel diagnosa ada:
        //    - kalau tabel kunjungan kamu punya kolom 'id_diagnosa', kamu bisa simpan id nya
        //    - kalau belum, tetap simpan teksnya ke 'diagnosa'
        $kolom_id_diagnosa_ada = false;
        $cekKol = mysqli_query($conn, "SHOW COLUMNS FROM kunjungan LIKE 'id_diagnosa'");
        if ($cekKol && mysqli_num_rows($cekKol) > 0) $kolom_id_diagnosa_ada = true;

        $diagnosa_text_to_save = '';
        $id_diagnosa_to_save = 0;

        if ($use_diagnosa_table) {
            // value post = id
            $id_diagnosa_to_save = (int)$diagnosa_post;

            // cari text diagnosa-nya untuk fallback / display
            foreach ($diagnosa_options as $d) {
                if ((int)$d['id_diagnosa'] === $id_diagnosa_to_save) {
                    $diagnosa_text_to_save = $d['nama_diagnosa'];
                    break;
                }
            }
        } else {
            // value post = text
            $diagnosa_text_to_save = trim((string)$diagnosa_post);
        }

        // susun query update sesuai kolom yang ada
        if ($use_diagnosa_table && $kolom_id_diagnosa_ada) {
            // simpan id diagnosa
            $updateSql = "UPDATE kunjungan SET 
                id_pasien='$id_pasien',
                keluhan='$keluhan',
                id_diagnosa=$id_diagnosa_to_save,
                tindakan='$tindakan',
                istirahat=$istirahat,
                status_kunjungan='$status_kunjungan'
                WHERE id_kunjungan=$id_kunjungan";
        } else {
            // simpan diagnosa text ke kolom diagnosa (model lama kamu)
            $diagnosa_safe = mysqli_real_escape_string($conn, $diagnosa_text_to_save);
            $updateSql = "UPDATE kunjungan SET 
                id_pasien='$id_pasien',
                keluhan='$keluhan',
                diagnosa='$diagnosa_safe',
                tindakan='$tindakan',
                istirahat=$istirahat,
                status_kunjungan='$status_kunjungan'
                WHERE id_kunjungan=$id_kunjungan";
        }

        $update = mysqli_query($conn, $updateSql);

        if (!$update) {
            $error = "Gagal memperbarui data kunjungan: " . mysqli_error($conn);
        } else {

            // ===================== RESEP & STOK =====================
            $obat_baru   = $_POST['obat'] ?? [];
            $jumlah_baru = $_POST['jumlah'] ?? [];
            $dosis_baru  = $_POST['dosis'] ?? [];

            // 1) Kembalikan stok lama & hapus log keluar lama
            foreach ($resep_lama as $kode_obat => $r) {
                $kode_obat_safe = mysqli_real_escape_string($conn, $kode_obat);
                $jumlah_lama = (int)$r['jumlah'];

                mysqli_query($conn, "UPDATE obat SET stok = stok + $jumlah_lama WHERE kode_obat = '$kode_obat_safe'");
                mysqli_query($conn, "DELETE FROM stok_log WHERE kode_obat = '$kode_obat_safe' AND tipe='keluar' AND id_kunjungan = $id_kunjungan");
            }

            // 2) Hapus resep lama
            mysqli_query($conn, "DELETE FROM resep WHERE id_kunjungan = $id_kunjungan");

            // 3) Insert resep baru + update stok + log
            foreach ($obat_baru as $i => $kode_obat) {
                $kode_obat = trim($kode_obat);
                $jumlah = isset($jumlah_baru[$i]) ? (int)$jumlah_baru[$i] : 0;
                $dosis  = isset($dosis_baru[$i]) ? trim($dosis_baru[$i]) : '';

                if ($kode_obat === '' || $jumlah <= 0 || $dosis === '') continue;

                $kode_obat_safe = mysqli_real_escape_string($conn, $kode_obat);
                $dosis_safe = mysqli_real_escape_string($conn, $dosis);

                mysqli_query($conn, "INSERT INTO resep (id_kunjungan, kode_obat, dosis, jumlah)
                                     VALUES ($id_kunjungan, '$kode_obat_safe', '$dosis_safe', $jumlah)");

                mysqli_query($conn, "UPDATE obat SET stok = stok - $jumlah WHERE kode_obat = '$kode_obat_safe'");

                mysqli_query($conn, "INSERT INTO stok_log (kode_obat, tipe, jumlah, tanggal, id_kunjungan)
                                     VALUES ('$kode_obat_safe', 'keluar', $jumlah, NOW(), $id_kunjungan)");
            }

            $success = "Data kunjungan berhasil diperbarui.";

            // refresh data setelah update biar form ikut update
            $q2 = mysqli_query($conn, "SELECT * FROM kunjungan WHERE id_kunjungan = $id_kunjungan");
            $data = mysqli_fetch_assoc($q2);

            // refresh resep lama setelah update
            $resep_lama_res2 = mysqli_query($conn, "SELECT kode_obat, dosis, jumlah FROM resep WHERE id_kunjungan = $id_kunjungan");
            $resep_lama = [];
            while ($r = mysqli_fetch_assoc($resep_lama_res2)) {
                $resep_lama[$r['kode_obat']] = [
                    'jumlah' => (int)$r['jumlah'],
                    'dosis' => $r['dosis']
                ];
            }
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
                <option value="<?= htmlspecialchars($p['id_pasien']) ?>" <?= ($p['id_pasien'] == $data['id_pasien']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Status Kunjungan</label><br>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_kunjungan" value="pertama" <?= ($data['status_kunjungan'] ?? '') == 'pertama' ? 'checked' : '' ?>> Pertama
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="status_kunjungan" value="followup" <?= ($data['status_kunjungan'] ?? '') == 'followup' ? 'checked' : '' ?>> Follow-Up
        </div>
    </div>

    <div class="mb-3">
        <label>Keluhan</label>
        <textarea name="keluhan" class="form-control" required><?= htmlspecialchars($data['keluhan'] ?? '') ?></textarea>
    </div>

    <!-- DIAGNOSA DROPDOWN -->
    <div class="mb-3">
        <label>Diagnosa</label>

        <?php if ($diagnosa_error): ?>
            <div class="alert alert-warning">
                Diagnosa dari tabel tidak bisa dimuat: <?= htmlspecialchars($diagnosa_error) ?>
            </div>
        <?php endif; ?>

        <select name="diagnosa" class="form-select" required>
            <option value="">-- Pilih Diagnosa --</option>

            <?php if ($use_diagnosa_table && !empty($diagnosa_options)): ?>
                <?php
                    // nilai selected:
                    // - kalau punya kolom id_diagnosa: pakai $data['id_diagnosa']
                    // - kalau tidak: fallback pakai $data['diagnosa'] text (cari yang match)
                    $selected_id = isset($data['id_diagnosa']) ? (int)$data['id_diagnosa'] : 0;
                    $selected_text = $data['diagnosa'] ?? '';
                ?>
                <?php foreach ($diagnosa_options as $d): ?>
                    <?php
                        $optId = (int)$d['id_diagnosa'];
                        $optText = $d['nama_diagnosa'];
                        $sel = ($selected_id > 0 && $optId === $selected_id) || ($selected_id === 0 && $selected_text === $optText);
                    ?>
                    <option value="<?= $optId ?>" <?= $sel ? 'selected' : '' ?>>
                        <?= htmlspecialchars($optText) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <?php foreach ($diagnosa_manual as $txt): ?>
                    <option value="<?= htmlspecialchars($txt) ?>" <?= (($data['diagnosa'] ?? '') === $txt) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($txt) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Tindakan</label>
        <textarea name="tindakan" class="form-control" required><?= htmlspecialchars($data['tindakan'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label>Istirahat (hari)</label>
        <input type="number" name="istirahat" class="form-control" min="0" required value="<?= (int)($data['istirahat'] ?? 0) ?>">
    </div>

    <div class="mb-3">
        <label>Resep Obat</label>
        <div id="resep-container">
            <?php
            $obat_list = mysqli_query($conn, "SELECT kode_obat, nama_obat FROM obat ORDER BY nama_obat ASC");
            if (!$obat_list) die("Query obat gagal: " . mysqli_error($conn));
            $obat_arr = [];
            while ($o = mysqli_fetch_assoc($obat_list)) {
                $obat_arr[$o['kode_obat']] = $o['nama_obat'];
            }

            // kalau tidak ada resep lama, tampilkan 1 baris kosong
            if (empty($resep_lama)) {
                $resep_lama = ['' => ['dosis' => '', 'jumlah' => 1]];
            }

            foreach ($resep_lama as $kode_obat => $r):
            ?>
            <div class="row mb-2 resep-item">
                <div class="col-md-4">
                    <select name="obat[]" class="form-select" required>
                        <option value="">-- Pilih Obat --</option>
                        <?php foreach ($obat_arr as $ko => $nama): ?>
                            <option value="<?= htmlspecialchars($ko) ?>" <?= ($ko == $kode_obat) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama) ?>
                            </option>
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
        options += '<option value="<?= addslashes($ko) ?>"><?= addslashes($nama) ?></option>';
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
        const items = document.querySelectorAll('#resep-container .resep-item');
        // biar minimal 1 baris tetap ada
        if (items.length > 1) {
            e.target.closest('.resep-item').remove();
        }
    }
});
</script>
</body>
</html>
