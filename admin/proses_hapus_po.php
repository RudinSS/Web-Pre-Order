<?php
// Mulai sesi secara manual karena tidak ada di koneksi.php
session_start();

// Panggil file koneksi setelah sesi dimulai
require_once '../includes/koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pesan = "ID Pesanan tidak valid."; // Pesan default

if ($order_id > 0) {
    // Gunakan transaksi untuk memastikan kedua operasi berhasil atau gagal bersamaan
    mysqli_begin_transaction($koneksi);

    try {
        // 1. Hapus dulu semua item pesanan di tabel pre_order_items
        $sql_items = "DELETE FROM pre_order_items WHERE order_id = ?";
        $stmt_items = mysqli_prepare($koneksi, $sql_items);
        mysqli_stmt_bind_param($stmt_items, "i", $order_id);
        mysqli_stmt_execute($stmt_items);
        mysqli_stmt_close($stmt_items);

        // 2. Setelah itu, hapus pesanan utamanya di tabel pre_orders
        $sql_order = "DELETE FROM pre_orders WHERE id = ?";
        $stmt_order = mysqli_prepare($koneksi, $sql_order);
        mysqli_stmt_bind_param($stmt_order, "i", $order_id);
        mysqli_stmt_execute($stmt_order);
        $affected_rows = mysqli_stmt_affected_rows($stmt_order);
        mysqli_stmt_close($stmt_order);
        
        if ($affected_rows > 0) {
            // Jika semua berhasil, simpan perubahan
            mysqli_commit($koneksi);
            $pesan = "Pesanan #" . $order_id . " berhasil dihapus.";
        } else {
            // Jika pesanan utama tidak ditemukan, batalkan
            mysqli_rollback($koneksi);
            $pesan = "Gagal menghapus: Pesanan tidak ditemukan.";
        }

    } catch (Exception $e) {
        // Jika ada error, batalkan semua perubahan
        mysqli_rollback($koneksi);
        $pesan = "Gagal menghapus pesanan karena terjadi error database.";
    }
}

// Kembalikan ke halaman daftar PO dengan notifikasi
header("Location: admin_po_masuk.php?pesan=" . urlencode($pesan));
exit;
?>