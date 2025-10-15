<?php
date_default_timezone_set('Asia/Jakarta');


// Pengaturan untuk koneksi database
$host       = "localhost";
$user       = "root"; // User default untuk XAMPP
$pass       = "";     // Password default untuk XAMPP (kosong)
$db_name    = "db_preorder1";

// Membuat koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db_name);

// Cek koneksi
// Jika gagal, hentikan script dan tampilkan pesan error
if (!$koneksi) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// 2. PERBAIKAN UTAMA: Atur zona waktu untuk koneksi database ini
mysqli_query($koneksi, "SET time_zone = '+07:00'");

// Jika berhasil, script akan lanjut berjalan.
// Sebaiknya tidak ada output apa pun di file ini jika koneksi berhasil.
?>