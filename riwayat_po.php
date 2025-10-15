<?php 
require_once 'includes/header.php'; 
require_once 'includes/koneksi.php';

// Pastikan hanya customer yang bisa akses
if ($_SESSION['role'] !== 'customer') {
    die("Akses ditolak.");
}

$customer_id = $_SESSION['user_id'];
?>

<div class="content-box">
    <h1>Riwayat Pre-Order Saya</h1>
    <p>Di bawah ini adalah daftar semua pesanan yang pernah Anda buat.</p>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #ddd; padding: 8px;">ID PO</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Tanggal Pesan</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Total</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // --- PERBAIKAN DI SINI: Query diubah untuk mengambil data pecahan ---
            $sql = "SELECT 
                        po.*,
                        IF(po.parent_order_id IS NOT NULL, 
                            (SELECT COUNT(*) FROM pre_orders p_sub WHERE p_sub.parent_order_id = po.parent_order_id AND p_sub.id <= po.id), 
                            NULL
                        ) as split_index
                    FROM pre_orders po 
                    WHERE po.customer_id = ? 
                    ORDER BY po.order_date DESC";
            
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "i", $customer_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";

                    // --- PERBAIKAN DI SINI: Logika untuk menampilkan ID PO yang benar ---
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    if ($row['parent_order_id']) {
                        echo "#" . $row['parent_order_id'] . "-" . $row['split_index'];
                    } else {
                        echo "#" . $row['id'];
                    }
                    echo "</td>";
                    
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . date("d M Y, H:i", strtotime($row['order_date'])) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>Rp " . number_format($row['total_amount'], 0, ',', '.') . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px; text-transform: capitalize;'>" . htmlspecialchars($row['status']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'><a href='detail_po_customer.php?id=" . $row['id'] . "'>Lihat Detail</a></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='5' style='border: 1px solid #ddd; padding: 8px; text-align:center;'>Anda belum memiliki riwayat pesanan.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<?php 
require_once 'includes/footer.php';
?>