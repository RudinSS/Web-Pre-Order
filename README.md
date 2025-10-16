# 🛒 Sistem Pre-Order (PO) Madani

<div align="center">

![PHP Native](https://img.shields.io/badge/PHP-Native-8892BF?style=for-the-badge&logo=php)
![Database](https://img.shields.io/badge/Database-MySQL%2FMariaDB-4479A1?style=for-the-badge&logo=mysql)
![Integrasi Jubelio](https://img.shields.io/badge/Integrasi-Jubelio-F7931E?style=for-the-badge)
![Integrasi WooCommerce](https://img.shields.io/badge/Integrasi-WooCommerce-A46497?style=for-the-badge&logo=woocommerce)

**Aplikasi web in-house berbasis PHP Native dan MySQL/MariaDB untuk mengelola alur Pre-Order internal**

Dikembangkan oleh **RudinSS**

</div>

---

## 📋 Daftar Isi

- [Tentang Sistem](#tentang-sistem)
- [Fitur Utama](#-fitur-dan-kapabilitas-utama)
- [Prasyarat](#prasyarat)
- [Instalasi & Konfigurasi](#-panduan-instalasi--konfigurasi)
- [Menjalankan Aplikasi](#langkah-3-menjalankan-aplikasi)
- [Integrasi & Endpoints](#-integrasi-dan-endpoints)

---

## Tentang Sistem

Sistem Pre-Order (PO) Madani adalah aplikasi web yang dirancang untuk mengelola seluruh alur internal pre-order. Sistem ini memfasilitasi:

- ✅ Manajemen pesanan pre-order
- ✅ Sinkronisasi data produk dari platform e-commerce
- ✅ Integrasi dengan **Jubelio** & **WordPress/WooCommerce**
- ✅ Manajemen loyalitas pelanggan
- ✅ Pencatatan dan validasi data yang ketat

---

## 🚀 Fitur dan Kapabilitas Utama

Sistem ini membagi fungsionalitas berdasarkan peran pengguna: **Admin (Internal)** dan **Customer (Eksternal)**.

### 1. Fungsionalitas Admin (Internal)

#### Integrasi Jubelio

| Fitur | Detail |
|-------|--------|
| **Login & Token Session** | Admin login menggunakan kredensial Jubelio untuk mendapatkan token API sesi, yang digunakan untuk interaksi API selanjutnya. |
| **Sinkronisasi Produk** | Mengambil data produk (SKU, Harga, Gambar, Varian) dari Jubelio. Dilengkapi filter *select all per page* dan indikator visual **"Sudah Ada"** untuk produk yang telah disinkronkan. |

#### Manajemen PO

| Fitur | Detail |
|-------|--------|
| **PO Creation** | Admin dapat membuat pesanan baru atas nama customer. |
| **Split PO** | Memecah kuantitas pesanan menjadi pesanan baru untuk item yang sudah tersedia atau siap diproses. |
| **Daftar PO Masuk** | Pencarian canggih berdasarkan ID PO, Nama Customer, ID Jubelio, dan SKU Produk. |

#### Integritas Data

| Fitur | Detail |
|-------|--------|
| **Blokir Hapus Produk** | Mencegah penghapusan produk dari database lokal jika produk tersebut sudah ada dalam riwayat pesanan. |
| **Validasi Alamat** | Otomatis memperbarui atau menyimpan alamat di profil customer saat Admin memproses PO. |

### 2. Integrasi WP/WooCommerce

| Fitur | Deskripsi | Endpoints |
|-------|-----------|-----------|
| **Poin Loyalitas** | Otomatis menambah/mengurangi poin loyalitas customer saat status PO diubah menjadi **'Completed'** atau **'Canceled'**. | `proses_update_status.php`, `sync_ulang_poin.php` |
| **Sinkronisasi Pengguna** | Sinkronisasi data customer dari WordPress/WooCommerce ke database lokal. | `check_wordpress_users.php` |


---

## Prasyarat

Sebelum memulai, pastikan sistem Anda memiliki:

- **XAMPP** atau **MAMP** (atau server lokal alternatif)
- **PHP 7.4+** (dengan ekstensi `cURL` aktif)
- **MySQL/MariaDB**
- **Git** (opsional, untuk cloning repository)
- Browser modern (Chrome, Firefox, Safari, Edge)

---

## 🛠️ Panduan Instalasi & Konfigurasi

### Langkah 1: Setup Proyek dan Database

1. **Tempatkan folder proyek**
   - Letakkan folder `preorder1` di direktori `htdocs` XAMPP Anda (atau sesuaikan dengan struktur server Anda).

2. **Buat database baru**
   - Buka **phpMyAdmin** dan buat database baru (misalnya: `db_preorder1`).

3. **Impor skema database**
   - Impor file `.sql` yang disediakan ke dalam database yang baru dibuat.

4. **Konfigurasi koneksi database**
   - Edit file `preorder1/includes/koneksi.php`:

   ```php
   <?php
   // File: preorder1/includes/koneksi.php
   
   $host = "localhost";
   $user = "root"; 
   $pass = "";                    // Ganti jika ada password
   $db_name = "db_preorder1";
   
   // Sisanya mengikuti konfigurasi koneksi Anda
   ?>
   ```

### Langkah 2: Konfigurasi Secrets (KRITIS ⚠️)

File `preorder1/includes/secrets.php` **TIDAK di-commit** ke GitHub (terdaftar di `.gitignore`). Anda **HARUS** membuatnya secara manual di setiap lingkungan deployment.

1. **Buat file baru**
   - Buat file bernama `secrets.php` di direktori `preorder1/includes/`

2. **Isi dengan konfigurasi berikut:**

   ```php
   <?php
   // File: preorder1/includes/secrets.php (BUAT BARU SECARA MANUAL)
   
   // ⚠️ KUNCI INI HARUS DIGANTI DENGAN NILAI YANG BARU DAN AMAN
   // Kunci Rahasia untuk Validasi Komunikasi Integrasi
   define('WC_POINT_SECRET_KEY', 'KunciIntegrasi_PO_@Madani2025!#'); 
   
   // Kredensial Admin Aplikasi (untuk sinkronisasi)
   define('WP_APP_PASSWORD_SYNC', 'PasswordAplikasiBaruWP');
   define('WP_SYNC_USERNAME', 'jihadadmin'); 
   
   // URL Dasar WordPress/WooCommerce
   define('WP_BASE_URL', 'https://order.rumahmadani.com');
   
   // Tambahkan konfigurasi API Key Jubelio di sini jika diperlukan
   // define('JUBELIO_API_KEY', 'your_api_key_here');
   
   ?>
   ```

   > **Catatan Keamanan**: Selalu gunakan kredensial yang aman dan unik untuk setiap lingkungan deployment.

### Langkah 3: Menjalankan Aplikasi

1. **Pastikan layanan berjalan**
   - Nyalakan Apache dan MySQL (atau MariaDB) di XAMPP/MAMP Anda.

2. **Akses aplikasi**
   - Buka browser dan akses:
   
   ```
   http://localhost/preorder1/index.php
   ```

3. **Login**
   - Gunakan kredensial admin Anda untuk login ke sistem.

---

## 🔗 Integrasi dan Endpoints

Berikut adalah ringkasan file utama yang menangani komunikasi dengan API eksternal:

| Fungsi | API Eksternal | File Handler | Autentikasi |
|--------|---------------|--------------|-------------|
| Sesi Login | Jubelio API | `preorder1/proses_login.php` | Kredensial Jubelio |
| Sinkronisasi Produk | Jubelio API | `preorder1/admin/fetch_jubelio_products.php` | Token Sesi |
| Update Poin Loyalitas | WP/WooCommerce API | `preorder1/admin/proses_update_status.php` | Secret Key |
| Sinkronisasi Poin | WP/WooCommerce API | `preorder1/admin/sync_ulang_poin.php` | Secret Key |
| Sinkronisasi Customer | WP/WooCommerce API | `preorder1/admin/check_wordpress_users.php` | App Password |

---

## 💡 Panduan Penggunaan

### Untuk Admin (Internal)

#### 1. Login ke Sistem

1. Buka halaman login di `http://localhost/preorder1/index.php`
2. Masukkan kredensial admin Anda
3. Sistem akan memvalidasi dan membuat token sesi dengan Jubelio API
4. Setelah login berhasil, Anda akan diarahkan ke dashboard admin

#### 2. Sinkronisasi Produk dari Jubelio

1. Navigasi ke menu **"Sinkronisasi Produk"** atau **"Kelola Produk"**
2. Sistem akan mengambil daftar produk dari Jubelio API
3. Fitur yang tersedia:
   - **Select All Per Page**: Pilih semua produk di halaman saat ini dengan satu klik
   - **Indikator "Sudah Ada"**: Produk yang sudah tersinkronkan akan ditandai dengan label **"Sudah Ada"**
   - **Filter & Pencarian**: Cari produk berdasarkan SKU, nama, atau kategori
4. Klik tombol **"Sinkronkan"** untuk produk yang ingin ditambahkan ke database lokal
5. Data produk (SKU, Harga, Gambar, Varian) akan tersimpan di database lokal

#### 3. Membuat Pre-Order Baru (PO Creation)

1. Navigasi ke menu **"Buat PO Baru"** atau **"Tambah Pesanan"**
2. Isi form dengan informasi berikut:
   - **Nama Customer**: Pilih customer yang sudah terdaftar atau tambahkan baru
   - **Produk**: Pilih produk dari daftar yang sudah tersinkronkan
   - **Varian** (jika ada): Pilih varian produk yang diinginkan
   - **Kuantitas**: Masukkan jumlah pesanan
   - **Alamat Pengiriman**: Verifikasi atau update alamat customer
   - **Catatan Khusus** (opsional): Tambahkan catatan tentang pesanan
3. Klik **"Simpan"** untuk membuat PO
4. Sistem akan otomatis memberi marker **[Dipesankan oleh: Nama Admin]** di catatan internal
5. PO akan masuk ke daftar pesanan dengan status **"Pending"**

#### 4. Memecah PO (Split PO)

1. Buka menu **"Daftar PO Masuk"**
2. Cari PO yang ingin dipecah menggunakan pencarian berdasarkan:
   - ID PO
   - Nama Customer
   - ID Jubelio
   - SKU Produk
3. Klik PO untuk membuka detail
4. Di halaman detail, klik tombol **"Pecah PO"** atau **"Split Order"**
5. Atur kuantitas untuk setiap bagian PO:
   - Bagian 1: Produk yang sudah tersedia (qty: X)
   - Bagian 2: Sisa produk yang masih dipesan (qty: Y)
6. Sistem akan membuat PO baru dengan marker **[Pecahan dibuat dari PO #XXX]**
7. Kedua PO akan dapat diproses secara terpisah

#### 5. Mengelola Daftar PO Masuk

1. Akses menu **"Daftar PO Masuk"**
2. Gunakan pencarian canggih untuk menemukan pesanan:
   - **Filter by ID PO**: Cari pesanan berdasarkan nomor PO
   - **Filter by Customer**: Cari berdasarkan nama customer
   - **Filter by Jubelio ID**: Cari berdasarkan ID produk di Jubelio
   - **Filter by SKU**: Cari berdasarkan kode SKU produk
3. Tabel akan menampilkan:
   - ID PO
   - Nama Customer (dengan tag **"via Admin"** jika dibuat oleh admin)
   - Produk & SKU
   - Kuantitas
   - Status Pesanan
   - Tanggal Pesanan
4. Klik pada PO untuk melihat detail lengkap

#### 6. Update Status PO

1. Buka detail PO dari daftar pesanan
2. Ubah status sesuai alur pesanan:
   - **Pending**: Pesanan baru, menunggu konfirmasi
   - **Processing**: Pesanan sedang diproses
   - **Ready**: Produk siap dikirim
   - **Completed**: Pesanan selesai dikirim
   - **Canceled**: Pesanan dibatalkan
3. Jika mengubah status menjadi **"Completed"** atau **"Canceled"**, sistem akan otomatis:
   - Menambah/mengurangi poin loyalitas customer di WooCommerce
   - Mengirim notifikasi ke customer
4. Klik **"Simpan"** untuk mengupdate status

#### 7. Memvalidasi Alamat Customer

1. Saat membuat atau mengedit PO, halaman akan menampilkan alamat customer
2. Jika alamat belum lengkap atau perlu diupdate:
   - Edit informasi alamat (jalan, kota, provinsi, kode pos, dll)
   - Alamat akan otomatis disimpan ke profil customer
3. Sistem tidak akan memproses PO jika alamat tidak lengkap

#### 8. Blokir Penghapusan Produk

Sistem secara otomatis melindungi integritas data:
- Jika Anda mencoba menghapus produk yang sudah pernah digunakan dalam pesanan, sistem akan:
  - Menampilkan pesan error: **"Tidak dapat menghapus produk ini karena sudah digunakan dalam riwayat pesanan"**
  - Menampilkan jumlah pesanan yang terkait dengan produk tersebut
- Untuk menghapus produk, hubungi tim teknis untuk membersihkan history terlebih dahulu

#### 9. Sinkronisasi Pengguna dari WordPress

1. Navigasi ke menu **"Sinkronisasi User"** atau **"Kelola Customer"**
2. Klik tombol **"Sync dari WordPress"**
3. Sistem akan:
   - Mengambil data customer dari WordPress/WooCommerce
   - Memeriksa customer yang sudah ada di database lokal
   - Menambahkan customer baru
   - Mengupdate informasi customer yang berubah
4. Tampilan akan menunjukkan:
   - Total customer yang tersinkronkan
   - Customer baru yang ditambahkan
   - Status sinkronisasi lengkap

#### 10. Sinkronisasi Ulang Poin Loyalitas

1. Navigasi ke menu **"Sinkronisasi Poin"** atau **"Kelola Loyalitas"**
2. Fitur ini digunakan jika ada ketidakcocokan poin loyalitas antara sistem lokal dan WooCommerce
3. Klik tombol **"Sinkronisasi Ulang Semua Poin"**
4. Sistem akan:
   - Membaca status semua PO di database lokal
   - Menghitung total poin yang seharusnya dimiliki setiap customer
   - Mengirim update ke WooCommerce untuk merekalibrasi poin
5. Tunggu hingga proses selesai (tergantung jumlah data)

---

### Untuk Customer (Eksternal)

#### 1. Login/Registrasi

1. Jika sudah memiliki akun di WordPress/WooCommerce, login menggunakan kredensial Anda
2. Jika belum, buat akun baru di platform e-commerce utama
3. Akun Anda akan otomatis tersinkronkan ke sistem Pre-Order

#### 2. Membuat Pre-Order Baru

1. Login ke akun Anda
2. Navigasi ke menu **"Buat Pesanan Baru"** atau **"Pre-Order"**
3. Isi form berikut:
   - **Pilih Produk**: Cari dan pilih produk dari katalog
   - **Varian** (jika ada): Pilih warna, ukuran, atau varian lainnya
   - **Kuantitas**: Masukkan jumlah yang ingin dipesan
   - **Alamat Pengiriman**: Gunakan alamat yang sudah tersimpan atau tambahkan alamat baru
   - **Catatan/Pesan** (opsional): Tulis pesan khusus untuk pesanan Anda
4. Review pesanan Anda sebelum submit
5. Klik tombol **"Konfirmasi Pesanan"** atau **"Submit PO"**
6. Sistem akan memberikan **Nomor PO Unik** yang bisa Anda gunakan untuk melacak pesanan

#### 3. Melacak Status Pesanan

1. Login ke akun Anda
2. Navigasi ke menu **"Pesanan Saya"** atau **"Track Order"**
3. Tabel akan menampilkan semua pesanan Anda dengan informasi:
   - **ID PO**: Nomor referensi pesanan unik
   - **Produk**: Nama dan SKU produk yang dipesan
   - **Kuantitas**: Jumlah item yang dipesan
   - **Status**: Status terkini pesanan (Pending, Processing, Ready, Completed, Canceled)
   - **Tanggal Pesanan**: Kapan Anda membuat pesanan
   - **Tanggal Update Terakhir**: Kapan status terakhir diubah
4. Warna status akan berbeda untuk setiap kondisi:
   - **Kuning (Pending)**: Menunggu konfirmasi
   - **Biru (Processing)**: Sedang diproses
   - **Hijau (Ready)**: Siap dikirim
   - **Hijau Gelap (Completed)**: Pesanan selesai
   - **Merah (Canceled)**: Pesanan dibatalkan

#### 4. Melihat Detail Pesanan

1. Dari menu **"Pesanan Saya"**, klik pada salah satu pesanan
2. Halaman detail akan menampilkan:
   - **Informasi Produk**:
     - Gambar produk
     - Nama dan SKU
     - Harga per unit
     - Total harga
   - **Detail Pesanan**:
     - Kuantitas yang dipesan
     - Status pesanan
     - Tanggal pesanan dibuat
     - Estimasi tanggal selesai (jika tersedia)
   - **Alamat Pengiriman**:
     - Nama penerima
     - Alamat lengkap
     - Kota dan provinsi
     - Kode pos
   - **Catatan**: Pesan atau catatan khusus yang Anda tulis
   - **Riwayat Status**: Timeline perubahan status pesanan

#### 5. Update Alamat Pengiriman

1. Jika alamat pengiriman belum dikonfirmasi oleh admin:
   - Buka detail pesanan
   - Klik tombol **"Edit Alamat"**
   - Update informasi alamat
   - Klik **"Simpan Alamat"**
2. Jika alamat sudah dikonfirmasi admin, hubungi customer service untuk perubahan lebih lanjut


#### 6. Pembatalan Pesanan

1. Pesanan yang masih dalam status **"Pending"** atau **"Processing"** dapat dibatalkan
2. Buka detail pesanan
3. Klik tombol **"Batalkan Pesanan"**
4. Masukkan alasan pembatalan (opsional)
5. Klik **"Konfirmasi Pembatalan"**
6. Admin akan memproses pembatalan Anda
7. Poin loyalitas Anda akan dikembalikan jika relevan

#### 7. Poin Loyalitas

1. Lihat saldo poin loyalitas Anda di **"Profil Saya"** atau **"Dashboard"**
2. Poin akan bertambah ketika:
   - Pesanan Anda selesai (status: **Completed**)
   - Nilai poin tergantung pada total belanja
3. Poin akan berkurang ketika:
   - Pesanan Anda dibatalkan (status: **Canceled**)
4. Gunakan poin untuk diskon pada pembelian berikutnya (jika fitur tersedia)

---



```
preorder1/
├── includes/
│   ├── koneksi.php          # Konfigurasi database
│   ├── secrets.php          # Konfigurasi rahasia (buat manual)
│   └── ...
├── admin/
│   ├── fetch_jubelio_products.php
│   ├── proses_update_status.php
│   ├── sync_ulang_poin.php
│   ├── check_wordpress_users.php
│   └── ...
├── index.php                # Halaman utama
├── proses_login.php         # Proses login
├── get_wilayah.php          # Endpoint data wilayah
└── ...
```

---

## 🔐 Keamanan

- **Jangan commit `secrets.php`**: File ini sudah terdaftar di `.gitignore`.
- **Gunakan HTTPS di production**: Selalu gunakan koneksi HTTPS untuk melindungi data sensitif.
- **Rotate API Keys secara berkala**: Ganti kunci rahasia dan token secara rutin.
- **Validasi input**: Semua input dari user harus divalidasi dan disanitasi.
- **Batch Operations**: Batasi jumlah operasi batch untuk mencegah server overload.

---

## 🐛 Troubleshooting

### Koneksi Database Gagal

- Pastikan MySQL/MariaDB berjalan.
- Verifikasi kredensial di `koneksi.php`.
- Pastikan database `db_preorder1` sudah dibuat.

### File `secrets.php` Tidak Ditemukan

- Buat file `secrets.php` secara manual di direktori `preorder1/includes/`.
- Isi dengan konfigurasi yang sesuai (lihat Langkah 2).

### Integrasi Jubelio Gagal

- Verifikasi kredensial Jubelio Anda.
- Pastikan API Jubelio dapat diakses dari server Anda.
- Periksa log error di browser console dan server logs.

### cURL Extension Tidak Aktif

- Buka `php.ini` dan uncomment line `extension=curl`.
- Restart Apache/server Anda.

---

<div align="center">

**Terakhir diupdate: October 16, 2025**


</div>
