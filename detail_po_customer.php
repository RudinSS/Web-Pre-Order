<?php 
require_once 'includes/header.php'; 
require_once 'includes/koneksi.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    die("ID Pesanan tidak valid.");
}

$customer_id = $_SESSION['user_id'];

// --- PERBAIKAN DI SINI: Query diubah untuk mengambil data pecahan ---
$sql_order = "SELECT 
                po.*,
                IF(po.parent_order_id IS NOT NULL, 
                    (SELECT COUNT(*) FROM pre_orders p_sub WHERE p_sub.parent_order_id = po.parent_order_id AND p_sub.id <= po.id), 
                    NULL
                ) as split_index
              FROM pre_orders po 
              WHERE po.id = ? AND po.customer_id = ?";

$stmt_order = mysqli_prepare($koneksi, $sql_order);
mysqli_stmt_bind_param($stmt_order, "ii", $order_id, $customer_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);
$order = mysqli_fetch_assoc($result_order);

if (!$order) {
    die("Pesanan tidak ditemukan atau Anda tidak memiliki akses.");
}

// --- PERBAIKAN DI SINI: Logika untuk menentukan ID yang akan ditampilkan ---
$display_id = $order['id'];
if ($order['parent_order_id']) {
    $display_id = $order['parent_order_id'] . '-' . $order['split_index'];
}
?>

<div class="content-box">
    <a href="riwayat_po.php">&larr; Kembali ke Riwayat PO</a>
    <h1 style="margin-top: 20px;">Detail Pesanan #<?php echo $display_id; ?></h1>

    <div style="display: flex; gap: 40px; margin-bottom: 20px; flex-wrap: wrap;">
        <div>
            <h3>Info Pesanan</h3>
            <p><strong>Tanggal Pesan:</strong> <?php echo date("d M Y, H:i", strtotime($order['order_date'])); ?></p>
            <p><strong>Status:</strong> <span style="font-weight: bold; text-transform: capitalize;"><?php echo htmlspecialchars($order['status']); ?></span></p>
            <p><strong>Total Pesanan:</strong> <strong style="color: #28a745;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></p>
            <?php if(!empty($order['note'])): ?>
                <p><strong>Catatan:</strong> <?php echo htmlspecialchars($order['note']); ?></p>
            <?php endif; ?>
        </div>
        
        <div>
            <h3>Alamat Pengiriman</h3>
            <p>
                <strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong><br>
                <?php echo htmlspecialchars($order['shipping_phone']); ?><br>
                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?><br>
                <?php echo htmlspecialchars($order['shipping_area']); ?>, <?php echo htmlspecialchars($order['shipping_subdistrict']); ?><br>
                <?php echo htmlspecialchars($order['shipping_city']); ?>, <?php echo htmlspecialchars($order['shipping_province']); ?><br>
                <?php echo htmlspecialchars($order['shipping_post_code']); ?>
            </p>
        </div>
    </div>

    <h3>Rincian Item</h3>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #ddd; padding: 8px;">SKU</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Nama Produk</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Harga Satuan</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Jumlah</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql_items = "SELECT oi.*, p.sku, p.model_name
                          FROM pre_order_items oi 
                          JOIN products p ON oi.product_id = p.id
                          WHERE oi.order_id = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "i", $order_id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            while ($item = mysqli_fetch_assoc($result_items)): 
                $subtotal = $item['quantity'] * $item['price'];
            ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['sku']); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['model_name']); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $item['quantity']; ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php 
require_once 'includes/footer.php';
?>