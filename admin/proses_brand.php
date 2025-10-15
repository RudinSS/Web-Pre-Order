<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
require_once '../includes/koneksi.php';

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';
$pesan = "Aksi tidak dikenal.";

if ($aksi == 'tambah' && !empty($_POST['brand_name'])) {
    $brand_name = $_POST['brand_name'];
    $sql = "INSERT INTO brands (brand_name) VALUES (?)";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "s", $brand_name);
    if(mysqli_stmt_execute($stmt)) $pesan = "Brand baru berhasil ditambahkan.";
    else $pesan = "Gagal menambahkan brand.";
}
elseif ($aksi == 'ubah' && !empty($_POST['id']) && !empty($_POST['brand_name'])) {
    $id = (int)$_POST['id'];
    $brand_name = $_POST['brand_name'];
    $sql = "UPDATE brands SET brand_name = ? WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "si", $brand_name, $id);
    if(mysqli_stmt_execute($stmt)) $pesan = "Brand berhasil diperbarui.";
    else $pesan = "Gagal memperbarui brand.";
}
elseif ($aksi == 'hapus' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM brands WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    if(mysqli_stmt_execute($stmt)) $pesan = "Brand berhasil dihapus.";
    else $pesan = "Gagal menghapus brand.";
}

header("Location: admin_brand.php?pesan=" . urlencode($pesan));
exit;
?>