<?php
session_start();
require_once 'includes/koneksi.php';

// Aktifkan pelaporan error untuk debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// 1. Validasi Sesi dan Keranjang
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'customer') {
    header('Location: index.php');
    exit;
}
if (empty($_SESSION['po_cart']) || empty($_SESSION['po_qty'])) {
    header("Location: po_baru.php?pesan=keranjang_kosong");
    exit;
}

// 2. Ambil Data dari Sesi dan Form
$cart = $_SESSION['po_cart'];
$quantities = $_SESSION['po_qty'];
$user_id = $_SESSION['user_id'];
$shipping_name = $_POST['shipping_full_name'];
$shipping_phone = $_POST['shipping_phone'];
$shipping_address = $_POST['shipping_address'];
$shipping_province = $_POST['shipping_province'];
$shipping_city = $_POST['shipping_city'];
$shipping_subdistrict = $_POST['shipping_subdistrict'];
$shipping_area = $_POST['shipping_area'];
$shipping_post_code = $_POST['shipping_post_code'];
$note = !empty($_POST['note']) ? $_POST['note'] : null;

// 3. Proses Penyimpanan ke Database dengan Transaksi
mysqli_begin_transaction($koneksi);

try {
    // --- LOGIKA BARU: Cek dan simpan alamat ke profil user jika kosong ---
    $sql_check_addr = "SELECT shipping_address FROM users WHERE id = ?";
    $stmt_check_addr = mysqli_prepare($koneksi, $sql_check_addr);
    mysqli_stmt_bind_param($stmt_check_addr, "i", $user_id);
    mysqli_stmt_execute($stmt_check_addr);
    $user_addr_data = mysqli_stmt_get_result($stmt_check_addr)->fetch_assoc();
    
    // Jika alamat di profil user kosong, update dengan alamat dari checkout ini
    if ($user_addr_data && (empty($user_addr_data['shipping_address']) || is_null($user_addr_data['shipping_address']))) {
        $sql_update_user = "UPDATE users SET shipping_address=?, shipping_province=?, shipping_city=?, shipping_subdistrict=?, shipping_area=?, shipping_post_code=? WHERE id = ?";
        $stmt_update_user = mysqli_prepare($koneksi, $sql_update_user);
        mysqli_stmt_bind_param($stmt_update_user, "ssssssi", 
            $shipping_address, $shipping_province, $shipping_city, 
            $shipping_subdistrict, $shipping_area, $shipping_post_code, $user_id
        );
        mysqli_stmt_execute($stmt_update_user);
    }
    // --- AKHIR LOGIKA BARU ---

    // Langkah A: Simpan pesanan utama
    $sql_order = "INSERT INTO pre_orders (customer_id, total_amount, status, shipping_name, shipping_phone, shipping_address, shipping_province, shipping_city, shipping_subdistrict, shipping_area, shipping_post_code, note) VALUES (?, 0, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_order = mysqli_prepare($koneksi, $sql_order);
    mysqli_stmt_bind_param($stmt_order, "isssssssss", $user_id, $shipping_name, $shipping_phone, $shipping_address, $shipping_province, $shipping_city, $shipping_subdistrict, $shipping_area, $shipping_post_code, $note);
    mysqli_stmt_execute($stmt_order);
    $order_id = mysqli_insert_id($koneksi);

    if ($order_id == 0) throw new Exception("Gagal membuat record pesanan utama.");

    // Langkah B: Simpan item pesanan dan hitung total
    $total_amount = 0;
    $sql_items = "INSERT INTO pre_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt_items = mysqli_prepare($koneksi, $sql_items);
    $product_ids = array_keys($quantities);
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $types = str_repeat('i', count($product_ids));
        $sql_prices = "SELECT id, base_price FROM products WHERE id IN ($placeholders)";
        $stmt_prices = mysqli_prepare($koneksi, $sql_prices);
        mysqli_stmt_bind_param($stmt_prices, $types, ...$product_ids);
        mysqli_stmt_execute($stmt_prices);
        $prices_result = mysqli_stmt_get_result($stmt_prices);
        $product_prices = [];
        while ($row = mysqli_fetch_assoc($prices_result)) {
            $product_prices[$row['id']] = $row['base_price'];
        }
        foreach ($quantities as $product_id => $quantity) {
            if ((int)$quantity > 0 && isset($product_prices[$product_id])) {
                $price = (float)$product_prices[$product_id];
                $total_amount += $price * (int)$quantity;
                mysqli_stmt_bind_param($stmt_items, "iiid", $order_id, $product_id, $quantity, $price);
                mysqli_stmt_execute($stmt_items);
            }
        }
    }
    
    // Langkah C: Update total_amount di pesanan utama
    $sql_update_total = "UPDATE pre_orders SET total_amount = ? WHERE id = ?";
    $stmt_update = mysqli_prepare($koneksi, $sql_update_total);
    mysqli_stmt_bind_param($stmt_update, "di", $total_amount, $order_id);
    mysqli_stmt_execute($stmt_update);

    mysqli_commit($koneksi);
    
    // 4. Bersihkan Sesi dan Arahkan ke Halaman Sukses
    unset($_SESSION['po_cart'], $_SESSION['po_qty']);
    header('Location: pesanan_berhasil.php?order_id=' . $order_id);
    exit;

} catch (mysqli_sql_exception $e) {
    mysqli_rollback($koneksi);
    $error_message = "Gagal memproses pesanan. Error: " . $e->getMessage();
    header('Location: checkout.php?pesan=' . urlencode($error_message));
    exit;
}
?>