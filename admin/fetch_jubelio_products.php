<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
set_time_limit(300);

session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['jubelio_token'])) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak.']);
    exit;
}

$jubelioApiToken = $_SESSION['jubelio_token'];

session_write_close();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = 100;

// Menambahkan parameter sortBy=last_modified & sortDirection=DESC
$endpoint = "https://api2.jubelio.com/inventory/items/?page={$page}&pageSize={$pageSize}&sortBy=last_modified&sortDirection=DESC";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $jubelioApiToken],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);

$api_result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

header('Content-Type: application/json');

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL Error: ' . $curl_error]);
    exit;
}

$data = json_decode($api_result, true);

if ($http_code === 200 && isset($data['data'])) {
    echo json_encode($data['data']);
} else {
    http_response_code($http_code > 0 ? $http_code : 500);
    echo json_encode(['error' => 'Gagal mengambil data dari Jubelio.', 'details' => $data]);
}
exit;
?>