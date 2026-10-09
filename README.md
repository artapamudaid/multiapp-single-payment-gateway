# Multi-App Single Payment Gateway

## 1. Tentang Aplikasi (About)
Sistem **Multi-App Single Payment Gateway** adalah sebuah solusi arsitektur di mana banyak aplikasi (seperti App Client 1, App Client 2) dapat berbagi satu gerbang pembayaran terpusat (Billing App) menggunakan platform Midtrans. 

Sistem ini didesain agar manajemen langganan lisensi aplikasi (SaaS) dapat dikontrol dan dipantau seluruhnya dari satu server Billing. Saat aplikasi klien membutuhkan pemrosesan pembayaran, klien akan meminta token Midtrans Snap ke server Billing. Ketika tagihan berhasil dibayar oleh user, Midtrans akan memanggil Webhook milik Billing. Server Billing kemudian akan memperbarui database master, serta secara otomatis mengirim notifikasi (*server-to-server webhook*) ke aplikasi klien yang bersangkutan untuk mengaktifkan fitur premium-nya.

## 2. Struktur Direktori
```text
multiapp-single-payment-gateway/
├── .env                # (Diabaikan git) File konfigurasi utama
├── .env.example        # Template file konfigurasi .env
├── .gitignore          # File untuk mengabaikan file tertentu oleh git
├── README.md           # Dokumentasi proyek
│
├── app_billing/        # [Aplikasi Pusat] Bertugas menangani Midtrans & Master Dashboard
│   ├── config.php      # Membaca konfigurasi dari .env
│   ├── index.php       # Dashboard UI untuk Admin memantau transaksi & status app
│   ├── api_plans.php   # Endpoint API penyedia daftar paket ke klien
│   ├── api_subscribe.php # Endpoint API untuk memproses request Snap dari klien
│   ├── webhook.php     # Menerima webhook langsung dari Midtrans & meneruskan ke klien
│   └── midtrans_helper.php # Helper koneksi CURL ke API Midtrans (Snap & Core)
│
├── app_client1/        # [Aplikasi Klien 1]
│   ├── config.php      # Konfigurasi database & memuat .env
│   ├── index.php       # Dashboard user untuk klien 1
│   ├── middleware.php  # Pengecekan akses fitur berbayar (Active/Inactive)
│   ├── subscription.php # Halaman form langganan (memuat popup Snap Midtrans)
│   ├── process_subscription.php # Pengirim request transaksi ke app_billing
│   └── webhook.php     # Menerima notifikasi status langganan dari app_billing
│
└── app_client2/        # [Aplikasi Klien 2]
    └── (Memiliki struktur yang persis serupa dengan app_client1)
```

## 3. Simulasi (Testing)
Untuk menjalankan simulasi pembayaran dari awal sampai status lisensi aplikasi klien menjadi aktif, ikuti langkah-langkah berikut:

### Langkah A: Persiapan Environment
1. Salin file `.env.example` menjadi `.env`.
2. Sesuaikan konfigurasi kredensial Database dan Midtrans Server/Client Key di dalam file `.env`.
3. Pastikan virtual host (seperti Laravel Valet) berjalan sehingga Anda bisa mengakses `http://app-client1.test` dan `http://app-billing.test`.

### Langkah B: Jalankan Ngrok (Penting untuk Webhook)
Server Midtrans membutuhkan URL publik agar bisa mengirimkan *callback* notifikasi keberhasilan pembayaran.
Jalankan ngrok dengan me-routing *host-header* ke aplikasi billing Anda (atau lewat php built-in server):
```bash
# Jika pakai valet (Port 80)
ngrok http --host-header=app-billing.test 80

# Jika pakai php built-in server (contoh server berjalan di port 9123)
ngrok http 9123
```
Salin URL ngrok HTTPS yang didapatkan (contoh: `https://abcd-12-34.ngrok-free.app`), masuk ke **Dashboard Sandbox Midtrans > Settings > Configuration**, lalu simpan URL tersebut ditambah dengan path `/webhook.php` di kolom **Payment Notification URL**.

### Langkah C: Simulasi Checkout & Pembayaran
1. Buka browser dan arahkan ke klien: `http://app-client1.test/subscription.php`.
2. Klik tombol **Langganan Sekarang** untuk memunculkan popup Midtrans Snap.
3. Pilih metode pembayaran simulasi (disarankan: **BCA Virtual Account**), lalu salin nomor VA yang muncul.
4. Buka tab baru, dan akses situs **[Simulator Midtrans](https://simulator.sandbox.midtrans.com)**.
5. Masukkan nomor VA tersebut di simulator, klik **Inquire**, lalu selesaikan dengan klik **Pay**.

### Langkah D: Verifikasi Hasil Simulasi
- **Terminal Ngrok:** Anda akan melihat ada 1 *POST Request* masuk berstatus 200 OK dari Midtrans ke `/webhook.php`.
- **Master Dashboard:** Buka dashboard sentral `http://app-billing.test/` — Anda akan melihat jumlah saldo Pendapatan bertambah, aplikasi tercatat ACTIVE, dan riwayat transaksi menjadi **SUCCESS**.
- **Aplikasi Klien:** Buka aplikasi klien `http://app-client1.test/` — Status langganan kini otomatis terbuka dan fitur premium bisa digunakan.
