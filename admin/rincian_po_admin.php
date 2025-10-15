<?php 
session_start();
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

if (isset($_POST['selected_products']) && !empty($_POST['selected_products'])) {
    $_SESSION['admin_po_cart'] = $_POST['selected_products'];
}
if (empty($_SESSION['admin_po_cart'])) {
    header("Location: po_baru_admin.php?pesan=belum_pilih");
    exit;
}
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<div class="content-box">
    <h1>Langkah 2 (Admin): Pilih Customer & Isi Kuantitas</h1>
    <p>Pilih customer yang memesan dari daftar, lalu isi jumlah (Qty) untuk setiap produk.</p>

    <form id="order-form" action="checkout_admin.php" method="POST">
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; max-width: 500px;">
            <label for="customer_id" style="font-weight: bold; font-size: 1.1em;">Pesanan untuk Customer:</label>
            <select name="customer_id" id="customer_id" required style="width: 100%; padding: 8px; margin-top: 8px; font-size: 1em;">
                <option value="">-- Cari dan Pilih Customer --</option>
                <?php
                $sql_customers = "SELECT id, full_name, username FROM users WHERE role = 'customer' ORDER BY full_name";
                $result_customers = mysqli_query($koneksi, $sql_customers);
                while ($customer = mysqli_fetch_assoc($result_customers)) {
                    echo "<option value='" . $customer['id'] . "'>" . htmlspecialchars($customer['full_name']) . " (" . htmlspecialchars($customer['username']) . ")</option>";
                }
                ?>
            </select>
        </div>

        <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="border: 1px solid #ddd; padding: 8px;">SKU</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Nama Produk</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Harga Satuan</th>
                    <th style="border: 1px solid #ddd; padding: 8px; width: 100px;">Qty</th>
                    <th style="border: 1px solid #ddd; padding: 8px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $selected_ids = $_SESSION['admin_po_cart'];
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $types = str_repeat('i', count($selected_ids));

                $sql = "SELECT p.id, p.sku, p.base_price, p.model_name 
                        FROM products p 
                        WHERE p.id IN ($placeholders) ORDER BY p.sku";
                
                $stmt = mysqli_prepare($koneksi, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$selected_ids);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($row['sku']); ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?php echo htmlspecialchars($row['model_name']); ?></td>
                        <td class="harga-satuan" data-harga="<?php echo $row['base_price']; ?>" style="border: 1px solid #ddd; padding: 8px;">
                            Rp <?php echo number_format($row['base_price'], 0, ',', '.'); ?>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <input type="number" name="qty[<?php echo $row['id']; ?>]" min="0" value="0" required class="qty-input" style="width: 70px; text-align: center; padding: 5px;">
                        </td>
                        <td class="subtotal" style="border: 1px solid #ddd; padding: 8px;">Rp 0</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; font-size: 1.2em;">
                    <td colspan="4" style="text-align: right; padding: 10px; border: 1px solid #ddd;">Total Keseluruhan:</td>
                    <td id="grand-total" style="padding: 10px; border: 1px solid #ddd;">Rp 0</td>
                </tr>
            </tfoot>
        </table>
        
        <button type="submit" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px; margin-top: 20px;">Lanjut ke Alamat Pengiriman</button>
        <a href="po_baru_admin.php" style="margin-left: 10px;">Kembali untuk Pilih Ulang</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inisialisasi Select2 pada dropdown customer
    $('#customer_id').select2({
        placeholder: "-- Cari dan Pilih Customer --",
        allowClear: true
    });

    const qtyInputs = document.querySelectorAll('.qty-input');
    const orderForm = document.getElementById('order-form');

    function calculateTotal() {
        let grandTotal = 0;
        let totalQty = 0;
        qtyInputs.forEach(input => {
            const row = input.closest('tr');
            const hargaSatuanEl = row.querySelector('.harga-satuan');
            const subtotalEl = row.querySelector('.subtotal');
            const harga = parseFloat(hargaSatuanEl.dataset.harga);
            const qty = parseInt(input.value) || 0;
            const subtotal = harga * qty;
            subtotalEl.textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
            grandTotal += subtotal;
            totalQty += qty;
        });
        document.getElementById('grand-total').textContent = 'Rp ' + grandTotal.toLocaleString('id-ID');
        return totalQty;
    }

    qtyInputs.forEach(input => { input.addEventListener('input', calculateTotal); });

    orderForm.addEventListener('submit', function(e) {
        if (document.getElementById('customer_id').value === "") {
             e.preventDefault();
             alert('Harap pilih customer terlebih dahulu.');
             return;
        }
        const totalQty = calculateTotal();
        if (totalQty === 0) {
            e.preventDefault();
            alert('Harap isi jumlah pesanan (Qty) minimal untuk satu produk.');
        }
    });
});
</script>

<?php 
require_once '../includes/footer.php'; 
?>