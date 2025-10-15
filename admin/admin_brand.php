<?php 
require_once '../includes/header.php';
require_once '../includes/koneksi.php'; 
?>

<div class="content-box">
    <div style="display:flex; justify-content: space-between; align-items: center;">
        <h1>Manajemen Brand</h1>
        <a href="admin_produk.php" style="text-decoration: none; color: #333;">&larr; Kembali ke Manajemen Produk</a>
    </div>

    <?php if (isset($_GET['pesan'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;">
            <?php echo htmlspecialchars(urldecode($_GET['pesan'])); ?>
        </div>
    <?php endif; ?>

    <div style="display: flex; gap: 30px;">
        <div style="flex: 1;">
            <h3>Tambah Brand Baru</h3>
            <form action="proses_brand.php" method="POST">
                <input type="hidden" name="aksi" value="tambah">
                <div style="margin-bottom: 15px;">
                    <label for="brand_name">Nama Brand:</label>
                    <input type="text" id="brand_name" name="brand_name" required style="width: 100%; padding: 8px; box-sizing: border-box;">
                </div>
                <button type="submit" style="background-color: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 14px;">Tambah Brand</button>
            </form>
        </div>

        <div style="flex: 2;">
            <h3>Daftar Brand</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid #ddd; padding: 8px;">Nama Brand</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT * FROM brands ORDER BY brand_name ASC";
                    $result = mysqli_query($koneksi, $sql);
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['brand_name']) . "</td>";
                            echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                            echo "<a href='form_edit_brand.php?id=" . $row['id'] . "'>Edit</a> | ";
                            echo "<a href='proses_brand.php?aksi=hapus&id=" . $row['id'] . "' onclick='return confirm(\"Yakin hapus brand ini?\")'>Hapus</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2' style='border: 1px solid #ddd; padding: 8px; text-align:center;'>Belum ada brand.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php 
require_once '../includes/footer.php';
?>