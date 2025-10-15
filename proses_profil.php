<?php
session_start();
require_once 'includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'customer') {
    die("Akses ditolak.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION['user_id'];
    
    // Ambil data dari form
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];

    // Ambil data alamat
    $shipping_province = $_POST['shipping_province'] ?? null;
    $shipping_city = $_POST['shipping_city'] ?? null;
    $shipping_subdistrict = $_POST['shipping_subdistrict'] ?? null;
    $shipping_area = $_POST['shipping_area'] ?? null;
    $shipping_post_code = $_POST['shipping_post_code'] ?? null;
    $shipping_address = $_POST['shipping_address'] ?? null;

    // Validasi email unik sebelum update
    $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        header("Location: profil.php?error=" . urlencode("Gagal: Email sudah digunakan oleh akun lain."));
        exit;
    }

    // Bangun query SQL
    $sql_parts = ["full_name = ?", "email = ?", "phone_number = ?", "shipping_province = ?", "shipping_city = ?", "shipping_subdistrict = ?", "shipping_area = ?", "shipping_post_code = ?", "shipping_address = ?"];
    $params = [$full_name, $email, $phone_number, $shipping_province, $shipping_city, $shipping_subdistrict, $shipping_area, $shipping_post_code, $shipping_address];
    $types = "sssssssss";

    // Jika password diisi, tambahkan ke query update
    if (!empty($password)) {
        if (strlen($password) < 6) {
            header("Location: profil.php?error=" . urlencode("Gagal: Password baru minimal 6 karakter."));
            exit;
        }
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql_parts[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    $params[] = $user_id;
    $types .= "i";

    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = ?";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        header("Location: profil.php?pesan=" . urlencode("Profil berhasil diperbarui."));
    } else {
        header("Location: profil.php?error=" . urlencode("Gagal memperbarui profil."));
    }
    exit;
}
?>