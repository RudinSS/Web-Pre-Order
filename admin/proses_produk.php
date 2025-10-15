<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
require_once '../includes/koneksi.php';

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// Logika untuk Tambah Produk Massal (Tidak Berubah)
if ($aksi == 'tambah_massal') {
    $skus = $_POST['sku'];
    $brand_ids = $_POST['brand_id'];
    $base_prices = $_POST['base_price'];
    $deadlines = $_POST['deadline_po'];

    $sukses_count = 0;
    $exist_count = 0;
    $gagal_count = 0;

    $sql_check = "SELECT sku FROM products WHERE sku = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check);

    $sql_insert = "INSERT INTO products (brand_id, model_name, sku, base_price, deadline_po) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($koneksi, $sql_insert);

    for ($i = 0; $i < count($skus); $i++) {
        if (!empty($skus[$i])) {
            $current_sku = $skus[$i];
            
            mysqli_stmt_bind_param($stmt_check, "s", $current_sku);
            mysqli_stmt_execute($stmt_check);
            mysqli_stmt_store_result($stmt_check);
            
            if (mysqli_stmt_num_rows($stmt_check) > 0) {
                $exist_count++;
            } else {
                $model_name = $current_sku;
                $deadline = !empty($deadlines[$i]) ? date('Y-m-d H:i:s', strtotime($deadlines[$i])) : null;
                
                mysqli_stmt_bind_param($stmt_insert, "issds", $brand_ids[$i], $model_name, $current_sku, $base_prices[$i], $deadline);
                if (mysqli_stmt_execute($stmt_insert)) {
                    $sukses_count++;
                } else {
                    $gagal_count++;
                }
            }
        }
    }
    mysqli_stmt_close($stmt_check);
    mysqli_stmt_close($stmt_insert);

    $pesan = "Proses Selesai. Sukses: $sukses_count, Sudah Ada: $exist_count, Gagal: $gagal_count.";
    header("Location: admin_produk.php?pesan=" . urlencode($pesan));
    exit;
}
// Logika untuk Ubah Produk Satuan (Tidak Berubah)
elseif ($aksi == 'ubah') {
    $id = $_POST['id'];
    $sku = $_POST['sku'];
    $brand_id = $_POST['brand_id'];
    $base_price = $_POST['base_price'];
    $deadline_po = !empty($_POST['deadline_po']) ? date('Y-m-d H:i:s', strtotime($_POST['deadline_po'])) : null;
    $model_name = $sku;

    $sql = "UPDATE products SET brand_id = ?, model_name = ?, sku = ?, base_price = ?, deadline_po = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($koneksi, $sql)) {
        mysqli_stmt_bind_param($stmt, "issdsi", $brand_id, $model_name, $sku, $base_price, $deadline_po, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    $pesan = "Produk berhasil diperbarui.";
    header("Location: admin_produk.php?pesan=" . urlencode($pesan));
    exit;
}
// Logika untuk Hapus Produk Satuan (DIPERBAIKI)
elseif ($aksi == 'hapus') {
    $id = (int)$_GET['id'];
    
    // --- PENGECEKAN BARU: Cek apakah produk sudah ada di pesanan ---
    $sql_check_po = "SELECT COUNT(id) as total_orders FROM pre_order_items WHERE product_id = ?";
    $stmt_check = mysqli_prepare($koneksi, $sql_check_po);
    mysqli_stmt_bind_param($stmt_check, "i", $id);
    mysqli_stmt_execute($stmt_check);
    $result = mysqli_stmt_get_result($stmt_check);
    $check_data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_check);

    if ($check_data['total_orders'] > 0) {
        $pesan = "Gagal menghapus: Produk ini sudah dipesan ($check_data[total_orders] kali) dan tidak dapat dihapus.";
        header("Location: admin_produk.php?pesan=" . urlencode($pesan));
        exit;
    }
    // --- AKHIR PENGECEKAN BARU ---
    
    $sql = "DELETE FROM products WHERE id = ?";
    if ($stmt = mysqli_prepare($koneksi, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    $pesan = "Produk berhasil dihapus.";
    header("Location: admin_produk.php?pesan=" . urlencode($pesan));
    exit;
}
// Logika untuk Hapus Produk Massal (DIPERBAIKI)
elseif ($aksi == 'hapus_massal') {
    if (isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
        $ids_to_delete = $_POST['selected_ids'];
        $ids = array_map('intval', $ids_to_delete);
        
        $can_delete_ids = [];
        $cannot_delete_count = 0;
        
        foreach ($ids as $id) {
            // Cek apakah produk sudah ada di pesanan
            $sql_check_po = "SELECT COUNT(id) as total_orders FROM pre_order_items WHERE product_id = ?";
            $stmt_check = mysqli_prepare($koneksi, $sql_check_po);
            mysqli_stmt_bind_param($stmt_check, "i", $id);
            mysqli_stmt_execute($stmt_check);
            $result = mysqli_stmt_get_result($stmt_check);
            $check_data = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt_check);
            
            if ($check_data['total_orders'] === 0) {
                $can_delete_ids[] = $id;
            } else {
                $cannot_delete_count++;
            }
        }
        
        if (!empty($can_delete_ids)) {
            $placeholders = implode(',', array_fill(0, count($can_delete_ids), '?'));
            $sql = "DELETE FROM products WHERE id IN ($placeholders)";
            $stmt = mysqli_prepare($koneksi, $sql);
            
            $types = str_repeat('i', count($can_delete_ids));
            mysqli_stmt_bind_param($stmt, $types, ...$can_delete_ids);
            mysqli_stmt_execute($stmt);
            
            $deleted_count = mysqli_stmt_affected_rows($stmt);
            
            $pesan = "$deleted_count produk berhasil dihapus.";
            if ($cannot_delete_count > 0) {
                $pesan .= " ($cannot_delete_count produk gagal dihapus karena sudah ada di pesanan.)";
            }
            header("Location: admin_produk.php?pesan=" . urlencode($pesan));
            exit;
        } elseif ($cannot_delete_count > 0) {
            $pesan = "Gagal: Semua produk yang dipilih sudah ada di pesanan dan tidak dapat dihapus.";
            header("Location: admin_produk.php?pesan=" . urlencode($pesan));
            exit;
        }
    }
}
// Logika untuk Edit Produk Massal (Tidak Berubah)
elseif ($aksi == 'edit_massal_save') {
    if (isset($_POST['selected_ids']) && is_array($_POST['selected_ids'])) {
        $ids_to_edit = $_POST['selected_ids'];
        $ids = array_map('intval', $ids_to_edit);

        $bulk_brand_id = !empty($_POST['bulk_brand_id']) ? (int)$_POST['bulk_brand_id'] : null;
        $bulk_deadline_po_raw = $_POST['bulk_deadline_po'] ?? '';
        $bulk_deadline_po = !empty($bulk_deadline_po_raw) ? date('Y-m-d H:i:s', strtotime($bulk_deadline_po_raw)) : null;

        if ($bulk_brand_id === null && $bulk_deadline_po_raw === '') {
             $pesan = "Tidak ada perubahan yang diterapkan.";
             header("Location: admin_produk.php?pesan=" . urlencode($pesan));
             exit;
        }

        $sql_parts = [];
        $params = [];
        $types = "";

        if ($bulk_brand_id !== null) {
            $sql_parts[] = "brand_id = ?";
            $params[] = $bulk_brand_id;
            $types .= "i";
        }
        if ($bulk_deadline_po_raw !== '') {
            $sql_parts[] = "deadline_po = ?";
            $params[] = $bulk_deadline_po;
            $types .= "s";
        }
        
        if (empty($sql_parts) || empty($ids)) {
             $pesan = "Tidak ada perubahan yang diterapkan.";
             header("Location: admin_produk.php?pesan=" . urlencode($pesan));
             exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        foreach ($ids as $id) {
            $params[] = $id;
            $types .= "i";
        }

        $sql = "UPDATE products SET " . implode(', ', $sql_parts) . " WHERE id IN ($placeholders)";
        $stmt = mysqli_prepare($koneksi, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $updated_count = mysqli_stmt_affected_rows($stmt);
        $pesan = "$updated_count produk berhasil diperbarui.";
        header("Location: admin_produk.php?pesan=" . urlencode($pesan));
        exit;
    }
}

// Redirect default jika tidak ada aksi yang cocok
header("Location: admin_produk.php");
exit;
?>