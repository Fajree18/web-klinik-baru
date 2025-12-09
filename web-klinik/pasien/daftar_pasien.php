<?php
session_start();
include "../koneksi.php";
if (!isset($_SESSION['admin'])) header("Location: ../index.php");

// === AJAX SEARCH HANDLER ===
if (isset($_POST['keyword'])) {
    $keyword = mysqli_real_escape_string($conn, $_POST['keyword']);
    $query = mysqli_query($conn, "
        SELECT id_pasien, nama, jabatan 
        FROM pasien 
        WHERE nama LIKE '%$keyword%' OR id_pasien LIKE '%$keyword%' 
        ORDER BY nama ASC LIMIT 10
    ");
    if (mysqli_num_rows($query) == 0) {
        echo "<div class='p-2 text-muted'>Tidak ditemukan</div>";
    } else {
        while ($r = mysqli_fetch_assoc($query)) {
            echo "<div data-id='{$r['id_pasien']}' class='p-2 border-bottom hover-bg-light' style='cursor:pointer'>
                    <i class='bi bi-person-circle text-primary me-2'></i>
                    {$r['nama']} <small class='text-muted'>({$r['id_pasien']} | {$r['jabatan']})</small>
                  </div>";
        }
    }
    exit;
}

// === Normal load edit pasien ===
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1000;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            max-height: 260px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        #suggestions div:hover {
            background-color: #f6f6f6;
        }
        .hover-bg-light:hover {
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body class="container mt-4">

<h4 class="mb-4 fw-bold">Edit Data Pasien</h4>

<!-- 🔍 Kolom Pencarian Pasien -->
<div class="position-relative mb-4">
    <label class="form-label fw-semibold">Cari Pasien Lain</label>
    <input type="text" id="search_pasien" class="form-control" placeholder="Ketik nama atau NIK pasien..." autocomplete="off">
    <div id="suggestions" class="shadow-sm"></div>
</div>

<form method="POST" enctype="multipart/form-data">
    <div class="mb-2">
        <label>ID Pasien (NIK)</label>
        <input name="id_pasien" class="form-control" value="<?= htmlspecialchars($data['id_pasien']) ?>" required>
    </div>
    <div class="mb-2">
        <label>No. RM</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($data['no_rm']) ?>" readonly>
    </div>
    <div class="mb-2">
        <label>Nama</label>
        <input name="nama" class="form-control" value="<?= htmlspecialchars($data['nama']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Departemen</label>
        <select name="departemen" class="form-control" required>
            <option value="">-- Pilih Departemen --</option>
            <?php while ($d = mysqli_fetch_assoc($departemen_list)): ?>
                <option value="<?= htmlspecialchars($d['nama']) ?>" <?= $data['departemen'] == $d['nama'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nama']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Jabatan</label>
        <input name="jabatan" class="form-control" value="<?= htmlspecialchars($data['jabatan']) ?>" required>
    </div>
    <div class="mb-2">
        <label>Tanggal Lahir</label>
        <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($data['tanggal_lahir']) ?>" required>
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
        <textarea name="alamat" class="form-control"><?= htmlspecialchars($data['alamat']) ?></textarea>
    </div>
    <div class="mb-2">
        <label>Riwayat Sakit</label>
        <textarea name="riwayat_sakit" class="form-control"><?= htmlspecialchars($data['riwayat_sakit']) ?></textarea>
    </div>
    <div class="mb-2">
        <label>No. Telepon</label>
        <input name="telepon" class="form-control" value="<?= htmlspecialchars($data['telepon']) ?>">
    </div>

    <div class="mt-4">
        <button name="simpan" class="btn btn-success"><i class="bi bi-save"></i> Simpan Perubahan</button>
        <a href="daftar_pasien.php" class="btn btn-secondary ms-2"><i class="bi bi-arrow-left-circle"></i> Batal</a>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$(document).ready(function() {
    // AJAX Search
    $("#search_pasien").on("input", function() {
        let keyword = $(this).val().trim();
        if (keyword.length > 1) {
            $.ajax({
                url: "edit_pasien.php",
                method: "POST",
                data: { keyword: keyword },
                success: function(res) {
                    $("#suggestions").html(res).fadeIn(150);
                }
            });
        } else {
            $("#suggestions").fadeOut(150);
        }
    });

    // Klik suggestion
    $(document).on("click", "#suggestions div", function() {
        const id = $(this).data("id");
        window.location.href = "edit_pasien.php?id=" + encodeURIComponent(id);
    });

    // Klik di luar => hide
    $(document).on("click", function(e) {
        if (!$(e.target).closest("#search_pasien, #suggestions").length) {
            $("#suggestions").fadeOut(150);
        }
    });
});
</script>

</body>
</html>
