<?php 
// Pastikan sesi dimulai
session_start();

require_once 'includes/header.php'; 
require_once 'includes/koneksi.php';

// Cek jika customer datang dari halaman seleksi produk
if (isset($_POST['selected_products']) && !empty($_POST['selected_products'])) {
    $_SESSION['po_cart'] = $_POST['selected_products'];
}

// Jika tidak ada produk di keranjang, kembalikan
if (empty($_SESSION['po_cart'])) {
    header("Location: po_baru.php?pesan=belum_pilih");
    exit;
}
?>

<div class="content-box">
    <h1>Langkah 2: Isi Kuantitas Pesanan</h1>
    <p>Silakan isi jumlah (Qty) untuk setiap produk. Setiap produk harus memiliki minimal 1 Qty.</p>

    <form id="order-form" action="checkout.php" method="POST">
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
                $selected_ids = $_SESSION['po_cart'];
                $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
                $types = str_repeat('i', count($selected_ids));

                $sql = "SELECT p.id, p.sku, p.base_price, p.model_name 
                        FROM products p 
                        WHERE p.id IN ($placeholders)
                        ORDER BY p.sku";
                
                $stmt = mysqli_prepare($koneksi, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$selected_ids);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if ($result && mysqli_num_rows($result) > 0) {
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
                    <?php endwhile;
                }
                ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; font-size: 1.2em;">
                    <td colspan="4" style="text-align: right; padding: 10px; border: 1px solid #ddd;">Total Keseluruhan:</td>
                    <td id="grand-total" style="padding: 10px; border: 1px solid #ddd;">Rp 0</td>
                </tr>
            </tfoot>
        </table>
        
        <button type="submit" style="background-color: #007bff; color: white; padding: 10px 20px; border: none; font-size: 16px; cursor: pointer; border-radius: 5px; margin-top: 20px;">Lanjut ke Alamat Pengiriman</button>
        <a href="po_baru.php" style="margin-left: 10px;">Kembali untuk Pilih Ulang</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    qtyInputs.forEach(input => {
        input.addEventListener('input', calculateTotal);
    });

    orderForm.addEventListener('submit', function(e) {
        const totalQty = calculateTotal();
        
        if (totalQty === 0) {
            e.preventDefault();
            alert('Harap isi jumlah pesanan (Qty) minimal untuk satu produk.');
            return;
        }

        let hasZeroQty = false;
        qtyInputs.forEach(input => {
            const qty = parseInt(input.value) || 0;
            if (qty <= 0) {
                hasZeroQty = true;
            }
        });

        if (hasZeroQty) {
            e.preventDefault();
            alert('Setiap produk yang dipesan harus memiliki jumlah (Qty) minimal 1.');
        }
    });
});
</script>

<?php 
require_once 'includes/footer.php'; 
?>