CREATE DATABASE IF NOT EXISTS klinik;
USE klinik;

CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);
INSERT INTO admin (username, password) VALUES ('admin', MD5('admin123'));

CREATE TABLE pasien (
    id_pasien INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    departemen VARCHAR(100),
    umur INT,
    jenis_kelamin ENUM('Laki-laki','Perempuan'),
    alamat TEXT,
    riwayat_sakit TEXT,
    telepon VARCHAR(20),
    created_at DATETIME
);

CREATE TABLE kunjungan (
    id_kunjungan INT AUTO_INCREMENT PRIMARY KEY,
    id_pasien INT,
    tanggal_kunjungan DATETIME,
    keluhan TEXT,
    diagnosa TEXT,
    tindakan TEXT,
    FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien)
);

CREATE TABLE obat (
    id_obat INT AUTO_INCREMENT PRIMARY KEY,
    nama_obat VARCHAR(100),
    stok INT,
    deskripsi TEXT
);

CREATE TABLE resep (
    id_resep INT AUTO_INCREMENT PRIMARY KEY,
    id_kunjungan INT,
    id_obat INT,
    jumlah INT,
    FOREIGN KEY (id_kunjungan) REFERENCES kunjungan(id_kunjungan),
    FOREIGN KEY (id_obat) REFERENCES obat(id_obat)
);