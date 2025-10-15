<?php
// Pastikan sesi dimulai untuk mengakses data keranjang
session_start();

require_once 'includes/header.php'; 
require_once 'includes/koneksi.php';

// Simpan kuantitas dari halaman rincian ke dalam session
if (isset($_POST['qty'])) {
    $_SESSION['po_qty'] = $_POST['qty'];
}

// Jika tidak ada produk di keranjang atau kuantitas belum diisi, kembalikan
if (empty($_SESSION['po_cart']) || empty($_SESSION['po_qty'])) {
    header("Location: po_baru.php");
    exit;
}

// Ambil data user (termasuk alamat) untuk diisi otomatis di form
$user_id = $_SESSION['user_id'];
$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($koneksi, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$user_data = mysqli_stmt_get_result($stmt_user)->fetch_assoc();
?>

<div class="content-box">
    <h1>Langkah 3: Detail Pengiriman</h1>
    <p>Harap isi detail pengiriman Anda dengan lengkap agar pesanan dapat diproses.</p>

    <form action="proses_po.php" method="POST" style="max-width: 700px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Nama Penerima:</label>
                <input type="text" name="shipping_full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>No. Telepon Penerima:</label>
                <input type="tel" name="shipping_phone" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Provinsi:</label>
                <input type="text" name="shipping_province" value="<?php echo htmlspecialchars($user_data['shipping_province'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kota/Kabupaten:</label>
                <input type="text" name="shipping_city" value="<?php echo htmlspecialchars($user_data['shipping_city'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kecamatan:</label>
                <input type="text" name="shipping_subdistrict" value="<?php echo htmlspecialchars($user_data['shipping_subdistrict'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kelurahan/Area:</label>
                <input type="text" name="shipping_area" value="<?php echo htmlspecialchars($user_data['shipping_area'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kode Pos:</label>
                <input type="text" name="shipping_post_code" value="<?php echo htmlspecialchars($user_data['shipping_post_code'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
            </div>
        </div>
        <div style="margin-bottom: 15px; grid-column: 1 / -1;">
            <label>Alamat Lengkap:</label>
            <textarea name="shipping_address" rows="4" required placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dll." style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($user_data['shipping_address'] ?? ''); ?></textarea>
        </div>
        <div style="margin-bottom: 15px; grid-column: 1 / -1;">
            <label>Catatan untuk Penjual (Opsional):</label>
            <textarea name="note" rows="3" style="width: 100%; padding: 8px; box-sizing: border-box;"></textarea>
        </div>
        
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Selesaikan Pesanan</button>
        <a href="rincian_po.php" style="margin-left: 10px;">Kembali ke Rincian Qty</a>
    </form>
</div>

<?php 
require_once 'includes/footer.php'; 
?>