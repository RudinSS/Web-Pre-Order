<?php
require_once 'includes/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $password = $_POST['password'];
    
    // Username diisi dengan Nama Lengkap
    $username = $full_name; 

    // Validasi dasar
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password)) {
        header("Location: register.php?pesan=gagal");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?pesan=gagal");
        exit;
    }
    if (strlen($password) < 6) {
        header("Location: register.php?pesan=password_pendek");
        exit;
    }

    // Cek duplikasi berdasarkan EMAIL
    $sql_check = "SELECT id FROM users WHERE email = ?";
    if ($stmt_check = mysqli_prepare($koneksi, $sql_check)) {
        mysqli_stmt_bind_param($stmt_check, "s", $email);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);
        
        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            header("Location: register.php?pesan=email_sudah_ada");
            exit;
        }
        mysqli_stmt_close($stmt_check);
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Query INSERT yang sudah diperbaiki
    $sql_insert = "INSERT INTO users (full_name, email, phone_number, username, password, role, source) VALUES (?, ?, ?, ?, ?, 'customer', 'Akun PO')";
    
    if ($stmt_insert = mysqli_prepare($koneksi, $sql_insert)) {
        mysqli_stmt_bind_param($stmt_insert, "sssss", $full_name, $email, $phone_number, $username, $hashed_password);
        
        if (mysqli_stmt_execute($stmt_insert)) {
            header("Location: index.php?pesan=registrasi_sukses");
            exit;
        }
    }

    header("Location: register.php?pesan=gagal");
    exit;

} else {
    header("Location: register.php");
    exit;
}
?>