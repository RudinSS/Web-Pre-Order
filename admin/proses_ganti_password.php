<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}
require_once '../includes/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];

    // Validasi dasar
    if (empty($new_password) || $user_id === 0) {
        die("Data tidak lengkap.");
    }

    // Hash password baru sebelum disimpan
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update password di database
    $sql = "UPDATE users SET password = ? WHERE id = ? AND role = 'customer'";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        // Jika berhasil, arahkan kembali dengan pesan sukses
        header("Location: ganti_password_user.php?id=" . $user_id . "&status=sukses");
    } else {
        // Jika gagal (misal user tidak ditemukan)
        header("Location: admin_pengguna.php?status=gagal");
    }
    mysqli_stmt_close($stmt);
    exit;
}
?>