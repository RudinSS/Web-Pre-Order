<?php
/**
 * QUICK DIAGNOSTIC
 * Upload ke folder admin sistem PO
 * Akses: https://po.yourdomain.com/admin/check_data.php
 */

session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic - Order Data</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        .error { background: #ffebee; padding: 10px; border-left: 4px solid #f44336; }
        .success { background: #e8f5e9; padding: 10px; border-left: 4px solid #4caf50; }
        .warning { background: #fff3e0; padding: 10px; border-left: 4px solid #ff9800; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
    </style>
</head>
<body>

<h1>🔍 Diagnostic Tool - Order Data</h1>

<form method="get">
    Order ID: <input type="number" name="order_id" value="<?php echo $order_id; ?>" required>
    <button type="submit">Check</button>
</form>

<hr>

<?php
if ($order_id > 0) {
    echo "<h2>Order #$order_id</h2>";
    
    // Ambil data order
    $sql = "SELECT * FROM pre_orders WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$order) {
        echo "<div class='error'>❌ Order tidak ditemukan!</div>";
        exit;
    }
    
    echo "<div class='success'>✅ Order ditemukan</div>";
    
    // CHECK 1: Username
    echo "<h3>1️⃣ Check Username</h3>";
    if (empty($order['username'])) {
        echo "<div class='error'>❌ <b>MASALAH DITEMUKAN:</b> Kolom 'username' kosong!</div>";
        echo "<p><b>Solusi:</b></p>";
        echo "<ol>";
        echo "<li>Pastikan user melakukan login saat order</li>";
        echo "<li>Atau isi manual kolom username dengan username WooCommerce mereka</li>";
        echo "</ol>";
        
        echo "<b>Query untuk update manual:</b><br>";
        echo "<pre>UPDATE pre_orders SET username = 'username_woocommerce' WHERE id = $order_id;</pre>";
    } else {
        echo "<div class='success'>✅ Username terisi: <b>" . htmlspecialchars($order['username']) . "</b></div>";
    }
    
    // CHECK 2: Total Amount
    echo "<h3>2️⃣ Check Total Amount</h3>";
    $total = floatval($order['total_amount']);
    $expected_points = floor($total / 100000);
    
    echo "<table>";
    echo "<tr><th>Total Amount</th><td>Rp " . number_format($total, 0, ',', '.') . "</td></tr>";
    echo "<tr><th>Poin yang akan didapat</th><td><b>$expected_points poin</b></td></tr>";
    echo "</table>";
    
    if ($expected_points == 0) {
        echo "<div class='warning'>⚠️ Total belum mencapai Rp100.000 - tidak akan dapat poin</div>";
    } else {
        echo "<div class='success'>✅ Order eligible untuk $expected_points poin</div>";
    }
    
    // CHECK 3: Status
    echo "<h3>3️⃣ Check Status</h3>";
    echo "<table>";
    echo "<tr><th>Status Sekarang</th><td><b>" . htmlspecialchars($order['status']) . "</b></td></tr>";
    echo "<tr><th>Jubelio SO ID</th><td>" . htmlspecialchars($order['jubelio_so_id'] ?? '-') . "</td></tr>";
    echo "</table>";
    
    // CHECK 4: Data Lengkap
    echo "<h3>4️⃣ Data Order Lengkap</h3>";
    echo "<table>";
    foreach ($order as $key => $value) {
        if ($key === 'username') {
            $display = "<b style='color: blue'>" . htmlspecialchars($value) . "</b>";
        } else {
            $display = htmlspecialchars($value);
        }
        echo "<tr><th>$key</th><td>$display</td></tr>";
    }
    echo "</table>";
    
    // CHECK 5: Test Payload
    echo "<h3>5️⃣ Payload yang Akan Dikirim ke WooCommerce</h3>";
    $test_payload = [
        'email' => $order['username'],
        'total_amount' => $total,
        'order_id' => $order_id
    ];
    echo "<pre>" . json_encode($test_payload, JSON_PRETTY_PRINT) . "</pre>";
    
    if (empty($order['username'])) {
        echo "<div class='error'>❌ Payload TIDAK VALID - username kosong!</div>";
    } else {
        echo "<div class='success'>✅ Payload valid</div>";
    }
    
    // CHECK 6: Simulasi Sync
    echo "<h3>6️⃣ Test Koneksi ke WooCommerce</h3>";
    
    if (!empty($order['username']) && $expected_points > 0) {
        $woocommerce_url = 'https://order.rumahmadani.com';
        $endpoint = $woocommerce_url . '/wp-json/po-integration/v1/add-points';
        
        echo "<p>Mencoba koneksi ke: <code>$endpoint</code></p>";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            echo "<div class='error'>❌ Koneksi gagal: $curl_error</div>";
        } elseif ($http_code === 404) {
            echo "<div class='error'>❌ Endpoint tidak ditemukan (404)<br>";
            echo "<b>Solusi:</b> Pastikan code REST API sudah ditambahkan ke functions.php</div>";
        } else {
            echo "<div class='success'>✅ Endpoint dapat diakses (HTTP $http_code)</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ Tidak bisa test - data tidak lengkap</div>";
    }
    
    // SUMMARY
    echo "<hr><h2>📊 Ringkasan</h2>";
    
    $issues = [];
    if (empty($order['username'])) $issues[] = "Username kosong";
    if ($expected_points == 0) $issues[] = "Total amount terlalu kecil";
    
    if (empty($issues)) {
        echo "<div class='success'>";
        echo "<h3>✅ Semua OK!</h3>";
        echo "<p>Order ini siap untuk sinkronisasi poin.</p>";
        echo "<p><b>Langkah selanjutnya:</b> Ubah status ke 'confirmed' dan poin akan otomatis ditambahkan.</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Ditemukan Masalah:</h3>";
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li>$issue</li>";
        }
        echo "</ul>";
        echo "<p>Perbaiki masalah di atas sebelum mengubah status.</p>";
        echo "</div>";
    }
}
?>

</body>
</html>