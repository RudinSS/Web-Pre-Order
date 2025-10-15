<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Sistem Pre-Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        body { font-family: 'Poppins', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; margin: 0; }
        .login-wrapper { width: 380px; padding: 40px; background-color: white; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); border-radius: 12px; }
        .login-wrapper h2 { text-align: center; margin-top: 0; margin-bottom: 25px; color: #333; }
        .form-group { margin-bottom: 20px; position: relative; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; border: none; color: white; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; background-color: #28a745; }
        .error { color: #dc3545; background-color: #f8d7da; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 15px; font-size: 14px; }
        .login-link { text-align: center; margin-top: 20px; font-size: 14px; }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <h2>Buat Akun Baru</h2>

        <?php
        if (isset($_GET['pesan'])) {
            $pesan = $_GET['pesan'];
            if ($pesan == 'gagal') { echo "<p class='error'>Registrasi gagal! Silakan coba lagi.</p>"; } 
            elseif ($pesan == 'email_sudah_ada') { echo "<p class='error'>Email sudah terdaftar, silakan gunakan email lain.</p>"; } 
            elseif ($pesan == 'password_pendek') { echo "<p class='error'>Password minimal 6 karakter.</p>"; }
        }
        ?>

        <form action="proses_register.php" method="POST">
            <div class="form-group">
                <input type="text" name="full_name" placeholder="Nama Lengkap" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Alamat Email" required>
            </div>
            <div class="form-group">
                <input type="tel" name="phone_number" placeholder="Nomor Telepon" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Password (minimal 6 karakter)" required class="password-field">
                <i class="fas fa-eye toggle-password"></i>
            </div>
            <button type="submit" class="btn">Daftar</button>
        </form>
        <p class="login-link">Sudah punya akun? <a href="index.php">Login di sini</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleIcons = document.querySelectorAll('.toggle-password');
            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const passwordField = this.previousElementSibling;
                    
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        // Ganti kelas ikon menjadi mata-tercoret (tutup)
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        // Ganti kelas ikon menjadi mata (buka)
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>