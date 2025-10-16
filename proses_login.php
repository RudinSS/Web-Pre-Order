<?php

session_start();
require_once 'includes/koneksi.php';
require_once 'includes/secrets.php'; 

$tipe_login = $_POST['tipe_login'] ?? '';

// =======================================================
// ALUR LOGIN UNTUK ADMIN VIA JUBELIO
// =======================================================
if ($tipe_login === 'admin_jubelio') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $loginEndpoint = 'https://api2.jubelio.com/login';
    $loginData = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $response = json_decode($result, true);

    if ($http_code === 200 && isset($response['token'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $response['userName'];
        $_SESSION['role'] = 'admin';
        $_SESSION['jubelio_token'] = $response['token'];

        header("Location: dashboard.php");
        exit;
    } else {
        header("Location: index.php?pesan=gagal_admin");
        exit;
    }
}
// =======================================================
// ALUR LOGIN CUSTOMER BERBASIS EMAIL
// =======================================================
elseif ($tipe_login === 'customer') {
    $email = $_POST['username']; // Input dari form adalah email
    $password = $_POST['password'];

    // --- Langkah 1: Cek Database Lokal menggunakan EMAIL ---
    $sql_local = "SELECT * FROM users WHERE email = ?";
    $stmt_local = mysqli_prepare($koneksi, $sql_local);
    mysqli_stmt_bind_param($stmt_local, "s", $email);
    mysqli_stmt_execute($stmt_local);
    $result_local = mysqli_stmt_get_result($stmt_local);
    $local_user = mysqli_fetch_assoc($result_local);
    mysqli_stmt_close($stmt_local);
    
    if ($local_user && !empty($local_user['password']) && password_verify($password, $local_user['password'])) {
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $local_user['id'];
        $_SESSION['username'] = $local_user['username'];
        $_SESSION['full_name'] = $local_user['full_name'];
        $_SESSION['role'] = $local_user['role'];
        header("Location: dashboard.php");
        exit;
    }

    // --- Langkah 2: Cek ke WordPress via REST API menggunakan EMAIL ---
    $wp_api_login_url = 'https://order.rumahmadani.com/wp-json/jwt-auth/v1/token';
    
    $ch_wp = curl_init();
    curl_setopt($ch_wp, CURLOPT_URL, $wp_api_login_url);
    curl_setopt($ch_wp, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch_wp, CURLOPT_POST, 1);
    curl_setopt($ch_wp, CURLOPT_POSTFIELDS, http_build_query(['username' => $email, 'password' => $password]));
    curl_setopt($ch_wp, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result_wp = curl_exec($ch_wp);
    $http_code_wp = curl_getinfo($ch_wp, CURLINFO_HTTP_CODE);
    curl_close($ch_wp);
    $response_wp = json_decode($result_wp, true);

    if ($http_code_wp === 200 && isset($response_wp['token'])) {
        $wp_username = $response_wp['user_display_name'];
        $wp_display_name = $response_wp['user_display_name'];
        $wp_email = $response_wp['user_email'];

        $sql_check_wp = "SELECT * FROM users WHERE email = ?";
        $stmt_check_wp = mysqli_prepare($koneksi, $sql_check_wp);
        mysqli_stmt_bind_param($stmt_check_wp, "s", $wp_email);
        mysqli_stmt_execute($stmt_check_wp);
        $existing_user = mysqli_stmt_get_result($stmt_check_wp)->fetch_assoc();
        
        $placeholder_password = password_hash(bin2hex(random_bytes(20)), PASSWORD_DEFAULT); 
        
        if ($existing_user) {
            // HANYA UPDATE username, JANGAN full_name
            $sql_update = "UPDATE users SET username = ?, source = 'order.rumahmadani.com', password = ? WHERE id = ?";
            $stmt_update = mysqli_prepare($koneksi, $sql_update);
            mysqli_stmt_bind_param($stmt_update, "ssi", $wp_username, $placeholder_password, $existing_user['id']);
            mysqli_stmt_execute($stmt_update);
            $_SESSION['user_id'] = $existing_user['id'];
            $_SESSION['full_name'] = $existing_user['full_name']; // Ambil nama dari data yang sudah ada
        } else {
            // Jika pengguna baru, 'full_name' dan 'username' diisi dari WordPress
            $sql_insert = "INSERT INTO users (username, email, password, full_name, role, source) VALUES (?, ?, ?, ?, 'customer', 'order.rumahmadani.com')";
            $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
            mysqli_stmt_bind_param($stmt_insert, "ssss", $wp_username, $wp_email, $placeholder_password, $wp_display_name);
            mysqli_stmt_execute($stmt_insert);
            $_SESSION['user_id'] = mysqli_insert_id($koneksi);
            $_SESSION['full_name'] = $wp_display_name;
        }
        
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $wp_username;
        $_SESSION['role'] = 'customer';
        
        header("Location: dashboard.php");
        exit;
    }
    
    header("Location: index.php?pesan=gagal_customer");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>