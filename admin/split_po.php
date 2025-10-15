<?php
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

// Pastikan hanya admin yang bisa akses
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id === 0) {
    die("ID Pesanan tidak valid.");
}

// Ambil data pesanan utama
$sql_order = "SELECT po.*, u.full_name 
              FROM pre_orders po JOIN users u ON po.customer_id = u.id 
              WHERE po.id = ?";
$stmt_order = mysqli_prepare($koneksi, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$order = mysqli_stmt_get_result($stmt_order)->fetch_assoc();

if (!$order) {
    die("Pesanan tidak ditemukan.");
}
?>

<div class="content-box">
    <h1>Pecah Pesanan #<?php echo $order['id']; ?></h1>
    <p>Customer: <strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>
    <p>Pilih item dan tentukan kuantitas yang akan diproses sekarang. Item yang tersisa akan tetap berada di pesanan ini.</p>

    <form action="proses_split_po.php" method="POST">
        <input type="hidden" name="original_order_id" value="<?php echo $order_id; ?>">

        <h3>Item yang Akan Diproses Sebagian</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px;">SKU</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Nama Produk</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Qty Pesanan</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 150px;">Qty untuk Diproses</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql_items = "SELECT oi.*, p.sku, p.model_name
                              FROM pre_order_items oi JOIN products p ON oi.product_id = p.id
                              WHERE oi.order_id = ?";
                $stmt_items = mysqli_prepare($koneksi, $sql_items);
                mysqli_stmt_bind_param($stmt_items, "i", $order_id);
                mysqli_stmt_execute($stmt_items);
                $result_items = mysqli_stmt_get_result($stmt_items);
                
                while ($item = mysqli_fetch_assoc($result_items)): 
                ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['model_name']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $item['quantity']; ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <input type="number" name="split_qty[<?php echo $item['id']; ?>]" min="0" max="<?php echo $item['quantity']; ?>" value="0" style="width: 100px;">
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px; margin-top: 20px;">Proses Pesanan Sebagian</button>
        <a href="admin_po_masuk.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<?php 
require_once '../includes/footer.php'; 
?>