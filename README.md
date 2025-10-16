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
- [Instalasi](#-panduan-instalasi-&-konfigurasi)
- [Panduan Penggunaan](#-panduan-penggunaan)
  - [Admin](#admin-internal)
  - [Customer](#customer-eksternal)
- [Integrasi & Endpoints](#-integrasi-dan-endpoints)

---

## Tentang Sistem

Sistem Pre-Order (PO) Madani adalah aplikasi web untuk mengelola alur pre-order internal dengan fitur:

- ✅ Manajemen pesanan & split order
- ✅ Sinkronisasi produk dari Jubelio
- ✅ Integrasi loyalitas poin dengan WooCommerce
- ✅ Validasi data & integritas pesanan
- ✅ Pencarian pesanan yang canggih

---

## 🚀 Fitur dan Kapabilitas Utama

### Admin (Internal)

| Fitur | Deskripsi |
|-------|-----------|
| **Sinkronisasi Produk** | Import produk (SKU, harga, gambar, varian) dari Jubelio |
| **Buat PO Atas Nama Customer** | Admin dapat membuat pesanan untuk customer |
| **Split PO** | Pecah pesanan menjadi beberapa bagian berdasarkan ketersediaan |
| **Pencarian Pesanan** | Cari berdasarkan ID PO, nama customer, SKU, atau ID Jubelio |
| **Update Status & Poin Loyalitas** | Ubah status pesanan dan otomatis sync poin ke WooCommerce |
| **Sinkronisasi User** | Tarik data customer dari WordPress/WooCommerce |
| **Proteksi Data** | Cegah penghapusan produk yang sudah digunakan dalam pesanan |

### Customer (Eksternal)

| Fitur | Deskripsi |
|-------|-----------|
| **Buat Pre-Order** | Customer membuat pesanan dengan produk pilihan |
| **Lacak Status** | Pantau status pesanan real-time (Pending → Completed) |
| **Lihat Detail Pesanan** | Informasi produk, alamat, dan riwayat status |
| **Poin Loyalitas** | Terima poin reward untuk setiap pembelian selesai |
| **Notifikasi Real-time** | Update otomatis via email dan dashboard |

---

## Prasyarat

- **XAMPP** atau **MAMP**
- **PHP 7.4+** (dengan ekstensi `cURL`)
- **MySQL/MariaDB**

---

## 🛠️ Panduan Instalasi & Konfigurasi

### Langkah 1: Setup Database

1. Letakkan folder `preorder1` di direktori `htdocs` XAMPP
2. Buka **phpMyAdmin** dan buat database `db_preorder1`
3. Impor file `.sql` ke database yang baru dibuat

### Langkah 2: Konfigurasi Koneksi

Edit file `preorder1/includes/koneksi.php`:

```php
$host = "localhost";
$user = "root"; 
$pass = "";                    // Ganti jika ada password
$db_name = "db_preorder1";
```

### Langkah 3: Setup File Secrets (KRITIS ⚠️)

File `secrets.php` **HARUS dibuat manual** di `preorder1/includes/secrets.php`:

```php
<?php
// File: preorder1/includes/secrets.php (BUAT BARU SECARA MANUAL)

define('WC_POINT_SECRET_KEY', 'KunciIntegrasi_PO_@Madani2025!#'); 
define('WP_APP_PASSWORD_SYNC', 'PasswordAplikasiBaruWP');
define('WP_SYNC_USERNAME', 'jihadadmin'); 
define('WP_BASE_URL', 'https://order.rumahmadani.com');

?>
```

### Langkah 4: Jalankan Aplikasi

Akses melalui browser:
```
http://localhost/preorder1/index.php
```

---

## 📖 Panduan Penggunaan

### Admin (Internal)

#### 1️⃣ Login & Dashboard Admin

![Admin Login](./docs/screenshots/Login%20Admin.png)
![Admin Login](./docs/screenshots/Dashboard%20Admin.png)

- Masukkan kredensial admin
- Sistem akan connect ke Jubelio API
- Setelah login, Anda melihat dashboard dengan ringkasan pesanan

#### 2️⃣ Sinkronisasi Produk dari Jubelio

![Sinkronisasi Produk](./docs/screenshots/Impor%20Produk.png)

**Menu: Kelola Produk → Sinkronisasi dari Jubelio**

- Sistem menampilkan daftar produk dari Jubelio
- Pilih produk dengan checkbox, atau "Select All Per Page"
- Produk yang sudah tersinkronkan ditandai **"✓ Sudah Ada"**
- Klik **"Sinkronkan"** untuk simpan ke database lokal
- Data tersimpan: SKU, harga, gambar, varian

#### 3️⃣ Buat Pre-Order Baru

![Buat PO Baru](./docs/screenshots/Order%20PO%20Admin%20(1).png)
![Buat PO Baru](./docs/screenshots/Order%20PO%20Admin%20(2).png)
![Buat PO Baru](./docs/screenshots/Order%20PO%20Admin%20(3).png)

**Menu: Pre-Order → Buat PO Baru**

1. Pilih/cari customer
2. Pilih produk dari katalog
3. Isi kuantitas & varian
4. Verifikasi alamat pengiriman
5. Tambah catatan (opsional)
6. Klik **"Simpan PO"**
7. Sistem auto-tag: `[Dipesankan oleh: Nama Admin]`

#### 4️⃣ Pencarian & Daftar PO Masuk

![Daftar PO](./docs/screenshots/Daftar%20PO%20Masuk%20Admin.png)

**Menu: Pre-Order → Daftar PO Masuk**

Fitur pencarian:
- 🔍 **By ID PO**: Cari nomor pesanan
- 🔍 **By Customer**: Cari nama pembeli
- 🔍 **By SKU/ID Jubelio**: Cari produk
- Filter status (Pending, Processing, Ready, Completed, Canceled)

Tampilan tabel:
| ID PO | Customer | Produk | Qty | Status | Aksi |
|-------|----------|--------|-----|--------|------|
| #001 | Budi | T-Shirt | 5 | Pending | [View] [Edit] |

#### 5️⃣ Update Status & Poin Loyalitas

**Di halaman Detail PO:**

Ubah status pesanan:
- **Pending** → **Processing** → **Ready** → **Completed**
- Atau langsung ubah ke **Canceled**

**Sistem otomatis:**
- Status **Completed**: ➕ Tambah poin ke customer di WooCommerce
- Status **Canceled**: ➖ Kurangi poin dari customer di WooCommerce

#### 6️⃣ Split Pre-Order


**Di halaman Detail PO → Tombol "Pecah PO"**

Contoh: Pesanan 10 unit, stok hanya 5
- Bagian 1: 5 unit (siap proses)
- Bagian 2: 5 unit (sisa pesanan)

Sistem akan:
- Buat PO baru dengan qty 5
- Tag: `[Pecahan dibuat dari PO #001]`
- Kedua PO dapat diproses terpisah

#### 7️⃣ Sinkronisasi User dari WordPress


**Menu: Kelola → Sinkronisasi User dari WordPress**

- Klik **"Sync Sekarang"**
- Sistem tarik data customer dari WooCommerce
- Tambahkan/update customer di database lokal
- Tampilkan ringkasan: "5 customer baru, 3 updated"

#### 8️⃣ Sinkronisasi Ulang Poin Loyalitas


**Menu: Kelola → Sinkronisasi Poin Loyalitas**

Gunakan jika ada ketidakcocokan poin:
- Klik **"Rekalibrasi Semua Poin"**
- Sistem hitung ulang poin dari semua pesanan
- Update ke WooCommerce secara otomatis

---

### Customer (Eksternal)

#### 1️⃣ Login & Dashboard Customer

![Customer Dashboard](./docs/screenshots/Dashboard%20Customer.png)

- Login dengan akun WordPress/WooCommerce
- Dashboard menampilkan: ringkasan pesanan, poin loyalitas, notifikasi

#### 2️⃣ Buat Pre-Order Baru

![Customer Create PO](./docs/screenshots/Order%20PO%20Customer.png)   

**Menu: Pesanan → Buat Pesanan Baru**

1. Pilih produk dari katalog
2. Pilih varian (warna, ukuran, dll)
3. Isi kuantitas
4. Pilih alamat pengiriman (atau tambah baru)
5. Tulis catatan (opsional)
6. Klik **"Konfirmasi Pesanan"**
7. Dapatkan **Nomor PO Unik** untuk tracking

#### 3️⃣ Lacak Status Pesanan

![Customer Track Order](./docs/screenshots/Riwayat%20PO%20Customer.png)

**Menu: Pesanan Saya**

Tampilan status:
- 🟡 **Pending**: Menunggu konfirmasi admin
- 🔵 **Processing**: Sedang diproses
- 🟢 **Ready**: Siap dikirim + nomor resi
- ✅ **Completed**: Pesanan selesai + poin reward
- ❌ **Canceled**: Pesanan dibatalkan

Setiap status dilengkapi:
- Tanggal update
- Perkiraan waktu selesai
- Notifikasi real-time

#### 4️⃣ Lihat Detail Pesanan


**Klik Pesanan → Detail**

Informasi ditampilkan:
- 📦 **Produk**: Gambar, nama, harga per unit
- 📋 **Detail Pesanan**: Qty, total harga, tanggal pesan
- 📍 **Alamat Pengiriman**: Nama, alamat lengkap, kota, kode pos
- 💬 **Catatan**: Pesan khusus yang dikirim
- ⏱️ **Riwayat Status**: Timeline perubahan status

#### 5️⃣ Poin Loyalitas


**Menu: Profil → Poin Loyalitas**

- Lihat saldo poin saat ini
- Poin bertambah: Pesanan **Completed** ✅
- Poin berkurang: Pesanan **Canceled** ❌
- Riwayat perubahan poin dengan detail transaksi

---

## 🔗 Integrasi dan Endpoints

| Fungsi | API | File Handler | 
|--------|-----|--------------|
| Login Admin | Jubelio API | `proses_login.php` | 
| Sinkronisasi Produk | Jubelio API | `admin/fetch_jubelio_products.php` |
| Update Status & Poin | WooCommerce API | `admin/proses_update_status.php` |
| Sinkronisasi Poin | WooCommerce API | `admin/sync_ulang_poin.php` |
| Sinkronisasi User | WooCommerce API | `admin/check_wordpress_users.php` |
| Data Wilayah | Local | `get_wilayah.php` |

---

## 📁 Struktur Direktori

```
preorder1/
├── includes/
│   ├── koneksi.php
│   └── secrets.php (BUAT MANUAL)
├── admin/
│   ├── fetch_jubelio_products.php
│   ├── proses_update_status.php
│   ├── sync_ulang_poin.php
│   └── check_wordpress_users.php
├── index.php
├── proses_login.php
└── get_wilayah.php
```

---

## 🔐 Keamanan

- ⚠️ File `secrets.php` tidak boleh di-commit (sudah di `.gitignore`)
- 🔒 Gunakan HTTPS di production
- 🔄 Rotate API keys secara berkala
- ✅ Validasi semua input dari user

---

## 🐛 Troubleshooting

| Masalah | Solusi |
|--------|--------|
| Koneksi database gagal | Pastikan MySQL berjalan, cek kredensial di `koneksi.php` |
| File `secrets.php` tidak ditemukan | Buat file baru di `preorder1/includes/secrets.php` |
| Integrasi Jubelio gagal | Verifikasi kredensial & pastikan API Jubelio accessible |
| cURL extension tidak aktif | Uncomment `extension=curl` di `php.ini`, restart server |


---

<div align="center">

**Terakhir diupdate: October 16, 2025**

Dibuat dengan ❤️ oleh **RudinSS**

</div>
