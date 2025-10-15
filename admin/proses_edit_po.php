<?php
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

// Ambil semua data dari POST
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$product_ids = $_POST['product_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];

// Data Alamat Baru
$shipping_name = $_POST['shipping_name'] ?? '';
$shipping_phone = $_POST['shipping_phone'] ?? '';
$shipping_address = $_POST['shipping_address'] ?? '';
$shipping_province = $_POST['shipping_province'] ?? '';
$shipping_city = $_POST['shipping_city'] ?? '';
$shipping_subdistrict = $_POST['shipping_subdistrict'] ?? '';
$shipping_area = $_POST['shipping_area'] ?? '';
$shipping_post_code = $_POST['shipping_post_code'] ?? '';


if ($order_id === 0) {
    die("ID Pesanan tidak valid.");
}

mysqli_begin_transaction($koneksi);

try {
    // 1. Kunci dan periksa status pesanan
    $sql_check = "SELECT status FROM pre_orders WHERE id = ? FOR UPDATE";
    $stmt_check = mysqli_prepare($koneksi, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $order_id);
    mysqli_stmt_execute($stmt_check);
    $order = mysqli_stmt_get_result($stmt_check)->fetch_assoc();

    if (!$order || $order['status'] !== 'pending') {
        throw new Exception("Pesanan tidak dapat diedit karena statusnya bukan lagi 'pending'.");
    }

    // 2. Hapus semua item lama dari pesanan
    $sql_delete = "DELETE FROM pre_order_items WHERE order_id = ?";
    $stmt_delete = mysqli_prepare($koneksi, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, "i", $order_id);
    mysqli_stmt_execute($stmt_delete);

    $grand_total = 0;

    // 3. Masukkan kembali item yang sudah diperbarui
    if (!empty($product_ids)) {
        $sql_insert = "INSERT INTO pre_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

        for ($i = 0; $i < count($product_ids); $i++) {
            $product_id = (int)$product_ids[$i];
            $quantity = (int)$quantities[$i];

            if ($quantity > 0) {
                $sql_price = "SELECT base_price FROM products WHERE id = ?";
                $stmt_price = mysqli_prepare($koneksi, $sql_price);
                mysqli_stmt_bind_param($stmt_price, "i", $product_id);
                mysqli_stmt_execute($stmt_price);
                $price_result = mysqli_stmt_get_result($stmt_price)->fetch_assoc();
                $price = $price_result['base_price'];
                
                $grand_total += $price * $quantity;

                mysqli_stmt_bind_param($stmt_insert, "iiid", $order_id, $product_id, $quantity, $price);
                mysqli_stmt_execute($stmt_insert);
            }
        }
    }
    
    // 4. Update total harga DAN alamat pengiriman di pesanan utama
    $sql_update_order = "UPDATE pre_orders SET 
                            total_amount = ?, 
                            shipping_name = ?, 
                            shipping_phone = ?, 
                            shipping_address = ?, 
                            shipping_province = ?, 
                            shipping_city = ?, 
                            shipping_subdistrict = ?, 
                            shipping_area = ?, 
                            shipping_post_code = ? 
                        WHERE id = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update_order);
    mysqli_stmt_bind_param($stmt_update, "dssssssssi", 
        $grand_total, $shipping_name, $shipping_phone, $shipping_address, 
        $shipping_province, $shipping_city, $shipping_subdistrict, 
        $shipping_area, $shipping_post_code, $order_id
    );
    mysqli_stmt_execute($stmt_update);

    // 5. Jika semua berhasil, simpan perubahan
    mysqli_commit($koneksi);
    
    header("Location: admin_po_masuk.php?pesan=" . urlencode("Pesanan #" . $order_id . " berhasil diperbarui."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal memperbarui pesanan. Error: " . $e->getMessage());
}
?>