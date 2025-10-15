<?php
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/secrets.php'; 

// Keamanan: Pastikan hanya admin yang login
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

/**
 * Fungsi ini disalin dari proses_update_status.php untuk digunakan di sini.
 * Mengirim request ke WordPress untuk menambah/mengurangi poin.
 */
function syncPointsToWooCommerce($action, $order_data, $koneksi) {
    $woocommerce_url = WP_BASE_URL;
    $secret_key = WC_POINT_SECRET_KEY;

    
    $endpoint = ($action === 'add') 
        ? $woocommerce_url . '/wp-json/po-integration/v1/add-points' 
        : $woocommerce_url . '/wp-json/po-integration/v1/deduct-points';
    
    $customer_id = $order_data['customer_id'];
    $sql_user_email = "SELECT email FROM users WHERE id = ?";
    $stmt_user_email = mysqli_prepare($koneksi, $sql_user_email);
    mysqli_stmt_bind_param($stmt_user_email, "i", $customer_id);
    mysqli_stmt_execute($stmt_user_email);
    $user_data = mysqli_stmt_get_result($stmt_user_email)->fetch_assoc();
    mysqli_stmt_close($stmt_user_email);
    
    if (!$user_data || empty($user_data['email'])) {
        return ['success' => false, 'error' => 'Email customer tidak ditemukan di database PO.'];
    }
    
    $user_email = $user_data['email'];
    $payload = ['email' => $user_email, 'total_amount' => floatval($order_data['total_amount']), 'order_id' => $order_data['order_id']];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-Secret-Key: ' . $secret_key],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) { return ['success' => false, 'error' => 'CURL Error: ' . $curl_error]; }
    
    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    }
    
    return ['success' => false, 'http_code' => $http_code, 'error' => $response];
}


$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = "Aksi tidak valid.";

if ($order_id > 0) {
    try {
        // Ambil detail pesanan yang diperlukan
        $sql_order_details = "SELECT customer_id, total_amount, status FROM pre_orders WHERE id = ?";
        $stmt_details = mysqli_prepare($koneksi, $sql_order_details);
        mysqli_stmt_bind_param($stmt_details, "i", $order_id);
        mysqli_stmt_execute($stmt_details);
        $order_details = mysqli_stmt_get_result($stmt_details)->fetch_assoc();
        mysqli_stmt_close($stmt_details);

        if (!$order_details) {
            throw new Exception("Pesanan tidak ditemukan.");
        }
        
        if ($order_details['status'] !== 'completed') {
            throw new Exception("Hanya pesanan dengan status 'completed' yang dapat disinkronisasi ulang poinnya.");
        }
        
        // Siapkan data untuk fungsi sinkronisasi
        $sync_data = [
            'customer_id' => $order_details['customer_id'], 
            'total_amount' => $order_details['total_amount'], 
            'order_id' => $order_id
        ];
        
        // Panggil fungsi untuk MENAMBAH poin
        $sync_result = syncPointsToWooCommerce('add', $sync_data, $koneksi);
        
        if (isset($sync_result['success']) && $sync_result['success'] === true) {
            // --- PERUBAHAN UTAMA: UPDATE STATUS SINKRONISASI DI DATABASE ---
            $sql_update_sync = "UPDATE pre_orders SET point_sync_status = 'sukses' WHERE id = ?";
            $stmt_sync = mysqli_prepare($koneksi, $sql_update_sync);
            mysqli_stmt_bind_param($stmt_sync, "i", $order_id);
            mysqli_stmt_execute($stmt_sync);
            mysqli_stmt_close($stmt_sync);
            
            $pesan = "✅ Sinkronisasi ulang poin berhasil! Poin telah ditambahkan.";
        } else {
            $error_msg = $sync_result['error'] ?? 'Unknown error';
            // Tidak perlu update status jika gagal, agar tombol tetap muncul
            $pesan = "⚠️ Gagal melakukan sinkronisasi ulang. Error: " . htmlspecialchars($error_msg);
        }

    } catch (Exception $e) {
        $pesan = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Kembalikan ke halaman daftar PO dengan pesan notifikasi
header("Location: admin_po_masuk.php?pesan=" . urlencode($pesan));
exit;