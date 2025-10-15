<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

// Ambil ID user dari URL dan pastikan valid
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id === 0) {
    die("Error: ID User tidak valid.");
}

// Ambil data user yang akan diubah
$sql_user = "SELECT username, full_name FROM users WHERE id = ? AND role = 'customer'";
$stmt = mysqli_prepare($koneksi, $sql_user);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Error: User tidak ditemukan atau bukan customer.");
}
?>

<div class="content-box">
    <h2>Ganti Password</h2>
    <p>Anda akan mengubah password untuk user: <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> (Username: <?php echo htmlspecialchars($user['username']); ?>)</p>

    <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #d4edda; color: #155724;">
            Password berhasil diperbarui.
        </div>
    <?php endif; ?>
    
    <form action="proses_ganti_password.php" method="POST" style="max-width: 400px;">
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
        <div style="margin-bottom: 15px;">
            <label for="new_password">Password Baru:</label>
            <input type="password" id="new_password" name="new_password" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Simpan Password Baru</button>
        <a href="admin_pengguna.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<?php 
require_once '../includes/footer.php'; 
?>