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

$from_page = $_GET['from'] ?? 'po_masuk'; 
$back_link = ($from_page === 'laporan') ? 'admin_laporan.php' : 'admin_po_masuk.php';
$back_text = ($from_page === 'laporan') ? 'Kembali ke Laporan' : 'Kembali ke Daftar PO';

$sql_order = "SELECT po.*, u.full_name, u.username 
              FROM pre_orders po 
              JOIN users u ON po.customer_id = u.id 
              WHERE po.id = ?";
$stmt_order = mysqli_prepare($koneksi, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$result_order = mysqli_stmt_get_result($stmt_order);
$order = mysqli_fetch_assoc($result_order);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}
?>

<div class="content-box">
    <a href="<?php echo $back_link; ?>">&larr; <?php echo $back_text; ?></a>
    
    <h1 style="margin-top: 20px;">Detail Pesanan #<?php echo $order['id']; ?></h1>

    <?php if (isset($_GET['pesan'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['pesan'])); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 40px; margin-bottom: 20px; flex-wrap: wrap;">
        <div>
            <h3>Info Pesanan</h3>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['full_name']); ?> (<?php echo htmlspecialchars($order['username']); ?>)</p>
            <p><strong>Tanggal Pesan:</strong> <?php echo date("d M Y, H:i", strtotime($order['order_date'])); ?></p>
            <p><strong>Status:</strong> <span style="font-weight: bold; text-transform: capitalize;"><?php echo htmlspecialchars($order['status']); ?></span></p>
            <p><strong>Total Pesanan:</strong> <strong style="color: #28a745;">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></p>
            <?php if(!empty($order['note'])): ?>
                <p><strong>Catatan:</strong><br><pre style="white-space: pre-wrap; font-family: inherit; margin: 0;"><?php echo htmlspecialchars($order['note']); ?></pre></p>
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
    
    <?php if ($order['status'] === 'completed'): ?>
    <div style="background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h4 style="margin-top:0;">Aksi Tambahan</h4>
        <p style="margin-bottom: 10px;">Jika penambahan poin sebelumnya gagal (misal, karena akun customer belum ada di `order.rumahmadani.com`), Anda dapat memicu ulang sinkronisasi poin secara manual.</p>
        <a href="sync_ulang_poin.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin mencoba menambahkan poin untuk pesanan ini lagi? Pastikan akun customer sudah ada di WordPress.')" style="background-color: #007bff; color: white; text-decoration: none; padding: 8px 15px; border-radius: 5px; font-weight: bold;">
            Sinkronkan Ulang Poin
        </a>
    </div>
    <?php endif; ?>
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
require_once '../includes/footer.php';
?>