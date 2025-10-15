<?php
// --- PENANGANAN AJAX HARUS DI ATAS SEMUA OUTPUT HTML/HEADER ---
if (isset($_GET['action']) && $_GET['action'] == 'get_order_items' && isset($_GET['order_id'])) {
    // Panggil koneksi saja, jangan header/footer
    require_once '../includes/koneksi.php';

    // Fungsi helper untuk memformat rincian item (didefinisikan di bawah)
    function format_order_details_ajax($order_id, $koneksi) {
        $sql_items = "SELECT oi.quantity, oi.price, p.sku, p.model_name
                      FROM pre_order_items oi 
                      JOIN products p ON oi.product_id = p.id
                      WHERE oi.order_id = ?";
        $stmt_items = mysqli_prepare($koneksi, $sql_items);
        mysqli_stmt_bind_param($stmt_items, "i", $order_id);
        mysqli_stmt_execute($stmt_items);
        $result_items = mysqli_stmt_get_result($stmt_items);
        
        $output = '<div style="margin: 15px; padding: 15px; background-color: #f7f7f7; border: 1px solid #ddd; border-radius: 5px;">';
        $output .= '<h4>Rincian Item Pesanan #' . $order_id . '</h4>';
        $output .= '<table style="width:100%; border-collapse: collapse; margin-bottom: 10px; font-size: 14px;">';
        $output .= '<thead><tr style="background-color: #eee;"><th style="padding: 8px; border: 1px solid #ccc;">SKU</th><th style="padding: 8px; border: 1px solid #ccc;">Nama Produk</th><th style="padding: 8px; border: 1px solid #ccc;">Qty</th><th style="padding: 8px; border: 1px solid #ccc;">Subtotal</th></tr></thead>';
        $output .= '<tbody>';
        
        $grand_total = 0;
        if ($result_items) {
            while ($item = mysqli_fetch_assoc($result_items)) {
                $subtotal = $item['quantity'] * $item['price'];
                $grand_total += $subtotal;
                $output .= '<tr>';
                $output .= '<td style="padding: 8px; border: 1px solid #ccc;">' . htmlspecialchars($item['sku']) . '</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #ccc;">' . htmlspecialchars($item['model_name']) . '</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #ccc;">' . $item['quantity'] . '</td>';
                $output .= '<td style="padding: 8px; border: 1px solid #ccc;">Rp ' . number_format($subtotal, 0, ',', '.') . '</td>';
                $output .= '</tr>';
            }
        }
        
        $output .= '</tbody>';
        $output .= '<tfoot><tr style="font-weight: bold; background-color: #e2e2e2;"><td colspan="3" style="padding: 8px; border: 1px solid #ccc; text-align: right;">Total:</td><td style="padding: 8px; border: 1px solid #ccc;">Rp ' . number_format($grand_total, 0, ',', '.') . '</td></tr></tfoot>';
        $output .= '</table>';
        $output .= '</div>';
        
        return $output;
    }
    
    $order_id = (int)$_GET['order_id'];
    echo format_order_details_ajax($order_id, $koneksi);
    exit; // PENTING: Menghentikan eksekusi script setelah mengirim respons AJAX
}
// --- AKHIR PENANGANAN AJAX ---

if (session_status() == PHP_SESSION_NONE) {
    session_name('PREORDER_SESSION');
    session_start();
}
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? 'all';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';


// Query untuk data pesanan utama
$sql = "SELECT 
            po.*, 
            u.full_name, 
            IF(po.parent_order_id IS NOT NULL, 
                (SELECT COUNT(*) FROM pre_orders p_sub WHERE p_sub.parent_order_id = po.parent_order_id AND p_sub.id <= po.id), 
                NULL
            ) as split_index 
        FROM pre_orders po 
        LEFT JOIN users u ON po.customer_id = u.id
        LEFT JOIN pre_order_items oi ON po.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id";

$where_conditions = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where_conditions[] = "(po.id = ? OR u.full_name LIKE ? OR po.jubelio_so_id LIKE ? OR p.sku LIKE ?)";
    $search_param = "%{$search}%";
    $params[] = $search;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'isss';
}
if ($status_filter !== 'all' && $status_filter !== '') {
    $where_conditions[] = "po.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}
if (!empty($start_date)) {
    $where_conditions[] = "DATE(po.order_date) >= ?";
    $params[] = $start_date;
    $types .= 's';
}
if (!empty($end_date)) {
    $where_conditions[] = "DATE(po.order_date) <= ?";
    $params[] = $end_date;
    $types .= 's';
}
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " GROUP BY po.id"; 
$sql .= " ORDER BY po.order_date DESC";
$stmt = mysqli_prepare($koneksi, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<style>
    /* ... (CSS Loading dan Filter Form tidak berubah) ... */
    #loading-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 9999; display: none; justify-content: center; align-items: center; color: white; flex-direction: column; }
    .spinner { border: 8px solid #f3f3f3; border-top: 8px solid #3498db; border-radius: 50%; width: 60px; height: 60px; animation: spin 1.5s linear infinite; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .filter-form { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
    .filter-form .form-group { display: flex; flex-direction: column; }
    .filter-form label { font-size: 12px; margin-bottom: 4px; color: #555; }
    .filter-form input, .filter-form select { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .filter-form .actions { align-self: flex-end; }
    .filter-form button { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; }
    .btn-filter { background-color: #007bff; color: white; }
    .btn-reset { background-color: #6c757d; color: white; text-decoration: none; display: inline-block; }
    
    /* CSS BARU UNTUK DATATABLES */
    .dt-control {
        cursor: pointer;
        font-weight: bold;
        color: #007bff;
        padding-right: 5px;
    }
    tr.dt-hasChild > td.dt-control:before {
        content: "−"; /* Karakter minus */
    }
    tr.dt-hasChild:not(.dt-show) > td.dt-control:before {
        content: "+"; /* Karakter plus */
    }
    
</style>

<div id="loading-overlay"><div class="spinner"></div></div>

<div class="content-box">
    <h1>Daftar Pre-Order Masuk</h1>
    <p>Di bawah ini adalah daftar semua pesanan yang telah dibuat oleh customer.</p>

    <form class="filter-form" method="GET" action="">
        <div class="form-group" style="flex-grow: 1;">
            <label for="search">Cari Pesanan</label>
            <input type="text" id="search" name="search" placeholder="ID Pesanan, Nama, ID Jubelio, SKU Produk..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="form-group">
            <label for="status_filter">Status</label>
            <select id="status_filter" name="status_filter">
                <option value="all" <?php if($status_filter == 'all') echo 'selected'; ?>>Semua Status</option>
                <option value="pending" <?php if($status_filter == 'pending') echo 'selected'; ?>>Pending</option>
                <option value="confirmed" <?php if($status_filter == 'confirmed') echo 'selected'; ?>>Confirmed</option>
                <option value="completed" <?php if($status_filter == 'completed') echo 'selected'; ?>>Completed</option>
                <option value="canceled" <?php if($status_filter == 'canceled') echo 'selected'; ?>>Canceled</option>
            </select>
        </div>
        <div class="form-group">
            <label for="start_date">Dari Tanggal</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
        </div>
        <div class="form-group">
            <label for="end_date">Sampai Tanggal</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
        </div>
        <div class="actions">
            <button type="submit" class="btn-filter">Filter</button>
            <a href="admin_po_masuk.php" class="btn-reset">Reset</a>
        </div>
    </form>

    <?php if (isset($_GET['pesan'])): ?>
        <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; background-color: #d4edda; color: #155724;">
            <?php echo htmlspecialchars(urldecode($_GET['pesan'])); ?>
        </div>
    <?php endif; ?>

    <table id="po-masuk-table" class="display" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th class="dt-control" style="border: 1px solid #ddd; padding: 8px; width: 20px;"></th>
                <th style="border: 1px solid #ddd; padding: 8px;">ID Pesanan</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Nama Customer</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Tanggal Pesan</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Total</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Status</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    // Siapkan ID untuk tampilan dan data-id
                    $data_order_id = $row['id'];
                    $display_order_id = "<strong>PO-" . $row['id'] . "</strong>";
                    if ($row['parent_order_id']) {
                        $display_order_id = "<strong>PO-" . $row['parent_order_id'] . "-" . $row['split_index'] . "</strong><br><span style='font-size:12px; color: #6c757d;'>(Pecahan dari #" . $row['parent_order_id'] . ")</span>";
                    }
                    
                    echo "<tr data-order-id='" . $data_order_id . "'>";
                    echo "<td class='dt-control' style='border: 1px solid #ddd; text-align: center;'></td>"; // Child Row Trigger
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    echo $display_order_id;
                    if (!empty($row['jubelio_so_id'])) {
                         echo "<br><span style='background-color: #28a745; color: white; padding: 2px 6px; font-size: 12px; border-radius: 4px; display: inline-block; margin-top: 4px;'>";
                         echo "Jubelio: " . htmlspecialchars($row['jubelio_so_id']);
                         echo "</span>";
                    }
                    echo "</td>";

                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['full_name']);
                    $admin_marker = '[Dipesankan oleh: ';
                    if (strpos($row['note'], $admin_marker) === 0) {
                        $end_pos = strpos($row['note'], ']');
                        if ($end_pos !== false) {
                            $admin_name = substr($row['note'], strlen($admin_marker), $end_pos - strlen($admin_marker));
                            echo "<br><span style='background-color: #ffc107; color: #333; padding: 2px 6px; font-size: 11px; border-radius: 4px;'>via " . htmlspecialchars($admin_name) . "</span>";
                        }
                    }
                    echo "</td>";

                    echo "<td data-order='" . strtotime($row['order_date']) . "' style='border: 1px solid #ddd; padding: 8px;'>" . date("d M Y, H:i", strtotime($row['order_date'])) . "</td>";
                    echo "<td data-order='" . $row['total_amount'] . "' style='border: 1px solid #ddd; padding: 8px;'>Rp " . number_format($row['total_amount'], 0, ',', '.') . "</td>";
                    
                    // Kolom Status
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    if ($row['status'] == 'canceled') {
                        echo "<span style='padding: 5px 10px; background-color: #f8d7da; color: #721c24; border-radius: 5px;'>" . ucfirst($row['status']) . "</span>";
                    } else {
                        echo "<form action='proses_update_status.php' method='POST' style='margin:0;'>";
                        echo "<input type='hidden' name='order_id' value='" . $row['id'] . "'>";
                        $statuses = [];
                        if ($row['status'] === 'pending') $statuses = ['pending', 'confirmed', 'canceled'];
                        elseif ($row['status'] === 'confirmed') $statuses = ['confirmed', 'completed', 'canceled'];
                        elseif ($row['status'] === 'completed') $statuses = ['completed', 'canceled'];
                        echo "<select name='status' onchange='showLoaderAndSubmit(this.form)' style='padding: 5px;'>";
                        foreach ($statuses as $status) {
                            $selected = ($row['status'] == $status) ? 'selected' : '';
                            echo "<option value='$status' $selected>" . ucfirst($status) . "</option>";
                        }
                        echo "</select>";
                        echo "</form>";
                    }
                    echo "</td>";
                    
                    // Kolom Aksi
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    echo "<a href='detail_po.php?id=" . $row['id'] . "'>Detail</a>";
                    if ($row['status'] == 'pending') {
                        echo " | <a href='edit_po.php?id=" . $row['id'] . "' style='font-weight:bold; color: #007bff;'>Edit</a>";
                        echo " | <a href='split_po.php?id=" . $row['id'] . "' style='font-weight:bold; color: #fd7e14;'>Ready Sebagian</a>";
                    }
                    if ($row['status'] == 'completed' && $row['point_sync_status'] !== 'sukses') {
                        echo " | <a href='sync_ulang_poin.php?id=" . $row['id'] . "' onclick='return confirm(\"Yakin ingin sinkronisasi ulang poin untuk pesanan ini?\")' style='font-weight:bold; color: #28a745;'>Sync Ulang Poin</a>";
                    }
                    echo " | <a href='proses_hapus_po.php?id=" . $row['id'] . "' onclick='return confirm(\"Yakin hapus permanen?\")' style='color:red;'>Hapus</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                // Perbaiki baris fallback: pakai '7' colspan
                echo "<tr><td colspan='7' style='border: 1px solid #ddd; padding: 8px; text-align:center;'>Tidak ada pesanan yang cocok.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
function showLoaderAndSubmit(formElement) {
    document.getElementById('loading-overlay').style.display = 'flex';
    formElement.submit();
}

$(document).ready(function() {
    
    // Inisialisasi DataTables
    var table = $('#po-masuk-table').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
        },
        "order": [[ 3, "desc" ]], // Urutkan berdasarkan tanggal pesan (Kolom ke-3)
        "columnDefs": [
            { "orderable": false, "targets": 0 } // Kolom pertama (trigger) tidak bisa diurutkan
        ]
    });

    // Fungsi untuk memuat dan menampilkan rincian item
    function format(order_id) {
        var content = '<div style="text-align:center; padding:10px;">Memuat rincian...</div>';
        
        // Menggunakan AJAX untuk memanggil file ini dengan action='get_order_items'
        $.ajax({
            url: 'admin_po_masuk.php', 
            type: 'GET',
            data: { 
                action: 'get_order_items', 
                order_id: order_id 
            },
            async: false, // Digunakan agar DataTables menunggu konten dimuat
            success: function(response) {
                // Response sekarang HANYA berisi HTML rincian item
                content = response;
            },
            error: function() {
                content = '<div style="color:red; padding:10px;">Gagal memuat rincian item.</div>';
            }
        });
        
        return '<div style="background-color: #f7f7f7; padding: 10px;">' + content + '</div>';
    }

    // Event listener pada klik kolom pertama
    $('#po-masuk-table tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var order_id = tr.data('order-id');

        if (!order_id) return; 

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('dt-show');
        } else {
            row.child(format(order_id)).show();
            tr.addClass('dt-show');
        }
    });
});
</script>

<?php 
require_once '../includes/footer.php';

// --- LOGIKA PHP UNTUK MEMENUHI PERMINTAAN AJAX (SUDAH DIPINDAHKAN KE ATAS) ---
// Bagian ini sekarang dikosongkan karena logika sudah dipindahkan ke baris paling atas (sebelum header.php)
?>