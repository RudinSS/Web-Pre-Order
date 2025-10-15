<?php 
require_once '../includes/header.php';
require_once '../includes/koneksi.php';
?>

<div class="content-box">
    <h1>Langkah 1 (Admin): Pilih Produk untuk di-PO</h1>
    <p>Gunakan fitur Search dan Filter untuk mencari produk, lalu centang produk yang ingin Anda pesan atas nama customer.</p>

    <div style="margin-bottom: 20px;">
        <label for="brandFilterCustomer">Filter berdasarkan Brand:</label>
        <select id="brandFilterCustomer" style="padding: 5px;">
            <option value="">Semua Brand</option>
            <?php
            $sql_brands = "SELECT brand_name FROM brands ORDER BY brand_name";
            $result_brands = mysqli_query($koneksi, $sql_brands);
            while ($row_brand = mysqli_fetch_assoc($result_brands)) {
                echo "<option value='" . htmlspecialchars($row_brand['brand_name']) . "'>" . htmlspecialchars($row_brand['brand_name']) . "</option>";
            }
            ?>
        </select>
    </div>

    <form id="po-selection-form" action="rincian_po_admin.php" method="POST">
        <table id="po-product-table" class="display" style="width:100%;">
            <thead>
                <tr>
                    <th>Pilih</th>
                    <th>SKU</th>
                    <th>Brand</th>
                    <th>Harga</th>
                    <th>Tgl. Ditambahkan</th>
                    <th>Batas Waktu PO</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT p.id, p.sku, p.base_price, p.image_url, b.brand_name, p.created_at, p.deadline_po 
                        FROM products p 
                        LEFT JOIN brands b ON p.brand_id = b.id 
                        WHERE p.deadline_po IS NULL OR p.deadline_po > NOW()
                        ORDER BY p.created_at DESC";
                $result = mysqli_query($koneksi, $sql);
                while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td style="text-align: center;"><input type="checkbox" name="selected_products[]" value="<?php echo $row['id']; ?>"></td>
                        <td>
                            <?php if (!empty($row['image_url'])): ?>
                                <a href="#" class="image-link" data-image-url="<?php echo htmlspecialchars($row['image_url']); ?>">
                                    <?php echo htmlspecialchars($row['sku']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($row['sku']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                        <td data-order="<?php echo $row['base_price']; ?>">Rp <?php echo number_format($row['base_price'], 0, ',', '.'); ?></td>
                        <td data-order="<?php echo strtotime($row['created_at']); ?>"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php 
                            if ($row['deadline_po']) {
                                echo "<strong style='color: #28a745;'>" . date("d M Y, H:i", strtotime($row['deadline_po'])) . "</strong>";
                            } else {
                                echo "Tanpa Batas";
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <button type="submit" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px; margin-top: 20px;">Lanjut ke Rincian Pesanan</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        var table = $('#po-product-table').DataTable({
            "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
            "order": [[ 4, "desc" ]] 
        });

        $('#brandFilterCustomer').on('change', function(){
            table.column(2).search(this.value).draw();
        });

        $('#po-selection-form').on('submit', function(e){
            e.preventDefault();
            var form = this;
            $(form).find('input[type="hidden"][name="selected_products[]"]').remove();
            var selected_rows = table.$('input[type="checkbox"]:checked');
            if(selected_rows.length === 0){
               alert('Silakan pilih minimal satu produk.');
               return false;
            }
            selected_rows.each(function(){
                $(form).append(
                    $('<input>').attr('type', 'hidden').attr('name', 'selected_products[]').val($(this).val())
                );
            });
            form.submit();
        });
    });
</script>

<?php 
require_once '../includes/footer.php'; 
?>