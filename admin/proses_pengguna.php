<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}
require_once '../includes/koneksi.php';

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// Logika untuk mengubah data pengguna
if ($aksi == 'ubah') {
    // Ambil data akun
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $full_name = $_POST['full_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';

    // Ambil data alamat
    $shipping_province = $_POST['shipping_province'] ?? null;
    $shipping_city = $_POST['shipping_city'] ?? null;
    $shipping_subdistrict = $_POST['shipping_subdistrict'] ?? null;
    $shipping_area = $_POST['shipping_area'] ?? null;
    $shipping_post_code = $_POST['shipping_post_code'] ?? null;
    $shipping_address = $_POST['shipping_address'] ?? null;
    
    if ($user_id > 0 && !empty($full_name) && !empty($username) && !empty($email)) {
        // Cek duplikasi email
        $sql_check = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt_check = mysqli_prepare($koneksi, $sql_check);
        mysqli_stmt_bind_param($stmt_check, "si", $email, $user_id);
        mysqli_stmt_execute($stmt_check);
        mysqli_stmt_store_result($stmt_check);

        if (mysqli_stmt_num_rows($stmt_check) > 0) {
            $pesan = "Gagal memperbarui: Email '" . htmlspecialchars($email) . "' sudah digunakan oleh pengguna lain.";
            header("Location: form_edit_pengguna.php?id=" . $user_id . "&error=" . urlencode($pesan));
            exit;
        } else {
            // --- PERBAIKAN: Query UPDATE diperluas untuk mencakup kolom alamat ---
            $sql = "UPDATE users SET 
                        full_name = ?, 
                        username = ?, 
                        email = ?, 
                        phone_number = ?,
                        shipping_province = ?,
                        shipping_city = ?,
                        shipping_subdistrict = ?,
                        shipping_area = ?,
                        shipping_post_code = ?,
                        shipping_address = ?
                    WHERE id = ? AND role = 'customer'";
            
            $stmt = mysqli_prepare($koneksi, $sql);
            // --- PERBAIKAN: bind_param disesuaikan dengan jumlah kolom baru ---
            mysqli_stmt_bind_param($stmt, "ssssssssssi", 
                $full_name, $username, $email, $phone_number,
                $shipping_province, $shipping_city, $shipping_subdistrict,
                $shipping_area, $shipping_post_code, $shipping_address,
                $user_id
            );
            mysqli_stmt_execute($stmt);
            $pesan = "Data pengguna berhasil diperbarui.";
            header("Location: admin_pengguna.php?pesan=" . urlencode($pesan));
            exit;
        }
    } else {
        $pesan = "Data tidak lengkap atau ID pengguna tidak valid.";
        if ($user_id > 0) {
            header("Location: form_edit_pengguna.php?id=" . $user_id . "&error=" . urlencode($pesan));
        } else {
            header("Location: admin_pengguna.php?pesan=" . urlencode($pesan));
        }
        exit;
    }
}
// Logika untuk hapus pengguna
elseif ($aksi == 'hapus') {
    // ... (kode untuk hapus pengguna tidak berubah) ...
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($user_id > 0) {
        $sql = "DELETE FROM users WHERE id = ? AND role = 'customer'";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
    }
    header("Location: admin_pengguna.php?pesan=" . urlencode("Pengguna berhasil dihapus."));
    exit;
}

header("Location: admin_pengguna.php");
exit;
?>