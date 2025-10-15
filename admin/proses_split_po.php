<?php
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses ditolak.");
}

$original_order_id = (int)$_POST['original_order_id'];
$split_quantities = $_POST['split_qty'] ?? [];

$items_to_split = array_filter($split_quantities, function($qty) {
    return (int)$qty > 0;
});

if (empty($items_to_split)) {
    header("Location: admin_po_masuk.php?pesan=" . urlencode("Tidak ada item yang dipilih untuk diproses."));
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // 1. Ambil data pesanan sumber
    $sql_source = "SELECT * FROM pre_orders WHERE id = ?";
    $stmt_source = mysqli_prepare($koneksi, $sql_source);
    mysqli_stmt_bind_param($stmt_source, "i", $original_order_id);
    mysqli_stmt_execute($stmt_source);
    $source_order = mysqli_stmt_get_result($stmt_source)->fetch_assoc();
    
    if (!$source_order) { throw new Exception("Pesanan sumber (#$original_order_id) tidak ditemukan."); }
    
    // 2. Tentukan ID induk utama dan ambil `split_count` dari sana
    $true_parent_id = $source_order['parent_order_id'] ?? $source_order['id'];
    $sql_parent = "SELECT split_count FROM pre_orders WHERE id = ? FOR UPDATE";
    $stmt_parent = mysqli_prepare($koneksi, $sql_parent);
    mysqli_stmt_bind_param($stmt_parent, "i", $true_parent_id);
    mysqli_stmt_execute($stmt_parent);
    $parent_data = mysqli_stmt_get_result($stmt_parent)->fetch_assoc();
    if (!$parent_data) { throw new Exception("Pesanan induk (#$true_parent_id) tidak ditemukan."); }
    
    $new_split_count = $parent_data['split_count'] + 1;
    $new_note = "Pesanan ini adalah pecahan dari #" . $original_order_id;

    $sql_new_order = "INSERT INTO pre_orders (parent_order_id, customer_id, total_amount, status, shipping_name, shipping_phone, shipping_address, shipping_province, shipping_city, shipping_subdistrict, shipping_area, shipping_post_code, note)
                      VALUES (?, ?, 0, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_new = mysqli_prepare($koneksi, $sql_new_order);
    
    // --- PERBAIKAN DI SINI ---
    // Tipe string disesuaikan menjadi 'iisssssssss' (11 karakter) agar cocok dengan 11 variabel yang diikat.
    mysqli_stmt_bind_param($stmt_new, "iisssssssss", 
        $true_parent_id, $source_order['customer_id'], $source_order['shipping_name'], 
        $source_order['shipping_phone'], $source_order['shipping_address'], $source_order['shipping_province'],
        $source_order['shipping_city'], $source_order['shipping_subdistrict'], $source_order['shipping_area'],
        $source_order['shipping_post_code'], $new_note
    );
    // --- AKHIR PERBAIKAN ---
    
    mysqli_stmt_execute($stmt_new);
    $new_order_id = mysqli_insert_id($koneksi);

    // 4. Update split_count di pesanan induk utama
    $sql_update_split_count = "UPDATE pre_orders SET split_count = ? WHERE id = ?";
    $stmt_update_count = mysqli_prepare($koneksi, $sql_update_split_count);
    mysqli_stmt_bind_param($stmt_update_count, "ii", $new_split_count, $true_parent_id);
    mysqli_stmt_execute($stmt_update_count);
    
    // (Sisa kode untuk memindahkan item dan mengupdate total tetap sama)
    $new_order_total = 0;
    foreach ($items_to_split as $item_id => $split_qty) {
        $item_id = (int)$item_id; $split_qty = (int)$split_qty;
        $sql_item = "SELECT * FROM pre_order_items WHERE id = ?";
        $stmt_item = mysqli_prepare($koneksi, $sql_item);
        mysqli_stmt_bind_param($stmt_item, "i", $item_id);
        mysqli_stmt_execute($stmt_item);
        $item = mysqli_stmt_get_result($stmt_item)->fetch_assoc();
        if ($item && $split_qty > 0 && $split_qty <= $item['quantity']) {
            $sql_insert_item = "INSERT INTO pre_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt_insert = mysqli_prepare($koneksi, $sql_insert_item);
            mysqli_stmt_bind_param($stmt_insert, "iiid", $new_order_id, $item['product_id'], $split_qty, $item['price']);
            mysqli_stmt_execute($stmt_insert);
            $new_order_total += $split_qty * $item['price'];
            $remaining_qty = $item['quantity'] - $split_qty;
            if ($remaining_qty > 0) {
                $sql_update_item = "UPDATE pre_order_items SET quantity = ? WHERE id = ?";
                $stmt_update = mysqli_prepare($koneksi, $sql_update_item);
                mysqli_stmt_bind_param($stmt_update, "ii", $remaining_qty, $item_id);
                mysqli_stmt_execute($stmt_update);
            } else {
                $sql_delete_item = "DELETE FROM pre_order_items WHERE id = ?";
                $stmt_delete = mysqli_prepare($koneksi, $sql_delete_item);
                mysqli_stmt_bind_param($stmt_delete, "i", $item_id);
                mysqli_stmt_execute($stmt_delete);
            }
        }
    }
    $sql_update_totals = "UPDATE pre_orders SET total_amount = ? WHERE id = ?";
    $stmt_update_totals = mysqli_prepare($koneksi, $sql_update_totals);
    mysqli_stmt_bind_param($stmt_update_totals, "di", $new_order_total, $new_order_id);
    mysqli_stmt_execute($stmt_update_totals);
    $sql_recalc = "SELECT SUM(quantity * price) as new_total FROM pre_order_items WHERE order_id = ?";
    $stmt_recalc = mysqli_prepare($koneksi, $sql_recalc);
    mysqli_stmt_bind_param($stmt_recalc, "i", $original_order_id);
    mysqli_stmt_execute($stmt_recalc);
    $recalc_result = mysqli_stmt_get_result($stmt_recalc)->fetch_assoc();
    $original_order_remaining_total = $recalc_result['new_total'] ?? 0;
    mysqli_stmt_bind_param($stmt_update_totals, "di", $original_order_remaining_total, $original_order_id);
    mysqli_stmt_execute($stmt_update_totals);

    mysqli_commit($koneksi);
    $display_split_id = $true_parent_id . '-' . $new_split_count;
    header("Location: admin_po_masuk.php?pesan=" . urlencode("Pesanan #" . $original_order_id . " berhasil dipecah. Pesanan baru #" . $display_split_id . " telah dibuat."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $error_message = "Gagal memecah pesanan: " . $e->getMessage();
    header("Location: admin_po_masuk.php?pesan=" . urlencode($error_message));
    exit;
}
?>