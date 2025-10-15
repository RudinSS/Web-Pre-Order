<?php

echo "Mencoba menghubungkan ke database...<br>";

// Memanggil file koneksi yang sudah kita buat
require_once 'includes/koneksi.php';

// Jika script berhasil sampai di sini tanpa error, artinya koneksi berhasil.
// Fungsi die() di dalam koneksi.php akan menghentikan script jika gagal.

echo "<strong>SELAMAT!</strong> Koneksi ke database BERHASIL!";

?>