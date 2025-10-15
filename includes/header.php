<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Path redirect sudah diperbarui
if (!isset($_SESSION['loggedin'])) {
    header("Location: /preorder1/index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sistem PO</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            display: flex;
        }
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            position: fixed;
            display: flex;
            flex-direction: column;
            overflow-y: auto; /* <-- TAMBAHKAN BARIS INI  */
        }
        .sidebar-top {
            flex-grow: 1;
        }
        .sidebar h2 {
            text-align: center;
            margin-top: 0;
            border-bottom: 1px solid #4a627a;
            padding-bottom: 20px;
        }
        .sidebar .user-info { text-align: center; margin-bottom: 20px; }
        .sidebar .user-info p { margin: 0; }
        .sidebar .user-info small { color: #bdc3c7; }
        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar nav a {
            display: block;
            padding: 12px 15px;
            color: #ecf0f1;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 5px;
            transition: background-color 0.3s;
        }
        .sidebar nav a:hover {
            background-color: #34495e;
        }
        .sidebar-footer a {
            display: block;
            padding: 12px 15px;
            text-align: center;
            color: #ecf0f1;
            text-decoration: none;
            border-radius: 5px;
            background-color: #c0392b;
            transition: background-color 0.3s;
        }
        .sidebar-footer a:hover {
            background-color: #e74c3c;
        }
        .main-content {
            margin-left: 250px; /* Lebar sidebar */
            flex-grow: 1;
            padding: 30px;
        }
        .content-box {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-top">
            <h2>Sistem PO</h2>
            <div class="user-info">
                <p><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                <small><?php echo htmlspecialchars($_SESSION['role']); ?></small>
            </div>
            <nav>
                <ul>
                    <li><a href="/preorder1/dashboard.php">Dashboard</a></li>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="/preorder1/admin/po_baru_admin.php">Buat PO (Admin)</a></li>
                        <li><a href="/preorder1/admin/admin_po_masuk.php">Daftar PO Masuk</a></li>
                        <li><a href="/preorder1/admin/admin_produk.php">Manajemen Produk</a></li>
                        <li><a href="/preorder1/admin/admin_brand.php">Manajemen Brand</a></li>
                        <li><a href="/preorder1/admin/sync_jubelio.php">Sinkronisasi Jubelio</a></li>
                        <li><a href="/preorder1/admin/admin_pengguna.php">Kelola Pengguna</a></li>
                        <li><a href="/preorder1/admin/admin_laporan.php">Laporan</a></li>
                    <?php elseif ($_SESSION['role'] == 'customer'): ?>
                        <li><a href="/preorder1/po_baru.php">Buat PO Baru</a></li>
                        <li><a href="/preorder1/riwayat_po.php">Riwayat PO Saya</a></li>
                        <li><a href="/preorder1/profil.php">Profil Saya</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
        <div class="sidebar-footer">
            <a href="/preorder1/logout.php">Logout</a>
        </div>
    </div>

    <div class="main-content">