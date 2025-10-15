<?php 
require_once 'includes/header.php'; 
require_once 'includes/koneksi.php';
?>

<style>
    /* Style untuk kartu statistik & perbandingan harian */
    .stat-cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
    .card { flex: 1; min-width: 200px; background-color: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 5px solid #007bff; }
    .card h3 { margin-top: 0; font-size: 16px; color: #555; }
    .card p { margin-bottom: 5px; font-size: 28px; font-weight: 600; }
    .card.pending { border-color: #ffc107; }
    .card.confirmed { border-color: #17a2b8; }
    .card.users { border-color: #6f42c1; }
    .card.daily { border-color: #28a745; }
    .card .comparison-text { font-size: 15px; font-weight: 500; }
    .comparison-text.positive { color: #28a745; }
    .comparison-text.negative { color: #dc3545; }
    .comparison-text.neutral { color: #6c757d; }
    
    /* Style untuk tombol akses cepat */
    .quick-access { display: flex; gap: 15px; margin-bottom: 30px; }
    .qa-btn { flex: 1; padding: 15px; text-align: center; text-decoration: none; color: white; font-weight: 500; border-radius: 5px; background-color: #007bff; transition: background-color 0.2s; }
    .qa-btn:hover { background-color: #0056b3; }
    .qa-btn.secondary { background-color: #6c757d; }
    .qa-btn.secondary:hover { background-color: #5a6268; }

    /* Style untuk tabel di bawah */
    .bottom-stats-container { display: flex; gap: 30px; flex-wrap: wrap; }
    .stat-table-wrapper { flex: 1; min-width: 300px; }
    .stat-table-wrapper h4, .recent-orders h4 { margin-top: 0; }
    .stat-table-wrapper table, .recent-orders table { width: 100%; border-collapse: collapse; }
    .stat-table-wrapper th, .stat-table-wrapper td, .recent-orders th, .recent-orders td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .stat-table-wrapper th, .recent-orders th { background-color: #f2f2f2; }

    /* Style untuk Panel Status Akun */
    .account-status-panel { padding: 15px; border-radius: 8px; margin-bottom: 30px; }
    .account-status-panel.linked { background-color: #d4edda; border-left: 5px solid #28a745; }
    .account-status-panel.unlinked { background-color: #fff3cd; border-left: 5px solid #ffc107; }
    .account-status-panel h4 { margin-top: 0; margin-bottom: 10px; }
    .account-status-panel p { margin: 0 0 15px 0; }
    .account-status-panel .status-label { font-weight: bold; padding: 3px 8px; border-radius: 4px; color: white; }
    .account-status-panel .status-label.linked { background-color: #28a745; }
    .account-status-panel .status-label.unlinked { background-color: #ffc107; color: #333; }
    .btn-action { display: inline-block; text-decoration: none; background-color: #007bff; color: white; padding: 10px 15px; border-radius: 5px; font-weight: bold; border: none; cursor: pointer; }
    .btn-wordpress { background-color: #6c757d; }
    #connect-form { max-width: 400px; }
    #connect-form input { width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 10px; }
</style>

<?php if ($_SESSION['role'] == 'admin'): ?>
    <?php
    // --- SEMUA QUERY UNTUK DASHBOARD ADMIN ---

    // 1. Statistik Kartu Atas
    $q_pending = mysqli_query($koneksi, "SELECT COUNT(id) as count FROM pre_orders WHERE status = 'pending'");
    $pending_count = mysqli_fetch_assoc($q_pending)['count'];
    $q_confirmed = mysqli_query($koneksi, "SELECT COUNT(id) as count FROM pre_orders WHERE status = 'confirmed'");
    $confirmed_count = mysqli_fetch_assoc($q_confirmed)['count'];
    $q_users = mysqli_query($koneksi, "SELECT COUNT(id) as count FROM users WHERE role = 'customer'");
    $users_count = mysqli_fetch_assoc($q_users)['count'];

    // 2. Statistik Omzet Harian
    $q_today = mysqli_query($koneksi, "SELECT SUM(total_amount) as omzet FROM pre_orders WHERE DATE(order_date) = CURDATE() AND parent_order_id IS NULL");
    $today_omzet = mysqli_fetch_assoc($q_today)['omzet'] ?? 0;
    $q_yesterday = mysqli_query($koneksi, "SELECT SUM(total_amount) as omzet FROM pre_orders WHERE DATE(order_date) = CURDATE() - INTERVAL 1 DAY AND parent_order_id IS NULL");
    $yesterday_omzet = mysqli_fetch_assoc($q_yesterday)['omzet'] ?? 0;
    
    $comparison_class = 'neutral'; $arrow = ''; $percentage_diff = 0;
    if ($yesterday_omzet > 0) { $percentage_diff = (($today_omzet - $yesterday_omzet) / $yesterday_omzet) * 100; } 
    elseif ($today_omzet > 0) { $percentage_diff = 100; }
    if ($percentage_diff > 0) { $comparison_class = 'positive'; $arrow = '▲'; } 
    elseif ($percentage_diff < 0) { $comparison_class = 'negative'; $arrow = '▼'; }

    // --- PERBAIKAN DI SINI ---
    // 3. Query SKU Terlaris (hanya dari pesanan 'pending')
    $sql_top_sku = "SELECT p.sku, SUM(oi.quantity) as total_qty 
                    FROM pre_order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN pre_orders po ON oi.order_id = po.id
                    WHERE po.status = 'pending'
                    GROUP BY oi.product_id 
                    ORDER BY total_qty DESC 
                    LIMIT 5";
    $result_top_sku = mysqli_query($koneksi, $sql_top_sku);

    // 4. Query Customer Teratas berdasarkan nominal (hanya dari pesanan 'pending')
    $sql_top_customer = "SELECT u.full_name, SUM(po.total_amount) as total_spent
                         FROM pre_orders po
                         JOIN users u ON po.customer_id = u.id
                         WHERE po.status = 'pending'
                         GROUP BY po.customer_id
                         ORDER BY total_spent DESC
                         LIMIT 5";
    $result_top_customer = mysqli_query($koneksi, $sql_top_customer);
    // --- AKHIR PERBAIKAN ---
    ?>
    <div class="content-box">
        <h2>Dashboard Admin</h2>
        
        <h4>Statistik Cepat</h4>
        <div class="stat-cards">
            <div class="card daily">
                <h3>Omzet Hari Ini</h3>
                <p>Rp <?php echo number_format($today_omzet, 0, ',', '.'); ?></p>
                <span class="comparison-text <?php echo $comparison_class; ?>">
                    <?php if ($arrow): ?><?php echo $arrow; ?> <?php echo round(abs($percentage_diff), 1); ?>%
                    <?php else: ?> - <?php endif; ?>
                    <span style="font-size: 12px; color: #6c757d;">vs kemarin</span>
                </span>
            </div>
            <div class="card pending"><h3>Total Pesanan Pending</h3><p><?php echo $pending_count; ?></p></div>
            <div class="card confirmed"><h3>Total Psn. Dikonfirmasi</h3><p><?php echo $confirmed_count; ?></p></div>
            <div class="card users"><h3>Total Customer</h3><p><?php echo $users_count; ?></p></div>
        </div>

        <h4>Akses Cepat</h4>
        <div class="quick-access">
            <a href="/preorder1/admin/po_baru_admin.php" class="qa-btn">Buat PO untuk Customer</a>
            <a href="/preorder1/admin/admin_po_masuk.php" class="qa-btn secondary">Lihat Semua Pesanan</a>
        </div>
        
        <hr style="margin: 30px 0; border-top: 1px solid #eee;">

        <div class="bottom-stats-container">
            <div class="stat-table-wrapper">
                <h4>SKU Terlaris (Pending)</h4>
                <table>
                    <thead><tr><th>Peringkat</th><th>SKU</th><th>Total Dipesan</th></tr></thead>
                    <tbody>
                        <?php
                        if ($result_top_sku && mysqli_num_rows($result_top_sku) > 0) {
                            $rank = 1;
                            while($row = mysqli_fetch_assoc($result_top_sku)) {
                                echo "<tr><td>".$rank++."</td><td>".htmlspecialchars($row['sku'])."</td><td>".number_format($row['total_qty'])." pcs</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center;'>Belum ada data pesanan pending.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="stat-table-wrapper">
                <h4>Top Customer by Nominal (Pending)</h4>
                <table>
                    <thead><tr><th>Peringkat</th><th>Nama Customer</th><th>Total Belanja</th></tr></thead>
                    <tbody>
                         <?php
                        if ($result_top_customer && mysqli_num_rows($result_top_customer) > 0) {
                            $rank = 1;
                            while($row = mysqli_fetch_assoc($result_top_customer)) {
                                echo "<tr><td>".$rank++."</td><td>".htmlspecialchars($row['full_name'])."</td><td>Rp ".number_format($row['total_spent'], 0, ',', '.')."</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='3' style='text-align:center;'>Belum ada data customer pending.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
<?php elseif ($_SESSION['role'] == 'customer'): ?>
    <?php
    // --- KODE DASHBOARD CUSTOMER DENGAN TAMPILAN YANG DIPERBAIKI ---
    $customer_id = $_SESSION['user_id'];
    $sql_user = "SELECT full_name, source, email FROM users WHERE id = ?";
    $stmt_user = mysqli_prepare($koneksi, $sql_user);
    mysqli_stmt_bind_param($stmt_user, "i", $customer_id);
    mysqli_stmt_execute($stmt_user);
    $user_data = mysqli_stmt_get_result($stmt_user)->fetch_assoc();
    $customer_name = $user_data['full_name'] ?? $_SESSION['username'];
    $is_linked = ($user_data['source'] === 'order.rumahmadani.com');
    $sql_stats = "SELECT COUNT(id) as total_orders, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders FROM pre_orders WHERE customer_id = ?";
    $stmt_stats = mysqli_prepare($koneksi, $sql_stats); mysqli_stmt_bind_param($stmt_stats, "i", $customer_id); mysqli_stmt_execute($stmt_stats);
    $customer_stats = mysqli_stmt_get_result($stmt_stats)->fetch_assoc();
    $sql_last_order = "SELECT * FROM pre_orders WHERE customer_id = ? ORDER BY order_date DESC LIMIT 1";
    $stmt_last = mysqli_prepare($koneksi, $sql_last_order); mysqli_stmt_bind_param($stmt_last, "i", $customer_id); mysqli_stmt_execute($stmt_last);
    $last_order = mysqli_stmt_get_result($stmt_last)->fetch_assoc();
    ?>
    <div class="content-box">
        <h2>Selamat Datang, <?php echo htmlspecialchars($customer_name); ?>!</h2>
        <p>Gunakan menu di samping untuk memulai atau melihat riwayat pesanan Anda.</p>
        
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'hubung_sukses'): ?>
                <div class="account-status-panel linked">Akun Anda berhasil dihubungkan!</div>
            <?php elseif ($_GET['status'] == 'hubung_gagal'): ?>
                <div class="account-status-panel" style="background-color: #f8d7da; border-left-color: #dc3545;">Gagal menghubungkan akun. Pastikan akun ada di order.rumahmadani.com dan password yang Anda masukkan benar.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="account-status-panel <?php echo $is_linked ? 'linked' : 'unlinked'; ?>">
            <h4>Status Akun Poin</h4>
            <?php if ($is_linked): ?>
                <p>Akun Anda sudah <span class="status-label linked">Terhubung</span> dengan `order.rumahmadani.com`. Poin dari pesanan akan otomatis ditambahkan.</p>
            <?php else: ?>
                <p>Akun Anda <span class="status-label unlinked">Belum Terhubung</span>. Hubungkan akun Anda untuk mendapatkan poin dari setiap pesanan.</p>
                <button id="show-connect-form-btn" class="btn-action">Hubungkan Akun Sekarang</button>
                <form id="connect-form" action="proses_hubungkan_akun.php" method="POST" style="display:none; margin-top: 15px;">
                    <p>Untuk verifikasi, masukkan password akun `order.rumahmadani.com` Anda yang terdaftar dengan email: <strong><?php echo htmlspecialchars($user_data['email']); ?></strong></p>
                    <div class="form-group"><input type="password" name="wp_password" placeholder="Password WordPress Anda" required></div>
                    <button type="submit" class="btn-action">Verifikasi & Hubungkan</button>
                </form>
                <hr style="margin: 20px 0;">
                <p style="font-size: 14px; color: #555;">Belum punya akun di `order.rumahmadani.com`?</p>
                <a href="https://order.rumahmadani.com/my-account/" target="_blank" class="btn-action btn-wordpress">Daftar Akun Baru</a>
            <?php endif; ?>
        </div>
        
        <h4>Ringkasan Pesanan Anda</h4>
        <div class="stat-cards">
            <div class="card">
                <h3>Total Pesanan Saya</h3>
                <p><?php echo $customer_stats['total_orders']; ?></p>
            </div>
            <div class="card pending">
                <h3>Pesanan Pending</h3>
                <p><?php echo $customer_stats['pending_orders']; ?></p>
            </div>
        </div>

        <h4>Akses Cepat</h4>
        <div class="quick-access">
            <a href="/preorder1/po_baru.php" class="qa-btn">Buat Pre-Order Baru</a>
            <a href="/preorder1/riwayat_po.php" class="qa-btn secondary">Lihat Riwayat PO</a>
        </div>

        <?php if($last_order): ?>
        <div class="recent-orders">
            <h4>Pesanan Terakhir Anda</h4>
            <table>
                <thead><tr><th>ID Pesanan</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <tr>
                        <td>#<?php echo $last_order['id']; ?></td>
                        <td><?php echo date("d M Y, H:i", strtotime($last_order['order_date'])); ?></td>
                        <td>Rp <?php echo number_format($last_order['total_amount'], 0, ',', '.'); ?></td>
                        <td style="text-transform: capitalize; font-weight: bold;"><?php echo htmlspecialchars($last_order['status']); ?></td>
                        <td><a href="/preorder1/detail_po_customer.php?id=<?php echo $last_order['id']; ?>">Lihat Detail</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showBtn = document.getElementById('show-connect-form-btn');
            const connectForm = document.getElementById('connect-form');
            if (showBtn) {
                showBtn.addEventListener('click', function() {
                    connectForm.style.display = 'block';
                    showBtn.style.display = 'none';
                });
            }
        });
    </script>
<?php endif; ?>

<?php 
require_once 'includes/footer.php'; 
?>