# Panduan pasang Gho Studio di Hostinger

Untuk programmer. Aplikasi ini Laravel 13 (PHP 8.3) + MySQL, tanpa Node.js dan tanpa proses yang berjalan terus. Jalan di paket Hostinger mana pun yang punya PHP 8.3, MySQL, SSH, dan cron (Premium, Business, Cloud, atau VPS).

## 1. Siapkan di hPanel
1. **PHP**: Websites › Advanced › PHP Configuration → pilih **PHP 8.3**. Pastikan ekstensi aktif: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `xml`, `dom`, `curl`, `intl` (umumnya sudah aktif).
   Naikkan `upload_max_filesize` dan `post_max_size` ke **512M** bila CMS dipakai untuk unggah video, dan `max_execution_time` ke **120**.
2. **Database**: Databases › MySQL Databases → buat database, pengguna, dan kata sandi. Catat ketiganya.
3. **SSH**: Advanced › SSH Access → aktifkan, catat host, port, pengguna.

## 2. Unggah kode
Lewat SSH (disarankan):
```bash
cd ~/domains/DOMAIN-ANDA/
git clone https://github.com/disdiksumut123-lang/gho-studio.git
cd gho-studio/aplikasi
composer install --no-dev --optimize-autoloader
```
Bila `composer` tidak tersedia, jalankan `composer install --no-dev` di komputer sendiri lalu unggah folder `aplikasi` (termasuk `vendor`) lewat File Manager.

## 3. Arahkan domain ke folder `public`
Pilihan A (disarankan): ganti `public_html` menjadi tautan ke folder public:
```bash
cd ~/domains/DOMAIN-ANDA/
mv public_html public_html_lama
ln -s ~/domains/DOMAIN-ANDA/gho-studio/aplikasi/public public_html
```
Pilihan B (bila tautan tidak diizinkan): salin isi `aplikasi/public` ke `public_html`, lalu ubah dua baris di `public_html/index.php`:
```php
require __DIR__.'/../gho-studio/aplikasi/vendor/autoload.php';
$app = require_once __DIR__.'/../gho-studio/aplikasi/bootstrap/app.php';
```
(sesuaikan bagian `require __DIR__.'/../storage/framework/maintenance.php'` dengan jalur yang sama).

## 4. Atur `.env`
```bash
cp .env.example .env
php artisan key:generate
nano .env
```
Isi minimal:
```
APP_URL=https://domain-anda.id
DB_DATABASE=...    DB_USERNAME=...    DB_PASSWORD=...
ADMIN_NAME="Nama Pemilik"
ADMIN_EMAIL=email-pemilik@...
ADMIN_PASSWORD=sandi-kuat-minimal-12-karakter
SEED_SAMPLE=true        # false bila tidak mau contoh pelatihan Latsar
MAIL_...                # opsional, untuk email
```
Biarkan `APP_ENV=production` dan `APP_DEBUG=false`.

## 5. Buat tabel dan akun Super Admin
```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R 775 storage bootstrap/cache
```
Akun di `ADMIN_EMAIL` otomatis punya peran Super Admin, Coach, Fasilitator, dan CMS. Setelah masuk pertama kali, hapus `ADMIN_PASSWORD` dari `.env`.

## 6. Cron (untuk jadwal terbit CMS)
hPanel › Advanced › Cron Jobs › tambahkan, setiap menit:
```
/usr/bin/php /home/PENGGUNA/domains/DOMAIN-ANDA/gho-studio/aplikasi/artisan schedule:run >> /dev/null 2>&1
```
(cek jalur PHP dengan `which php` di SSH; di beberapa server `/opt/alt/php83/usr/bin/php`).

## 7. Setelah terpasang (oleh Super Admin di aplikasi)
1. **Pengaturan › Layanan cerdas**: tambah penyedia. Contoh DeepSeek: alamat `https://api.deepseek.com`, model `deepseek-chat`, isi kunci API, tekan **Uji**. Bisa menambah OpenAI, Gemini, Claude, OpenRouter, Groq; pilih satu sebagai **Utama**.
2. **Pengaturan › WhatsApp** (opsional): pilih Fonnte atau Wablas dan isi token untuk kirim undangan otomatis. Tanpa ini, tombol WA tetap bisa dipakai (membuka WhatsApp dengan pesan siap kirim).
3. **CMS › Akun terhubung**: lihat bagian 9.

## 8. Memperbarui aplikasi
```bash
cd ~/domains/DOMAIN-ANDA/gho-studio && git pull
cd aplikasi && composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 9. Catatan CMS (media sosial)
Konten, caption per platform, jadwal, dan status sudah berjalan. Pengiriman otomatis ke tiap platform butuh **aplikasi resmi** yang didaftarkan dan disetujui platform:
- YouTube: Google Cloud project + YouTube Data API v3 (OAuth).
- Facebook dan Instagram: Meta for Developers, izin `pages_manage_posts`, `instagram_content_publish` (perlu App Review).
- TikTok: TikTok for Developers, Content Posting API (perlu audit).
Setelah disetujui, token disimpan di **CMS › Akun terhubung**, lalu kode pengirim ditambahkan di `app/Services/Publisher.php` (fungsi `send`). Sampai saat itu, status tiap platform tampil "Menunggu integrasi".

## 10. Keamanan
- Kunci API, token WhatsApp, dan token media sosial disimpan terenkripsi dengan `APP_KEY`. **Jangan ganti `APP_KEY`** setelah data terisi; simpan cadangannya.
- Berkas jawaban peserta disimpan di `storage/app/private`, tidak bisa diakses langsung dari internet.
- Cadangan rutin: hPanel › Backups (database dan berkas).

## Uji cepat di komputer sendiri (tanpa MySQL)
```bash
cd aplikasi && composer install && cp .env.example .env
# ganti DB_CONNECTION=sqlite dan hapus baris DB_ lainnya
touch database/database.sqlite && php artisan key:generate && php artisan migrate --seed
php artisan serve   # buka http://127.0.0.1:8000, masuk dengan ADMIN_EMAIL/ADMIN_PASSWORD
```
