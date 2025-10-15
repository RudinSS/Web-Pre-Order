<?php 
require_once 'includes/header.php'; 
// Ambil ID pesanan dari URL untuk ditampilkan
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
?>

<div class="content-box" style="text-align: center;">
    <h1 style="color: #28a745;">✓ Pesanan Berhasil Dibuat!</h1>
    <p>Terima kasih, pesanan Anda dengan nomor <strong>#<?php echo $order_id; ?></strong> telah kami terima.</p>
    <p>Status pesanan Anda saat ini adalah "Pending". Kami akan segera memprosesnya.</p>
    <br>
    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px;">
        <a href="riwayat_po.php" style="background-color: #007bff; color: white; text-decoration: none; padding: 12px 25px; border-radius: 5px;">Lihat Riwayat PO</a>
        <a href="po_baru.php" style="background-color: #6c757d; color: white; text-decoration: none; padding: 12px 25px; border-radius: 5px;">Order Lagi</a>
    </div>
</div>

<?php 
require_once 'includes/footer.php'; 
?>