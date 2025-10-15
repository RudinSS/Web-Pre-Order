<?php
session_start();
require_once 'includes/koneksi.php';

// Keamanan: Pastikan hanya customer yang login yang bisa mengakses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'customer') {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $wp_password = $_POST['wp_password'];

    // Ambil email user dari database lokal
    $sql_user = "SELECT email FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($koneksi, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $user_id);
    mysqli_stmt_execute($stmt_user);
    $user_data = mysqli_stmt_get_result($stmt_user)->fetch_assoc();
    $email = $user_data['email'];

    if (!$email || empty($wp_password)) {
        header("Location: dashboard.php?status=hubung_gagal");
        exit;
    }

    // Coba login ke WordPress via REST API untuk verifikasi
    $wp_api_login_url = 'https://order.rumahmadani.com/wp-json/jwt-auth/v1/token';
    
    $ch_wp = curl_init();
    curl_setopt($ch_wp, CURLOPT_URL, $wp_api_login_url);
    curl_setopt($ch_wp, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch_wp, CURLOPT_POST, 1);
    curl_setopt($ch_wp, CURLOPT_POSTFIELDS, http_build_query(['username' => $email, 'password' => $wp_password]));
    curl_setopt($ch_wp, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result_wp = curl_exec($ch_wp);
    $http_code_wp = curl_getinfo($ch_wp, CURLINFO_HTTP_CODE);
    curl_close($ch_wp);
    $response_wp = json_decode($result_wp, true);

    // Jika login/verifikasi berhasil (HTTP code 200)
    if ($http_code_wp === 200 && isset($response_wp['token'])) {
        // Update 'source' di database lokal menjadi terhubung
        $sql_update = "UPDATE users SET source = 'order.rumahmadani.com' WHERE id = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "i", $user_id);
        mysqli_stmt_execute($stmt_update);
        
        // Arahkan kembali ke dashboard dengan pesan sukses
        header("Location: dashboard.php?status=hubung_sukses");
        exit;
    } else {
        // Jika password salah atau akun tidak ditemukan, kembali dengan pesan gagal
        header("Location: dashboard.php?status=hubung_gagal");
        exit;
    }
}
?>