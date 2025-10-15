<?php
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Akses ditolak.");
}

// Mengambil data dari form dan session
$customer_id = $_SESSION['admin_customer_id'] ?? null; 
$quantities = $_SESSION['admin_po_qty'] ?? [];
$shipping_name = $_POST['shipping_full_name'];
$shipping_phone = $_POST['shipping_phone'];
$shipping_address = $_POST['shipping_address'];
$shipping_province = $_POST['shipping_province'];
$shipping_city = $_POST['shipping_city'];
$shipping_subdistrict = $_POST['shipping_subdistrict'];
$shipping_area = $_POST['shipping_area'];
$shipping_post_code = $_POST['shipping_post_code'];
$admin_username = $_SESSION['username'] ?? 'Admin';
$admin_note = "[Dipesankan oleh: " . $admin_username . "]";
$customer_note = $_POST['note'];
$note = !empty($customer_note) ? $admin_note . "\n" . $customer_note : $admin_note;

if (empty($quantities) || empty($customer_id)) {
    header("Location: po_baru_admin.php?pesan=sesi_tidak_valid");
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // --- LOGIKA BARU: Cek dan simpan alamat ke profil user jika kosong ---
    $sql_check_addr = "SELECT shipping_address FROM users WHERE id = ?";
    $stmt_check_addr = mysqli_prepare($koneksi, $sql_check_addr);
    mysqli_stmt_bind_param($stmt_check_addr, "i", $customer_id);
    mysqli_stmt_execute($stmt_check_addr);
    $user_addr_data = mysqli_stmt_get_result($stmt_check_addr)->fetch_assoc();

    if ($user_addr_data && (empty($user_addr_data['shipping_address']) || is_null($user_addr_data['shipping_address']))) {
        $sql_update_user = "UPDATE users SET shipping_address=?, shipping_province=?, shipping_city=?, shipping_subdistrict=?, shipping_area=?, shipping_post_code=? WHERE id = ?";
        $stmt_update_user = mysqli_prepare($koneksi, $sql_update_user);
        mysqli_stmt_bind_param($stmt_update_user, "ssssssi", 
            $shipping_address, $shipping_province, $shipping_city, 
            $shipping_subdistrict, $shipping_area, $shipping_post_code, $customer_id
        );
        mysqli_stmt_execute($stmt_update_user);
    }
    // --- AKHIR LOGIKA BARU ---

    // Menyimpan data pesanan utama
    $sql_order = "INSERT INTO pre_orders (customer_id, total_amount, status, shipping_name, shipping_phone, shipping_address, shipping_province, shipping_city, shipping_subdistrict, shipping_area, shipping_post_code, note) VALUES (?, 0, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_order = mysqli_prepare($koneksi, $sql_order);
    mysqli_stmt_bind_param($stmt_order, "isssssssss", $customer_id, $shipping_name, $shipping_phone, $shipping_address, $shipping_province, $shipping_city, $shipping_subdistrict, $shipping_area, $shipping_post_code, $note);
    mysqli_stmt_execute($stmt_order);
    $order_id = mysqli_insert_id($koneksi);

    if ($order_id === 0) throw new Exception("Gagal membuat pesanan baru.");

    // Menyimpan item pesanan
    $grand_total = 0;
    $sql_item = "INSERT INTO pre_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
    $stmt_item = mysqli_prepare($koneksi, $sql_item);
    foreach ($quantities as $product_id => $qty) {
        if ($qty > 0) {
            $sql_price = "SELECT base_price FROM products WHERE id = " . (int)$product_id;
            $res_price = mysqli_query($koneksi, $sql_price);
            if (!$res_price) throw new Exception("Gagal mengambil harga produk.");
            
            $prod_data = mysqli_fetch_assoc($res_price);
            $price_per_item = $prod_data['base_price'];
            $grand_total += $qty * $price_per_item;
            
            mysqli_stmt_bind_param($stmt_item, "iiid", $order_id, $product_id, $qty, $price_per_item);
            mysqli_stmt_execute($stmt_item);
        }
    }
    mysqli_stmt_close($stmt_item);

    // Memperbarui total harga
    $sql_update_total = "UPDATE pre_orders SET total_amount = ? WHERE id = ?";
    $stmt_update_total = mysqli_prepare($koneksi, $sql_update_total);
    mysqli_stmt_bind_param($stmt_update_total, "di", $grand_total, $order_id);
    mysqli_stmt_execute($stmt_update_total);

    mysqli_commit($koneksi);
    
    unset($_SESSION['admin_po_qty'], $_SESSION['admin_customer_id'], $_SESSION['admin_po_cart']);
    header("Location: admin_po_masuk.php?pesan=" . urlencode("Pesanan #" . $order_id . " berhasil dibuat."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("<h1>Proses Gagal!</h1><p>Terjadi kesalahan: " . $e->getMessage() . "</p>");
}
?>