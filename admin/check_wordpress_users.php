<?php
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/secrets.php'; 

if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$wp_admin_username = WP_SYNC_USERNAME;
$wp_app_password   = WP_APP_PASSWORD_SYNC; 
header('Content-Type: application/json');

try {
    // Ambil email dan nama dari WordPress
    $wp_users_map = []; // email => nama
    $page = 1;
    $total_pages = 1;

    do {
        $wp_users_url = 'https://order.rumahmadani.com/wp-json/wp/v2/users?context=edit&per_page=100&page=' . $page;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $wp_users_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_USERPWD => $wp_admin_username . ':' . $wp_app_password,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($http_code !== 200) {
            throw new Exception("Gagal mengambil data dari WordPress (Hal: $page). Kode: $http_code. Pastikan kredensial benar.");
        }

        $header_str = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        if ($page === 1) {
            preg_match('/X-WP-TotalPages: (\d+)/i', $header_str, $matches);
            if (isset($matches[1])) {
                $total_pages = (int)$matches[1];
            }
        }

        $wp_users_data = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Gagal parsing JSON dari WordPress (Hal: $page).");
        }
        
        foreach ($wp_users_data as $user) {
            if (isset($user['email']) && isset($user['name'])) {
                $wp_users_map[$user['email']] = $user['name'];
            }
        }
        $page++;
    } while ($page <= $total_pages);

    if (empty($wp_users_map)) {
         throw new Exception("Tidak ada pengguna yang ditemukan dari WordPress.");
    }

    $wp_emails = array_keys($wp_users_map);
    $updated_count = 0;
    
    // Update pengguna yang cocok berdasarkan EMAIL, HANYA username dan source
    foreach ($wp_users_map as $email => $name) {
        $sql_update = "UPDATE users SET username = ?, source = 'order.rumahmadani.com' WHERE email = ?";
        $stmt_update = mysqli_prepare($koneksi, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "ss", $name, $email);
        mysqli_stmt_execute($stmt_update);
        if(mysqli_stmt_affected_rows($stmt_update) > 0){
             $updated_count++;
        }
    }
    
    // Update sisanya menjadi 'Akun PO'
    $placeholders = implode(',', array_fill(0, count($wp_emails), '?'));
    $types = str_repeat('s', count($wp_emails));
    $sql_update_local = "UPDATE users SET source = 'Akun PO' WHERE email NOT IN ($placeholders)";
    $stmt_update_local = mysqli_prepare($koneksi, $sql_update_local);
    mysqli_stmt_bind_param($stmt_update_local, $types, ...$wp_emails);
    mysqli_stmt_execute($stmt_update_local);

    echo json_encode(['success' => true, 'message' => "Sinkronisasi selesai. " . $updated_count . " data pengguna telah disinkronkan dengan WordPress."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;
?>