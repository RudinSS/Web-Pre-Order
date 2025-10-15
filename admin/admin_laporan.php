<?php 
// Pastikan sesi dimulai jika belum
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php'; 

if ($_SESSION['role'] !== 'admin') { 
    die("Akses ditolak."); 
}

$start_date = $_GET['start_date'] ?? date('Y-m-01'); 
$end_date = $_GET['end_date'] ?? date('Y-m-t'); 
$status_filter = $_GET['status_filter'] ?? 'all';

// Siapkan kondisi WHERE untuk query SQL
$where_conditions = [];
$params = [];
$types = "";

if ($status_filter === 'all') {
    $where_conditions[] = "po.status IN ('pending', 'confirmed')";
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

// Query untuk data ringkasan
$sql_summary = "SELECT COUNT(id) as total_orders, SUM(total_amount) as total_revenue FROM pre_orders po WHERE $where_clause";
$stmt_summary = mysqli_prepare($koneksi, $sql_summary);
if(!empty($types)) {
    mysqli_stmt_bind_param($stmt_summary, $types, ...$params);
}
mysqli_stmt_execute($stmt_summary);
$summary = mysqli_stmt_get_result($stmt_summary)->fetch_assoc();
?>

<div class="content-box">
    <h1>Laporan Penjualan</h1>
    <p>Lihat ringkasan performa bisnis Anda berdasarkan rentang tanggal dan status.</p>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
        <form method="GET" action="" style="display: flex; align-items: center; gap: 15px;">
            <div>
                <label for="start_date">Dari Tanggal:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div>
                <label for="end_date">Sampai Tanggal:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div>
                <label for="status_filter">Status:</label>
                <select id="status_filter" name="status_filter">
                    <option value="all" <?php if($status_filter == 'all') echo 'selected'; ?>>Semua Status</option>
                    <option value="pending" <?php if($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="confirmed" <?php if($status_filter == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                </select>
            </div>
            <button type="submit" style="padding: 5px 10px;">Filter</button>
        </form>

        <form method="POST" action="export_laporan.php">
            <input type="hidden" name="start_date" value="<?php echo $start_date; ?>">
            <input type="hidden" name="end_date" value="<?php echo $end_date; ?>">
            <input type="hidden" name="status_filter" value="<?php echo $status_filter; ?>">
            <button type="submit" style="padding: 8px 12px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">Export ke CSV</button>
        </form>
    </div>

    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div style="flex: 1; padding: 20px; background-color: #e9f7fe; border-radius: 8px; text-align: center;">
            <h3 style="margin-top: 0;">Total Pendapatan (<?php echo ucfirst($status_filter); ?>)</h3>
            <p style="font-size: 24px; font-weight: bold; margin-bottom: 0;">Rp <?php echo number_format($summary['total_revenue'] ?? 0, 0, ',', '.'); ?></p>
        </div>
        <div style="flex: 1; padding: 20px; background-color: #d4edda; border-radius: 8px; text-align: center;">
            <h3 style="margin-top: 0;">Jumlah Pesanan (<?php echo ucfirst($status_filter); ?>)</h3>
            <p style="font-size: 24px; font-weight: bold; margin-bottom: 0;"><?php echo $summary['total_orders'] ?? 0; ?></p>
        </div>
    </div>

    <h3>Daftar Pesanan di Periode Ini</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #ddd; padding: 8px;">ID PO</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Jubelio SO ID</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Customer</th>
                <th style="border: 1px solid #ddd; padding: 8px;">No. Telepon</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Tanggal</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Total</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query diubah untuk menghitung split_index agar bisa menampilkan ID pecahan dengan benar
            $sql_orders = "SELECT 
                               po.id, po.jubelio_so_id, po.order_date, po.total_amount, po.status, u.full_name, po.shipping_phone, po.parent_order_id,
                               IF(po.parent_order_id IS NOT NULL, 
                                   (SELECT COUNT(*) FROM pre_orders p_sub WHERE p_sub.parent_order_id = po.parent_order_id AND p_sub.id <= po.id), 
                                   NULL
                               ) as split_index
                           FROM pre_orders po
                           LEFT JOIN users u ON po.customer_id = u.id
                           WHERE $where_clause
                           ORDER BY po.order_date DESC";
            
            $stmt_orders = mysqli_prepare($koneksi, $sql_orders);
            if(!empty($types)) {
                mysqli_stmt_bind_param($stmt_orders, $types, ...$params);
            }
            mysqli_stmt_execute($stmt_orders);
            $result_orders = mysqli_stmt_get_result($stmt_orders);

            if (mysqli_num_rows($result_orders) > 0) {
                while ($row = mysqli_fetch_assoc($result_orders)) {
                    echo "<tr>";
                    // Logika baru untuk menampilkan ID PO yang benar
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    if ($row['parent_order_id']) {
                        echo "#" . $row['parent_order_id'] . "-" . $row['split_index'];
                    } else {
                        echo "#" . $row['id'];
                    }
                    echo "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['jubelio_so_id'] ?? '') . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['full_name'] ?? 'Customer Dihapus') . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['shipping_phone']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . date("d M Y", strtotime($row['order_date'])) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px; text-transform: capitalize;'>" . htmlspecialchars($row['status']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>Rp " . number_format($row['total_amount'], 0, ',', '.') . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'><a href='detail_po.php?id=" . $row['id'] . "&from=laporan'>Lihat Detail</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='border: 1px solid #ddd; padding: 8px; text-align:center;'>Tidak ada pesanan pada periode dan status ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php require_once '../includes/footer.php'; ?>