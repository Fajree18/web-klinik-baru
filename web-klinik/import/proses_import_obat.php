<?php
include "../koneksi.php";

if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
    $file = $_FILES['file_csv']['tmp_name'];

    $handle = fopen($file, 'r');
    if ($handle === false) {
        die("Gagal membuka file.");
    }

    $baris = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
   
        if ($baris == 0) {
            $baris++;
            continue;
        }

        $kode_obat = mysqli_real_escape_string($conn, trim($data[0]));
        $nama_obat = mysqli_real_escape_string($conn, trim($data[1]));
        $satuan = mysqli_real_escape_string($conn, trim($data[2]));  
        $stok = (int)trim($data[3]);                           

        if ($kode_obat && $nama_obat && $satuan && $stok >= 0) {
         
            $cek = mysqli_query($conn, "SELECT * FROM obat WHERE kode_obat = '$kode_obat'");
            if (mysqli_num_rows($cek) > 0) {
                
                $row = mysqli_fetch_assoc($cek);
                $stok_baru = $row['stok'] + $stok;
                mysqli_query($conn, "UPDATE obat SET stok = $stok_baru, nama_obat = '$nama_obat', satuan = '$satuan' WHERE kode_obat = '$kode_obat'");
            } else {
               
                mysqli_query($conn, "INSERT INTO obat (kode_obat, nama_obat, satuan, stok) VALUES ('$kode_obat', '$nama_obat', '$satuan', $stok)");
            }
        }
        $baris++;
    }
    fclose($handle);

    echo "Import data obat berhasil.";
} else {
    echo "File CSV belum diupload atau terjadi kesalahan.";
}
?>
