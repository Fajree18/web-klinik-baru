<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: ../index.php");
    exit;
}
include "../koneksi.php";


$pasien = mysqli_query($conn, "SELECT id_pasien, nama, jabatan FROM pasien ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Histori Kunjungan Follow-Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body class="container mt-4">

<h4>Cek Riwayat Kunjungan Pasien</h4>


<div class="row mb-3">
    <div class="col-md-6">
        <label class="form-label">Scan Barcode (Format: NIK|Nama|Jabatan)</label>
        <input type="text" id="barcode_input" class="form-control" oninput="cariDariBarcode(this.value)" placeholder="" autofocus>
    </div>

    <div class="col-md-6">
        <label class="form-label">Atau Pilih Pasien Manual</label>
        <select id="select_pasien" class="form-select" onchange="cariDariDropdown(this.value)">
            <option value="">-- Pilih Pasien --</option>
            <?php while($row = mysqli_fetch_assoc($pasien)): ?>
                <option value="<?= $row['id_pasien'] ?>"><?= htmlspecialchars($row['nama']) ?> (<?= $row['id_pasien'] ?> | <?= $row['jabatan'] ?>)</option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<div id="hasil_histori"></div>


<div class="mt-4">
    <a href="../dashboard.php" class="btn btn-secondary">Kembali ke Dashboard</a>
</div>

<script>
function cariDariBarcode(barcode) {
    if (barcode.length > 5) {
        const nik = barcode.split('|')[0].trim(); 
        kirimPermintaan(nik);
    }
}

function cariDariDropdown(id_pasien) {
    if (id_pasien !== "") {
        kirimPermintaan(id_pasien);
    } else {
        document.getElementById("hasil_histori").innerHTML = "";
    }
}

function kirimPermintaan(id_pasien) {
    $.ajax({
        url: "ajax_histori_followup.php",
        method: "POST",
        data: { id_pasien: id_pasien },
        success: function(response) {
            document.getElementById("hasil_histori").innerHTML = response;
        },
        error: function() {
            document.getElementById("hasil_histori").innerHTML = "<div class='alert alert-danger'>Gagal mengambil data.</div>";
        }
    });
}
</script>

</body>
</html>