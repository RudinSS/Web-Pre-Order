<?php
// Mengatur header agar outputnya berupa JSON
header('Content-Type: application/json');

// Base URL dari API wilayah
$baseUrl = 'https://dev.farizdotid.com/api/daerahindonesia/';

// Fungsi untuk mengambil data dari API menggunakan cURL
function fetchData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // Opsi tambahan untuk mengatasi masalah SSL di beberapa environment lokal
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

// Menentukan data apa yang akan diambil berdasarkan parameter GET
if (isset($_GET['provinsi_id'])) {
    $provinsiId = $_GET['provinsi_id'];
    echo fetchData($baseUrl . 'kota?id_provinsi=' . $provinsiId);

} elseif (isset($_GET['kota_id'])) {
    $kotaId = $_GET['kota_id'];
    echo fetchData($baseUrl . 'kecamatan?id_kota=' . $kotaId);

} elseif (isset($_GET['kecamatan_id'])) {
    $kecamatanId = $_GET['kecamatan_id'];
    echo fetchData($baseUrl . 'kelurahan?id_kecamatan=' . $kecamatanId);

} else {
    // Jika tidak ada parameter, ambil semua provinsi
    echo fetchData($baseUrl . 'provinsi');
}

exit;