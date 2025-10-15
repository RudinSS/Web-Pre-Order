<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id === 0) {
    die("Error: ID Produk tidak valid.");
}

$sql_edit = "SELECT * FROM products WHERE id = ?";
$stmt = mysqli_prepare($koneksi, $sql_edit);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Error: Produk tidak ditemukan.");
}
?>

<div class="content-box">
    <h2>Edit Produk</h2>
    <p>Anda sedang mengubah data untuk SKU: <strong><?php echo htmlspecialchars($data['sku']); ?></strong></p>
    
    <form action="proses_produk.php" method="POST" style="max-width: 500px;">
        <input type="hidden" name="aksi" value="ubah">
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">

        <div style="margin-bottom: 15px;">
            <label>SKU (Stock Keeping Unit)</label>
            <input type="text" name="sku" value="<?php echo htmlspecialchars($data['sku']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Brand</label>
            <select name="brand_id" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                <?php
                $sql_brands = "SELECT id, brand_name FROM brands ORDER BY brand_name";
                $result_brands = mysqli_query($koneksi, $sql_brands);
                while ($row = mysqli_fetch_assoc($result_brands)) {
                    $selected = ($row['id'] == $data['brand_id']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' " . $selected . ">" . htmlspecialchars($row['brand_name']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div style="margin-bottom: 15px;">
            <label>Harga</label>
            <input type="number" name="base_price" value="<?php echo htmlspecialchars($data['base_price']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>
        <div style="margin-bottom: 15px;">
            <label>Batas Waktu PO (Kosongkan jika tidak ada batas)</label>
            <?php
            $deadline_value = $data['deadline_po'] ? date('Y-m-d\TH:i', strtotime($data['deadline_po'])) : '';
            ?>
            <input type="datetime-local" name="deadline_po" value="<?php echo $deadline_value; ?>" style="width: 100%; padding: 8px; box-sizing: border-box;">
        </div>

        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Simpan Perubahan</button>
        <a href="admin_produk.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<?php 
require_once '../includes/footer.php'; 
?>