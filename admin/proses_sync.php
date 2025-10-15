<?php

session_start();
// Pastikan koneksi.php dipanggil pertama untuk memulai sesi
require_once '../includes/koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['type' => 'gagal', 'message' => 'Akses ditolak.']);
    exit;
}

// Atur header output sebagai JSON
header('Content-Type: application/json');

// Cek jika ada produk yang dipilih
if (!isset($_POST['selected_products']) || empty($_POST['selected_products'])) {
    echo json_encode(['type' => 'gagal', 'message' => 'Tidak ada produk yang dipilih.']);
    exit;
}

// Menyiapkan brand "Jubelio" jika belum ada
$brand_name = "Jubelio";
$jubelio_brand_id = null;
$sql_find_brand = "SELECT id FROM brands WHERE brand_name = ?";
$stmt_find = mysqli_prepare($koneksi, $sql_find_brand);
mysqli_stmt_bind_param($stmt_find, "s", $brand_name);
mysqli_stmt_execute($stmt_find);
$result_brand = mysqli_stmt_get_result($stmt_find);
if ($row = mysqli_fetch_assoc($result_brand)) {
    $jubelio_brand_id = $row['id'];
} else {
    $sql_insert_brand = "INSERT INTO brands (brand_name) VALUES (?)";
    $stmt_insert = mysqli_prepare($koneksi, $sql_insert_brand);
    mysqli_stmt_bind_param($stmt_insert, "s", $brand_name);
    mysqli_stmt_execute($stmt_insert);
    $jubelio_brand_id = mysqli_insert_id($koneksi);
}
mysqli_stmt_close($stmt_find);

$selected_products = $_POST['selected_products'];
$added_count = 0;
$updated_count = 0;

foreach ($selected_products as $product_json) {
    $product = json_decode($product_json, true);
    
    $sku = $product['sku'];
    $price = $product['price'];
    $model_name = $product['name'];
    $image_url = $product['image_url'];
    $jubelio_date_formatted = date("Y-m-d H:i:s", strtotime($product['jubelio_date']));
    $jubelio_item_id = $product['jubelio_item_id']; // Ambil item_id

    $sql_check = "SELECT id FROM products WHERE sku = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "s", $sku);
    mysqli_stmt_execute($stmt_check);
    $res_check = mysqli_stmt_get_result($stmt_check);
    
    if ($existing_product = mysqli_fetch_assoc($res_check)) {
        // Jika produk sudah ada, perbarui datanya
        $sql_update = "UPDATE products SET base_price = ?, model_name = ?, image_url = ?, jubelio_created_at = ?, jubelio_item_id = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "dsssii", $price, $model_name, $image_url, $jubelio_date_formatted, $jubelio_item_id, $existing_product['id']);
        mysqli_stmt_execute($stmt_update);
        $updated_count++;
    } else {
        // Jika produk belum ada, buat baris baru
        $sql_insert = "INSERT INTO products (brand_id, model_name, sku, base_price, image_url, jubelio_created_at, jubelio_item_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $sql_insert);
        
        // --- INI ADALAH PERBAIKAN UTAMA ---
        // Urutan tipe data harus cocok persis dengan kolom di VALUES
        // brand_id (i), model_name (s), sku (s), base_price (d), image_url (s), jubelio_created_at (s), jubelio_item_id (i)
        mysqli_stmt_bind_param($stmt_insert, "issdssi", $jubelio_brand_id, $model_name, $sku, $price, $image_url, $jubelio_date_formatted, $jubelio_item_id);
        
        mysqli_stmt_execute($stmt_insert);
        $added_count++;
    }
}

// Menentukan tipe notifikasi untuk ditampilkan
$message = "Proses Selesai. Produk Baru: $added_count, Produk Diperbarui: $updated_count.";
$type = 'info'; 

if ($added_count > 0) {
    $type = 'sukses';
} elseif ($updated_count > 0) {
    $type = 'warning';
}

echo json_encode(['type' => $type, 'message' => $message]);
exit;
?>