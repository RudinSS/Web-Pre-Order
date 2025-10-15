<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

$brand_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($brand_id === 0) die("Error: ID Brand tidak valid.");

$sql_edit = "SELECT * FROM brands WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $sql_edit);
mysqli_stmt_bind_param($stmt, "i", $brand_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
if (!$data) die("Error: Brand tidak ditemukan.");
?>

<div class="content-box">
    <h2>Edit Brand</h2>
    <form action="proses_brand.php" method="POST" style="max-width: 400px;">
        <input type="hidden" name="aksi" value="ubah">
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
        <div style="margin-bottom: 15px;">
            <label for="brand_name">Nama Brand:</label>
            <input type="text" id="brand_name" name="brand_name" value="<?php echo htmlspecialchars($data['brand_name']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <button type="submit" style="background-color: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">Simpan Perubahan</button>
        <a href="admin_brand.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<?php 
require_once '../includes/footer.php'; 
?>