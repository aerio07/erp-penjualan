# TradePro ERP (ERP Express)

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="280" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Sistem Informasi Enterprise Resource Planning (ERP) Terpadu untuk Perusahaan Trading & Distribusi</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS">
  <img src="https://img.shields.io/badge/Tests-78%20Passed-16A34A?style=for-the-badge&logo=checkmarx&logoColor=white" alt="Tests Status">
</p>

---

## 📌 Tentang Aplikasi

**TradePro ERP (ERP Express)** adalah aplikasi manajemen bisnis berbasis web yang dirancang khusus untuk mengelola siklus operasional bisnis trading, distributor, dan retail modern secara terintegrasi—mulai dari **Pengadaan (Purchasing)**, **Manajemen Stok & Multi-Gudang (Inventory)**, **Penjualan & Pengiriman (Sales & Delivery)**, hingga **Akuntansi & Keuangan (Accounting & Tax Compliance)**.

---

## 🚀 Fitur Utama & Modul Sistem

### 1. 🏢 Master Data Manajemen
* **Produk & Kategori**: Manajemen SKU, satuan unit, foto produk, harga beli/jual, dan batas minimum stok.
* **Pelanggan (Customers)**:
  * Klasifikasi Pajak: **PKP (Wajib NPWP)** atau **Non-PKP (Wajib NIK/KTP)**.
  * Termin pembayaran (*Payment Terms*), plafon kredit (*Credit Limit*), dan penetapan Sales PIC (*Account Owner*).
* **Pemasok (Suppliers)**:
  * Nomor NPWP, Nomor KTP (perorangan), dan informasi rekening bank pemasok.
* **Gudang (Warehouses)**:
  * Multi-lokasi gudang dengan pelacakan stok real-time per lokasi.
* **Chart of Accounts (CoA)**:
  * Struktur bagan akun akuntansi bertingkat (*Parent & Child sub-accounts*) dengan kategori Aset, Kewajiban, Ekuitas, Pendapatan, dan Beban.

---

### 2. 🛒 Pembelian (Procurement / Purchasing)
* **Purchase Request (PR)**: Pengajuan kebutuhan barang dari divisi operasional.
* **Purchase Order (PO)**:
  * Pembuatan PO resmi dengan dukungan **Custom Ship-To** (alamat pengiriman fleksibel).
* **Penerimaan Barang (Goods Receipt / GRN)**:
  * Penerimaan bertahap (*Partial Receiving*), verifikasi kuantiti, alasan selisih/kekurangan (*shortage reasons*), dan alokasi ke gudang tujuan.
* **Faktur Pembelian (Purchase Invoices)**:
  * Pencocokan 3-arah (*3-Way Matching* PO vs GRN vs Invoice) dengan perhitungan PPN.
* **Retur Pembelian (Purchase Returns) & Pembayaran Hutang (Purchase Payments)**.

---

### 3. 📦 Penjualan & Distribusi (Sales & Delivery)
* **Sales Order (SO)**:
  * Pemesanan barang dengan pengecekan stok otomatis, sistem *Stock Reservation*, pelacakan *Backorder*, dan *Procurement Demand*.
* **Surat Jalan / Pengiriman (Delivery Orders)**:
  * Penerbitan Surat Jalan pengiriman bertahap (*Partial Delivery*) dan pengurangan stok otomatis.
* **Faktur Penjualan (Sales Invoices)**:
  * Penerbitan invoice resmi dengan dukungan **Nomor Faktur Pajak** khusus customer PKP.
* **Pelunasan Piutang (Sales Payments)** & **Retur Penjualan (Sales Returns)**.

---

### 4. 🏬 Manajemen Inventaris & Stok (Inventory)
* **Kartu Stok (Stock Card)**: Riwayat pergerakan masuk, keluar, dan sisa saldo stok per produk dan per gudang.
* **Transfer Antar Gudang (Warehouse Transfers)**: Mutasi barang antar gudang dengan alur *Draft &rarr; Shipped &rarr; Received*.
* **Stock Opname**: Penyesuaian fisik stok periodik dengan pencatatan selisih otomatis.
* **Disposisi Stok (Stock Dispositions)**: Penanganan barang rusak/kadaluarsa melalui opsi *Write-Off (Pemusnahan)* atau *Jual Reject (Scrap Sale)*.

---

### 5. 📊 Akuntansi & Laporan Finansial (Accounting & Finance)
* **Jurnal Otomatis (Auto-Journal Entry)**: Setiap transaksi penjualan, penerimaan kas, pembayaran hutang, pembelian, dan disposisi stok otomatis membentuk jurnal debet-kredit.
* **Buku Besar (General Ledger)** & **Neraca Saldo (Trial Balance)**.
* **Laporan Keuangan**: Laporan Laba Rugi (*Profit & Loss*), Neraca (*Balance Sheet*), dan Umur Piutang/Hutang (*Aging Reports*).

---

### 6. 📄 Ekspor Dokumen & Cetak PDF
* Cetak PDF berstandar profesional untuk **Purchase Order**, **Sales Order**, **Surat Jalan**, **Faktur Penjualan (Invoice & Faktur Pajak)**, dan **Kuitansi Pembayaran**.

---

## 🛠️ Tech Stack & Dependensi

* **Backend Framework**: [Laravel 11.x](https://laravel.com)
* **Bahasa Pemrograman**: [PHP 8.2+](https://php.net)
* **Database**: [MySQL 8.0+](https://mysql.com)
* **Frontend Engine**: Blade Templating, [Tailwind CSS](https://tailwindcss.com), [Alpine.js](https://alpinejs.dev)
* **Ikon**: [FontAwesome 6 Pro/Free](https://fontawesome.com)
* **PDF Generator**: [barryvdh/laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
* **Testing Suite**: PHPUnit / Laravel Test Suite (78 feature tests passing)

---

## 💻 Panduan Instalasi & Menjalankan Lokal

### 1. Prasyarat Sistem
* PHP >= 8.2 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `gd`, `zip`)
* Composer >= 2.x
* Node.js >= 18.x & NPM
* Database MySQL / MariaDB (XAMPP, Laragon, Docker, atau MySQL Server)

---

### 2. Langkah Instalasi

1. **Clone Repository & Masuk ke Direktori Project**:
   ```bash
   git clone https://github.com/qqltech/erp-express.git
   cd erp-express/laravel
   ```

2. **Install Dependensi Composer & Node.js**:
   ```bash
   composer install
   npm install
   ```

3. **Salin File Environment (`.env`)**:
   ```bash
   cp .env.example .env
   ```

4. **Generate Application Key**:
   ```bash
   php artisan key:generate
   ```

5. **Konfigurasi Database pada `.env`**:
   Buka file `.env` dan sesuaikan koneksi database Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=erpexpress
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Jalankan Migrasi & Database Seeder**:
   ```bash
   php artisan migrate --seed
   ```

7. **Compile Asset Frontend & Jalankan Web Server**:
   ```bash
   # Terminal 1 (Build Assets):
   npm run dev

   # Terminal 2 (Web Server):
   php artisan serve
   ```

Aplikasi siap diakses melalui browser di: **`http://127.0.0.1:8000`**

---

## 👥 Akun Login Demo (Default Seeder)

Setelah menjalankan `php artisan db:seed`, Anda dapat masuk menggunakan akun default berikut (password untuk semua akun adalah: `password`):

| Role | Email | Password | Akses & Wewenang |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@erpexpress.com` | `password` | Akses penuh ke seluruh modul sistem |
| **Sales** | `sales@erpexpress.com` | `password` | Master Customer, Sales Order, Surat Jalan |
| **Gudang** | `gudang@erpexpress.com` | `password` | Goods Receipt, Pengiriman, Mutasi Stok, Opname |
| **Finance** | `finance@erpexpress.com` | `password` | Faktur, Pembayaran, Pajak, Jurnal & Laporan |

---

## 🧪 Menjalankan Automated Test Suite

Untuk memastikan seluruh alur bisnis dan validasi berjalan normal tanpa regresi:

```bash
php artisan test
```

Hasil verifikasi:
```text
  Tests:    78 passed (380 assertions)
  Duration: 13.08s
```

---

## 📂 Struktur Direktori Penting

```text
├── app/
│   ├── Http/Controllers/
│   │   ├── Accounting/          # Controller Jurnal, Buku Besar, Neraca
│   │   ├── Inventory/           # Controller Transfer, Opname, Disposisi
│   │   ├── Master/              # Controller Customer, Supplier, Produk, CoA, Gudang
│   │   ├── Purchase/            # Controller PO, GRN, Purchase Invoice, Return
│   │   └── Sales/               # Controller SO, Delivery, Sales Invoice, Return
│   ├── Models/                  # Eloquent Models & Relasi Database
│   ├── Services/                # Service Layer (Stok, Jurnal Otomatis, Pajak)
│   └── Traits/                  # Trait Helper (HasListFilters, Sortable, dll.)
├── database/
│   ├── migrations/              # Skema tabel database
│   └── seeders/                 # Data inisialisasi master data & pengguna
├── resources/
│   ├── views/                   # Blade templates & komponen antarmuka
│   └── css/                     # Stylesheet & token desain
└── routes/
    └── web.php                  # Web routing & middleware otentikasi
```

---

## 📄 Lisensi

Proyek ini dikembangkan untuk kebutuhan operasional trading & distribusi. Hak cipta dilindungi undang-undang.
