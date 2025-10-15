<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

if ($_SESSION['role'] !== 'admin') { 
    die("Akses ditolak."); 
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id === 0) {
    die("Error: ID User tidak valid.");
}

// Ambil semua data pengguna, termasuk alamat, dengan SELECT *
$sql = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Error: User tidak ditemukan.");
}
?>
<div class="content-box">
    <h2>Edit Pengguna</h2>

    <?php if (isset($_GET['error'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
    <?php endif; ?>

    <form action="proses_pengguna.php" method="POST" style="max-width: 700px;">
        <input type="hidden" name="aksi" value="ubah">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

        <h3>Informasi Akun</h3>
        <div style="margin-bottom: 15px;">
            <label>Nama Lengkap:</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Username:</label>
                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Nomor Telepon:</label>
                <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
        </div>
        
        <hr style="margin: 30px 0;">

        <h3>Alamat Pengiriman</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Provinsi:</label>
                <input type="text" name="shipping_province" value="<?php echo htmlspecialchars($user['shipping_province'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kota/Kabupaten:</label>
                <input type="text" name="shipping_city" value="<?php echo htmlspecialchars($user['shipping_city'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kecamatan:</label>
                <input type="text" name="shipping_subdistrict" value="<?php echo htmlspecialchars($user['shipping_subdistrict'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kelurahan/Area:</label>
                <input type="text" name="shipping_area" value="<?php echo htmlspecialchars($user['shipping_area'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kode Pos:</label>
                <input type="text" name="shipping_post_code" value="<?php echo htmlspecialchars($user['shipping_post_code'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
        </div>
        <div style="margin-bottom: 15px; grid-column: 1 / -1;">
            <label>Alamat Lengkap:</label>
            <textarea name="shipping_address" rows="4" placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dll." style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($user['shipping_address'] ?? ''); ?></textarea>
        </div>
        <hr style="margin: 30px 0;">

        <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Simpan Perubahan</button>
        <a href="admin_pengguna.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>
<?php 
require_once '../includes/footer.php'; 
?>