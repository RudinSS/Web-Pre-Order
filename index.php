<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pre-Order</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f2f5;
            margin: 0;
        }
        .login-wrapper {
            width: 380px;
            padding: 40px;
            background-color: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        .tab-links {
            display: flex;
            margin-bottom: 25px;
            border-bottom: 1px solid #e0e0e0;
        }
        .tab-link {
            flex: 1;
            padding: 10px;
            text-align: center;
            cursor: pointer;
            font-weight: 500;
            color: #888;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }
        .tab-link.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        .btn-primary { background-color: #007bff; }
        .error {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .success {
            color: #155724;
            background-color: #d4edda;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
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
        <div class="tab-links">
            <div class="tab-link active" data-target="customerLogin">Customer</div>
            <div class="tab-link" data-target="adminLogin">Admin</div>
        </div>

        <div id="customerLogin" class="form-section active">
            <h2>Login Customer</h2>
            <?php 
            if (isset($_GET['pesan'])) {
                if ($_GET['pesan'] == 'gagal_customer') { 
                    echo "<p class='error'>Login gagal! Cek kembali email & password Anda.</p>"; 
                } elseif ($_GET['pesan'] == 'registrasi_sukses') { 
                    echo "<p class='success'>Registrasi berhasil! Silakan login.</p>"; 
                }
            } 
            ?>
            <form action="proses_login.php" method="POST">
                <input type="hidden" name="tipe_login" value="customer">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required class="password-field">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
        </div>

        <div id="adminLogin" class="form-section">
            <h2>Login Admin</h2>
            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal_admin') { echo "<p class='error'>Login Jubelio gagal! Cek kredensial.</p>"; } ?>
            <form action="proses_login.php" method="POST">
                <input type="hidden" name="tipe_login" value="admin_jubelio">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email Jubelio" required>
                </div>
                 <div class="form-group">
                    <input type="password" name="password" placeholder="Password Jubelio" required class="password-field">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <button type="submit" class="btn btn-primary">Login via Jubelio</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab-link');
            const forms = document.querySelectorAll('.form-section');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(item => item.classList.remove('active'));
                    forms.forEach(form => form.classList.remove('active'));
                    tab.classList.add('active');
                    document.getElementById(tab.dataset.target).classList.add('active');
                });
            });

            <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'gagal_admin') {
                echo "document.querySelector('.tab-link[data-target=\"adminLogin\"]').click();";
            } ?>

            // Logika untuk semua ikon mata di halaman ini
            const toggleIcons = document.querySelectorAll('.toggle-password');
            toggleIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const passwordField = this.previousElementSibling;
                    
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>