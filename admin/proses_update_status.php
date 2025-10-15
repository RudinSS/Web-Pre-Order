<?php
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

/**
 * Kirim request ke WordPress untuk menambah/mengurangi poin
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

/**
 * Kirim request ke API Jubelio
 */
function callJubelioAPI($method, $url, $token, $payload = null) {
    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json']
    ];
    if ($payload) {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http_code' => $http_code, 'result' => $result];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    $pesan = "Aksi tidak valid.";

    mysqli_begin_transaction($koneksi);
    try {
        $sql_order_details = "SELECT * FROM pre_orders WHERE id = ?";
        $stmt_details = mysqli_prepare($koneksi, $sql_order_details);
        mysqli_stmt_bind_param($stmt_details, "i", $order_id);
        mysqli_stmt_execute($stmt_details);
        $order_details = mysqli_stmt_get_result($stmt_details)->fetch_assoc();
        mysqli_stmt_close($stmt_details);

        if (!$order_details) throw new Exception("Order tidak ditemukan.");
        
        $old_status = $order_details['status'];
        
        if ($new_status === 'confirmed' && $old_status === 'pending') {
            
            if (!empty($order_details['jubelio_so_id'])) {
                throw new Exception("Pesanan ini sudah ada di Jubelio dengan No. Order: " . htmlspecialchars($order_details['jubelio_so_id']));
            }

            if (empty($_SESSION['jubelio_token'])) {
                throw new Exception("Token Jubelio tidak ditemukan. Silakan logout dan login kembali sebagai admin.");
            }
            $jubelioApiToken = $_SESSION['jubelio_token'];

            $sql_items = "SELECT p.sku, p.jubelio_item_id, p.model_name, oi.quantity, oi.price FROM pre_order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "i", $order_id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);
            
            $items_for_api = [];
            $missing_jubelio_id_skus = [];
            $sub_total_manual = 0;
            
            while ($item = mysqli_fetch_assoc($result_items)) {
                if (empty($item['jubelio_item_id'])) {
                    $missing_jubelio_id_skus[] = $item['sku'];
                }
                $item_amount = (float)$item['price'] * (int)$item['quantity'];
                $sub_total_manual += $item_amount;
                $items_for_api[] = [ 
                    "salesorder_detail_id" => 0,
                    "item_id" => (int)$item['jubelio_item_id'], 
                    "description" => $item['model_name'], 
                    "tax_id" => 1,
                    "price" => (float)$item['price'], 
                    "unit" => "Pcs",
                    "qty_in_base" => (int)$item['quantity'], 
                    "disc" => 0,
                    "disc_amount" => 0,
                    "tax_amount" => 0,
                    "amount" => $item_amount,
                    "location_id" => -1
                ];
            }
            mysqli_stmt_close($stmt_items);

            if (!empty($missing_jubelio_id_skus)) {
                throw new Exception("Gagal: Produk dengan SKU berikut tidak memiliki Jubelio ID: " . implode(', ', $missing_jubelio_id_skus) . ". Harap sinkronkan dari Jubelio.");
            }
            
            // --- PERBAIKAN UTAMA: Mengembalikan salesorder_no ke [auto] ---
            $payload = [
                "salesorder_id" => 0,
                "salesorder_no" => "[auto]", // DIKEMBALIKAN SESUAI PERMINTAAN
                "contact_id" => -9,
                "customer_name" => $order_details['shipping_name'],
                "transaction_date" => date('c'),
                "is_tax_included" => false,
                "note" => $order_details['note'] ?? '',
                "sub_total" => $sub_total_manual,
                "total_disc" => 0,
                "total_tax" => 0,
                "grand_total" => (float)$order_details['total_amount'],
                "location_id" => -1,
                "source" => 1,
                "channel_status" => "Pending",
                "is_paid" => false,
                "shipping_cost" => 0,
                "insurance_cost" => 0,
                "add_disc" => 0,
                "add_fee" => 0,
                "service_fee" => 0,
                "shipping_full_name" => $order_details['shipping_name'],
                "shipping_phone" => $order_details['shipping_phone'],
                "shipping_address" => $order_details['shipping_address'],
                "shipping_area" => $order_details['shipping_area'] ?? '',
                "shipping_city" => $order_details['shipping_city'] ?? '',
                "shipping_subdistrict" => $order_details['shipping_subdistrict'] ?? '',
                "shipping_province" => $order_details['shipping_province'] ?? '',
                "shipping_post_code" => $order_details['shipping_post_code'] ?? '',
                "shipping_country" => "Indonesia",
                "items" => $items_for_api
            ];
            
            $response = callJubelioAPI('POST', 'https://api2.jubelio.com/sales/orders/', $jubelioApiToken, $payload);
            
            if ($response['http_code'] < 200 || $response['http_code'] >= 300) {
                $error_message = "Gagal membuat SO di Jubelio. ";
                $api_response_data = json_decode($response['result'], true);
                if (isset($api_response_data['message'])) {
                    $error_message .= "Pesan dari Jubelio: " . $api_response_data['message'];
                } else {
                    $error_message .= "Respon mentah: " . $response['result'];
                }
                throw new Exception($error_message);
            }
            
            $api_response_data = json_decode($response['result'], true);
            
            $jubelio_id_from_api = $api_response_data['id'] ?? null;
            $jubelio_so_no_from_api = $api_response_data['data']['salesorder_no'] ?? null;

            if (!$jubelio_id_from_api || !$jubelio_so_no_from_api) {
                // Logika fallback jika 'data' tidak ada, tapi 'id' ada
                 if($jubelio_id_from_api && !$jubelio_so_no_from_api){
                    $jubelio_so_no_from_api = 'SO-0000' . $jubelio_id_from_api; // Buat nomor SO sementara
                 } else {
                    throw new Exception("Gagal membuat pesanan di Jubelio. Respon tidak sesuai: " . $response['result']);
                 }
            }

            $sql_update_final = "UPDATE pre_orders SET status = ?, jubelio_so_id = ?, jubelio_salesorder_id = ? WHERE id = ?";
            $stmt_final = mysqli_prepare($koneksi, $sql_update_final);
            mysqli_stmt_bind_param($stmt_final, "ssii", $new_status, $jubelio_so_no_from_api, $jubelio_id_from_api, $order_id);
            mysqli_stmt_execute($stmt_final);
            mysqli_stmt_close($stmt_final);
            $pesan = "Pesanan berhasil dikonfirmasi dan dikirim ke Jubelio (No. " . htmlspecialchars($jubelio_so_no_from_api) . ")";
        
        } else {
            // Logika untuk status lain
            $sql_update_status = "UPDATE pre_orders SET status = ? WHERE id = ?";
            $stmt_status = mysqli_prepare($koneksi, $sql_update_status);
            mysqli_stmt_bind_param($stmt_status, "si", $new_status, $order_id);
            mysqli_stmt_execute($stmt_status);
            mysqli_stmt_close($stmt_status);
            $pesan = "Status pesanan #" . $order_id . " berhasil diperbarui menjadi " . ucfirst($new_status);
        }
        
        mysqli_commit($koneksi);
        
        // Logika sinkronisasi poin (tidak berubah)
        $sync_data = ['customer_id' => $order_details['customer_id'], 'total_amount' => $order_details['total_amount'], 'order_id' => $order_id];
        $sync_status_to_db = null;
        if ($new_status === 'completed' && $old_status !== 'completed') {
            $sync_result = syncPointsToWooCommerce('add', $sync_data, $koneksi);
            if (isset($sync_result['success']) && $sync_result['success'] === true) {
                $pesan .= " | ✅ Poin berhasil ditambahkan.";
                $sync_status_to_db = 'sukses';
            } else {
                $error_msg = $sync_result['error'] ?? 'Unknown error';
                $pesan .= " | ⚠️ Gagal menambah poin: " . htmlspecialchars($error_msg);
                $sync_status_to_db = 'gagal';
            }
        } 
        elseif ($new_status === 'canceled' && $old_status === 'completed') {
            $sync_result = syncPointsToWooCommerce('deduct', $sync_data, $koneksi);
            if (isset($sync_result['success']) && $sync_result['success'] === true) {
                $pesan .= " | ✅ Poin berhasil dikurangi.";
                $sync_status_to_db = 'dibatalkan';
            }
        }
        
        if ($sync_status_to_db) {
            $sql_update_sync = "UPDATE pre_orders SET point_sync_status = ? WHERE id = ?";
            $stmt_sync = mysqli_prepare($koneksi, $sql_update_sync);
            mysqli_stmt_bind_param($stmt_sync, "si", $sync_status_to_db, $order_id);
            mysqli_stmt_execute($stmt_sync);
            mysqli_stmt_close($stmt_sync);
        }
        
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $pesan = "⚠️ Gagal! Error: " . $e->getMessage();
    }
    header("Location: admin_po_masuk.php?pesan=" . urlencode($pesan));
    exit;
}
?>