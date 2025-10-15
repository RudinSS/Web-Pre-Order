<?php 
require_once '../includes/header.php'; 
require_once '../includes/koneksi.php';

if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}
?>

<style>
    #loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); z-index: 9999; display: none; justify-content: center; align-items: center; color: white; flex-direction: column; }
    .spinner { border: 8px solid #f3f3f3; border-top: 8px solid #3498db; border-radius: 50%; width: 60px; height: 60px; animation: spin 1.5s linear infinite; }
    #loading-overlay p { margin-top: 15px; font-size: 1.2em; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<div id="loading-overlay">
    <div class="spinner"></div>
    <p>Sinkronisasi sedang berjalan, mohon tunggu...</p>
</div>


<div class="content-box">
    <h1>Manajemen Pengguna (Customer)</h1>
    <p>Di bawah ini adalah daftar semua pengguna dengan peran customer yang terdaftar di sistem.</p>

    <div id="notification-area" style="margin-bottom: 20px;"></div>

    <button id="sync-wordpress-btn" style="background-color: #17a2b8; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-size: 14px; margin-bottom: 20px;">
        Sinkronkan dengan WordPress
    </button>
    
    <div style="margin-bottom: 20px;">
        <label for="userSearchInput"><strong>Cari Pengguna:</strong></label>
        <input type="text" id="userSearchInput" placeholder="Ketik untuk mencari..." style="padding: 8px; width: 350px; border: 1px solid #ccc; border-radius: 4px;">
    </div>


    <table id="pengguna-table" class="display" style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #ddd; padding: 8px;">ID</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Nama Lengkap</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Username</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Email</th>
                <th style="border: 1px solid #ddd; padding: 8px;">No. Telepon</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Sumber Akun</th>
                <th style="border: 1px solid #ddd; padding: 8px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query ini sudah mengurutkan dari yang terbaru (created_at DESC)
            $sql = "SELECT id, full_name, username, email, phone_number, created_at, source FROM users WHERE role = 'customer' ORDER BY created_at DESC";
            $result = mysqli_query($koneksi, $sql);

            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . $row['id'] . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['full_name']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['email']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>" . htmlspecialchars($row['phone_number']) . "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    if ($row['source'] == 'order.rumahmadani.com') {
                        echo "<span style='background-color: #007bff; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($row['source']) . "</span>";
                    } else {
                        echo htmlspecialchars($row['source']);
                    }
                    echo "</td>";
                    echo "<td style='border: 1px solid #ddd; padding: 8px;'>";
                    echo "<a href='form_edit_pengguna.php?id=" . $row['id'] . "'>Edit</a> | ";
                    echo "<a href='ganti_password_user.php?id=" . $row['id'] . "'>Ganti Pass</a> | ";
                    echo "<a href='proses_pengguna.php?aksi=hapus&id=" . $row['id'] . "' onclick='return confirm(\"Yakin hapus pengguna ini?\")' style='color:red;'>Hapus</a>";
                    echo "</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='8' style='border: 1px solid #ddd; padding: 8px; text-align:center;'>Belum ada customer yang terdaftar.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var usersTable = $('#pengguna-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json" },
        "dom": 'lrtip',
        "order": [[ 0, "desc" ]] // Menambahkan baris ini untuk mengurutkan berdasarkan kolom pertama (ID) secara menurun
    });

    $('#userSearchInput').on('keyup', function(){
        usersTable.search(this.value).draw();
    });

    const syncBtn = document.getElementById('sync-wordpress-btn');
    const loadingOverlay = document.getElementById('loading-overlay');
    const notificationArea = document.getElementById('notification-area');

    syncBtn.addEventListener('click', function() {
        loadingOverlay.style.display = 'flex';
        notificationArea.innerHTML = '';

        fetch('check_wordpress_users.php', {
            method: 'POST' 
        })
        .then(response => response.json())
        .then(data => {
            loadingOverlay.style.display = 'none';

            let alertStyle = data.success ? 
                'padding: 15px; border-radius: 5px; color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb;' : 
                'padding: 15px; border-radius: 5px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;';
            
            notificationArea.innerHTML = `<div style="${alertStyle}">${data.message}</div>`;

            if(data.success) {
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            loadingOverlay.style.display = 'none';
            let alertStyle = 'padding: 15px; border-radius: 5px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb;';
            notificationArea.innerHTML = `<div style="${alertStyle}">Terjadi error: ${error}</div>`;
        });
    });
});
</script>

<?php 
require_once '../includes/footer.php';
?>