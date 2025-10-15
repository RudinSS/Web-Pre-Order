<?php
require_once 'includes/header.php';
require_once 'includes/koneksi.php';

if ($_SESSION['role'] !== 'customer') { 
    die("Akses ditolak."); 
}
$user_id = $_SESSION['user_id'];

$sql_user = "SELECT * FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($koneksi, $sql_user);
mysqli_stmt_bind_param($stmt_user, "i", $user_id);
mysqli_stmt_execute($stmt_user);
$user_data = mysqli_stmt_get_result($stmt_user)->fetch_assoc();
?>

<div class="content-box">
    <h1>Profil Saya</h1>
    <p>Perbarui informasi akun dan alamat Anda.</p>

    <?php if (isset($_GET['pesan'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['pesan'])); ?>
        </div>
    <?php endif; ?>
     <?php if (isset($_GET['error'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
        </div>
    <?php endif; ?>

    <form action="proses_profil.php" method="POST" style="max-width: 700px;">
        <h3>Informasi Akun</h3>
        <div style="margin-bottom: 15px;">
            <label>Nama Lengkap:</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Nomor Telepon:</label>
                <input type="tel" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
            </div>
        </div>
        <div style="margin-bottom: 15px;">
            <label>Password Baru (kosongkan jika tidak ingin mengubah):</label>
            <input type="password" name="password" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        
        <hr style="margin: 30px 0;">

        <h3>Alamat Pengiriman (Opsional)</h3>
        <p>Jika diisi, alamat ini akan otomatis digunakan saat checkout.</p>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div style="margin-bottom: 15px;">
                <label>Provinsi:</label>
                <input type="text" name="shipping_province" value="<?php echo htmlspecialchars($user_data['shipping_province'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kota/Kabupaten:</label>
                <input type="text" name="shipping_city" value="<?php echo htmlspecialchars($user_data['shipping_city'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
             <div style="margin-bottom: 15px;">
                <label>Kecamatan:</label>
                <input type="text" name="shipping_subdistrict" value="<?php echo htmlspecialchars($user_data['shipping_subdistrict'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kelurahan/Area:</label>
                <input type="text" name="shipping_area" value="<?php echo htmlspecialchars($user_data['shipping_area'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <label>Kode Pos:</label>
                <input type="text" name="shipping_post_code" value="<?php echo htmlspecialchars($user_data['shipping_post_code'] ?? ''); ?>" style="width: 100%; padding: 8px;">
            </div>
        </div>
        <div style="margin-bottom: 15px; grid-column: 1 / -1;">
            <label>Alamat Lengkap:</label>
            <textarea name="shipping_address" rows="4" placeholder="Nama jalan, nomor rumah, RT/RW, patokan, dll." style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($user_data['shipping_address'] ?? ''); ?></textarea>
        </div>
        
        <button type="submit" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Simpan Perubahan</button>
    </form>
</div>

<?php 
require_once 'includes/footer.php'; 
?>