<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';
?>

<div class="content-box">
    <h2>Tambah Produk Baru (Massal)</h2>
    <p>Anda bisa menambahkan beberapa SKU sekaligus. Pastikan setiap SKU unik.</p>
    
    <form action="proses_produk.php" method="POST">
        <input type="hidden" name="aksi" value="tambah_massal">

        <div id="product-entry-container">
            <div class="product-row" style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                <div style="flex: 3;">
                    <label>SKU (Stock Keeping Unit)</label>
                    <input type="text" name="sku[]" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex: 2;">
                    <label>Brand</label>
                    <select name="brand_id[]" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                        <option value="">-- Pilih Brand --</option>
                        <?php
                        $sql_brands = "SELECT id, brand_name FROM brands ORDER BY brand_name";
                        $result_brands = mysqli_query($koneksi, $sql_brands);
                        while ($row = mysqli_fetch_assoc($result_brands)) {
                            echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['brand_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div style="flex: 2;">
                    <label>Harga</label>
                    <input type="number" name="base_price[]" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="flex: 2;">
                    <label>Batas Waktu PO (Opsional)</label>
                    <input type="datetime-local" name="deadline_po[]" style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="align-self: flex-end;">
                    </div>
            </div>
        </div>

        <button type="button" id="addRowBtn" style="background-color: #5bc0de; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer;">+ Tambah Baris</button>
        <hr style="margin: 20px 0;">
        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Simpan Semua Produk</button>
        <a href="admin_produk.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<script>
document.getElementById('addRowBtn').addEventListener('click', function() {
    const container = document.getElementById('product-entry-container');
    const firstRow = container.querySelector('.product-row');
    const newRow = firstRow.cloneNode(true);
    
    newRow.querySelectorAll('input').forEach(input => input.value = '');

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.textContent = 'Hapus';
    removeBtn.style.cssText = "background-color: #c9302c; color: white; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer;";
    removeBtn.onclick = function() {
        newRow.remove();
    };
    newRow.appendChild(removeBtn);

    container.appendChild(newRow);
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>