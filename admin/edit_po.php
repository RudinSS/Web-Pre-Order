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

// Ambil data pesanan, pastikan statusnya 'pending'
$sql_order = "SELECT po.*, u.full_name 
              FROM pre_orders po JOIN users u ON po.customer_id = u.id 
              WHERE po.id = ? AND po.status = 'pending'";
$stmt_order = mysqli_prepare($koneksi, $sql_order);
mysqli_stmt_bind_param($stmt_order, "i", $order_id);
mysqli_stmt_execute($stmt_order);
$order = mysqli_stmt_get_result($stmt_order)->fetch_assoc();

if (!$order) {
    die("Pesanan tidak ditemukan atau statusnya bukan 'pending', sehingga tidak dapat diedit.");
}
?>

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<div class="content-box">
    <h1>Edit Pesanan #<?php echo $order['id']; ?></h1>
    <p>Customer: <strong><?php echo htmlspecialchars($order['full_name']); ?></strong></p>

    <form action="proses_edit_po.php" method="POST">
        <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

        <h3>Item Pesanan</h3>
        <table id="items-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px;">SKU</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Nama Produk</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Harga Satuan</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 100px;">Qty</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Subtotal</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="items-tbody">
                <?php
                $sql_items = "SELECT oi.*, p.sku, p.model_name
                              FROM pre_order_items oi JOIN products p ON oi.product_id = p.id
                              WHERE oi.order_id = ?";
                $stmt_items = mysqli_prepare($koneksi, $sql_items);
                mysqli_stmt_bind_param($stmt_items, "i", $order_id);
                mysqli_stmt_execute($stmt_items);
                $result_items = mysqli_stmt_get_result($stmt_items);
                
                while ($item = mysqli_fetch_assoc($result_items)): 
                ?>
                    <tr class="item-row">
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <?php echo htmlspecialchars($item['sku']); ?>
                            <input type="hidden" name="product_id[]" value="<?php echo $item['product_id']; ?>">
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($item['model_name']); ?></td>
                        <td class="price" data-price="<?php echo $item['price']; ?>" style="border: 1px solid #ddd; padding: 8px;">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><input type="number" name="quantity[]" class="qty" value="<?php echo $item['quantity']; ?>" min="1" style="width: 70px;"></td>
                        <td class="subtotal" style="border: 1px solid #ddd; padding: 8px;">Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><button type="button" class="remove-item" style="color:red; border:none; background:none; cursor:pointer;">Hapus</button></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; font-size: 1.1em;">
                    <td colspan="4" style="text-align: right; padding: 10px;">Total Keseluruhan:</td>
                    <td id="grand-total" colspan="2" style="padding: 10px;"></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin: 20px 0;">
            <label for="search-product"><strong>Tambah Produk:</strong> Cari SKU atau Nama Produk</label>
            <input type="text" id="search-product" style="width: 400px; padding: 8px;">
        </div>
        
        <hr style="margin: 30px 0; border: 1px solid #eee;">

        <h3>Alamat Pengiriman</h3>
        <div style="max-width: 800px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="margin-bottom: 15px;">
                    <label>Nama Penerima:</label>
                    <input type="text" name="shipping_name" value="<?php echo htmlspecialchars($order['shipping_name']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>No. Telepon Penerima:</label>
                    <input type="tel" name="shipping_phone" value="<?php echo htmlspecialchars($order['shipping_phone']); ?>" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Provinsi:</label>
                    <input type="text" name="shipping_province" value="<?php echo htmlspecialchars($order['shipping_province'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
                </div>
                 <div style="margin-bottom: 15px;">
                    <label>Kota/Kabupaten:</label>
                    <input type="text" name="shipping_city" value="<?php echo htmlspecialchars($order['shipping_city'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
                </div>
                 <div style="margin-bottom: 15px;">
                    <label>Kecamatan:</label>
                    <input type="text" name="shipping_subdistrict" value="<?php echo htmlspecialchars($order['shipping_subdistrict'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Kelurahan/Area:</label>
                    <input type="text" name="shipping_area" value="<?php echo htmlspecialchars($order['shipping_area'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
                </div>
                <div style="margin-bottom: 15px;">
                    <label>Kode Pos:</label>
                    <input type="text" name="shipping_post_code" value="<?php echo htmlspecialchars($order['shipping_post_code'] ?? ''); ?>" required style="width: 100%; padding: 8px;">
                </div>
            </div>
            <div style="margin-bottom: 15px;">
                <label>Alamat Lengkap:</label>
                <textarea name="shipping_address" rows="4" required style="width: 100%; padding: 8px; box-sizing: border-box;"><?php echo htmlspecialchars($order['shipping_address'] ?? ''); ?></textarea>
            </div>
        </div>
        <hr style="margin: 30px 0;">

        <button type="submit" style="background-color: #28a745; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px;">Simpan Semua Perubahan</button>
        <a href="admin_po_masuk.php" style="margin-left: 10px;">Batal</a>
    </form>
</div>

<script>
$(document).ready(function() {
    function calculateTotal() {
        let grandTotal = 0;
        $('.item-row').each(function() {
            const row = $(this);
            const price = parseFloat(row.find('.price').data('price'));
            const qty = parseInt(row.find('.qty').val()) || 0;
            const subtotal = price * qty;
            row.find('.subtotal').text('Rp ' + subtotal.toLocaleString('id-ID'));
            grandTotal += subtotal;
        });
        $('#grand-total').text('Rp ' + grandTotal.toLocaleString('id-ID'));
    }

    calculateTotal();

    $('#items-tbody').on('input', '.qty', calculateTotal);

    $('#items-tbody').on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    $("#search-product").autocomplete({
        source: "search_produk.php",
        minLength: 2,
        select: function(event, ui) {
            const item = ui.item;
            let isExist = false;
            $('input[name="product_id[]"]').each(function() {
                if ($(this).val() == item.id) {
                    isExist = true;
                    const qtyInput = $(this).closest('tr').find('.qty');
                    qtyInput.val(parseInt(qtyInput.val()) + 1);
                    calculateTotal();
                }
            });

            if (!isExist) {
                const newRow = `
                    <tr class="item-row">
                        <td style="border: 1px solid #ddd; padding: 8px;"><input type="hidden" name="product_id[]" value="${item.id}">${item.sku}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">${item.name}</td>
                        <td class="price" data-price="${item.price}" style="border: 1px solid #ddd; padding: 8px;">Rp ${item.price.toLocaleString('id-ID')}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><input type="number" name="quantity[]" class="qty" value="1" min="1" style="width: 70px;"></td>
                        <td class="subtotal" style="border: 1px solid #ddd; padding: 8px;">Rp ${item.price.toLocaleString('id-ID')}</td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><button type="button" class="remove-item" style="color:red; border:none; background:none; cursor:pointer;">Hapus</button></td>
                    </tr>
                `;
                $('#items-tbody').append(newRow);
                calculateTotal();
            }

            $(this).val('');
            return false;
        }
    });
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>