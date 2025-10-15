<?php
session_start();
require_once '../includes/koneksi.php';
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') { die("Akses ditolak."); }
$start_date = $_POST['start_date'] ?? date('Y-m-01'); $end_date = $_POST['end_date'] ?? date('Y-m-t'); $status_filter = $_POST['status_filter'] ?? 'all';

// Siapkan kondisi WHERE
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter === 'all') {
    $where_conditions[] = "po.status IN ('pending', 'confirmed', 'completed')";
} else {
    $where_conditions[] = "po.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}
$where_conditions[] = "po.order_date BETWEEN ? AND ?";
$end_date_for_query = $end_date . ' 23:59:59';
$params[] = $start_date;
$params[] = $end_date_for_query;
$types .= "ss";
$where_clause = implode(' AND ', $where_conditions);

// --- PERUBAHAN 1: Tambahkan po.jubelio_so_id ke dalam SELECT ---
$sql = "SELECT 
            po.id as order_id, po.jubelio_so_id, u.full_name as customer_name, po.shipping_phone, po.order_date, po.status,
            p.sku, p.model_name, oi.quantity, oi.price, (oi.quantity * oi.price) as subtotal, po.parent_order_id,
            IF(po.parent_order_id IS NOT NULL, 
                (SELECT COUNT(*) FROM pre_orders p_sub WHERE p_sub.parent_order_id = po.parent_order_id AND p_sub.id <= po.id), 
                NULL
            ) as split_index
        FROM pre_orders po
        LEFT JOIN users u ON po.customer_id = u.id
        LEFT JOIN pre_order_items oi ON po.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE $where_clause
        ORDER BY po.order_date DESC, po.id, p.sku";
// --- AKHIR PERBAIKAN ---

$stmt = mysqli_prepare($koneksi, $sql);
if(!empty($types)) { mysqli_stmt_bind_param($stmt, $types, ...$params); }
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$filename = "laporan_po_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');
fputcsv($output, ['ID Pesanan', 'Jubelio SO ID', 'Customer', 'No. Telepon', 'Tanggal Pesan', 'Status', 'SKU', 'Nama Produk', 'Jumlah', 'Harga Satuan', 'Subtotal']);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // --- PERBAIKAN DI SINI: Logika baru untuk ID Pesanan ---
        $display_po_id = $row['order_id'];
        if ($row['parent_order_id']) {
            $display_po_id = $row['parent_order_id'] . '-' . $row['split_index'];
        }
        // --- AKHIR PERBAIKAN ---

        $csv_row = [
            $display_po_id, // Menggunakan ID yang sudah diformat
            $row['jubelio_so_id'] ?? '',
            $row['customer_name'],
            $row['shipping_phone'],
            $row['order_date'],
            $row['status'],
            $row['sku'],
            $row['model_name'],
            $row['quantity'],
            number_format($row['price'], 0, ',', ''),
            number_format($row['subtotal'], 0, ',', '')
        ];
        fputcsv($output, $csv_row);
    }
}
fclose($output);
exit;
?>