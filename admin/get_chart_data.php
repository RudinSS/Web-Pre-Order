<?php
session_start();
require_once '../includes/koneksi.php';

// Keamanan: Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

header('Content-Type: application/json');

// Menentukan rentang tanggal (default 7 hari terakhir)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-$days days"));

// Menyiapkan array untuk menampung data harian
$dates = [];
$sales_data = [];
$revenue_data = [];
$current_date = new DateTime($start_date);
$end_date_obj = new DateTime($end_date);

while ($current_date <= $end_date_obj) {
    $date_key = $current_date->format('Y-m-d');
    $dates[] = $current_date->format('d M'); // Format untuk label di grafik
    $sales_data[$date_key] = 0;
    $revenue_data[$date_key] = 0;
    $current_date->modify('+1 day');
}

// Mengambil data dari database
// Hanya menghitung pesanan yang statusnya 'confirmed' atau 'completed'
$sql = "SELECT 
            DATE(order_date) as order_day, 
            COUNT(id) as total_orders, 
            SUM(total_amount) as total_revenue
        FROM pre_orders 
        WHERE 
            order_date BETWEEN ? AND ? 
            AND status IN ('confirmed', 'completed')
        GROUP BY DATE(order_date)";
        
$stmt = mysqli_prepare($koneksi, $sql);
$end_date_query = $end_date . ' 23:59:59';
mysqli_stmt_bind_param($stmt, "ss", $start_date, $end_date_query);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $sales_data[$row['order_day']] = (int)$row['total_orders'];
    $revenue_data[$row['order_day']] = (float)$row['total_revenue'];
}

// Mengembalikan data dalam format JSON
echo json_encode([
    'labels' => $dates,
    'sales' => array_values($sales_data),
    'revenue' => array_values($revenue_data)
]);

exit;