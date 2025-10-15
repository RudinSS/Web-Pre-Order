<?php
session_start();
require_once '../includes/koneksi.php';

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_GET['term'])) {
    die(json_encode([]));
}

$searchTerm = '%' . $_GET['term'] . '%';

// Query untuk mencari produk berdasarkan SKU atau nama model
$sql = "SELECT id, sku, model_name, base_price 
        FROM products 
        WHERE sku LIKE ? OR model_name LIKE ? 
        LIMIT 10";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "ss", $searchTerm, $searchTerm);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Format data untuk jQuery UI Autocomplete
    $products[] = [
        'id'    => $row['id'],
        'label' => $row['sku'] . ' - ' . $row['model_name'],
        'value' => $row['sku'],
        'sku'   => $row['sku'],
        'name'  => $row['model_name'],
        'price' => $row['base_price']
    ];
}

echo json_encode($products);
exit;