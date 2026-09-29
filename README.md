# Dashboard Data SISKAPERBAPO Kabupaten Mojokerto

Aplikasi Laravel untuk menampilkan, mengelola, membandingkan, dan memprediksi harga
komoditas pasar di Kabupaten Mojokerto. Repository ini sudah menyertakan snapshot
dataset sehingga aplikasi dapat langsung diisi setelah di-clone tanpa perlu menerima
file database secara terpisah.

## Fitur utama

- Dashboard harga komoditas dan grafik tren.
- Perbandingan harga antar pasar.
- Prediksi harga dan evaluasi model.
- Ekspor laporan CSV dan PDF.
- Impor serta pengelolaan data harga oleh administrator.
- Galeri infografis, pemeriksaan kualitas data, arsip, dan backup database.
- Pencatatan aktivitas administrator.

## Teknologi dan kebutuhan sistem

- PHP 8.2 atau lebih baru.
- Composer.
- MySQL/MariaDB untuk deployment, atau SQLite untuk pengembangan.
- Ekstensi PHP yang dibutuhkan Laravel, termasuk PDO, Mbstring, OpenSSL, dan Zlib.
- Node.js dan npm untuk membangun aset frontend.
- Python 3 beserta paket pada `requirements-prediction.txt` jika fitur pembuatan
  prediksi otomatis akan digunakan.

## Instalasi lokal

Clone repository, lalu masuk ke direktori proyek:

```bash
git clone <url-repository>
cd <nama-direktori>
```

Pasang dependensi dan buat konfigurasi lokal:

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Pada Windows PowerShell, pengganti perintah `cp` adalah:

```powershell
Copy-Item .env.example .env
```

### Konfigurasi database

Untuk MySQL/MariaDB, ubah bagian berikut di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=siskaperbapo
DB_USERNAME=root
DB_PASSWORD=
```

Buat database kosong sesuai nilai `DB_DATABASE`, lalu jalankan migration dan seeder:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

> **Peringatan:** `migrate:fresh` menghapus seluruh tabel. Gunakan perintah tersebut
> hanya untuk instalasi baru atau database pengembangan yang boleh dikosongkan.

Jalankan aplikasi lokal:

```bash
php artisan serve
```

Aplikasi dapat dibuka melalui `http://127.0.0.1:8000`.

## Akun administrator

Seeder membuat akun administrator dari konfigurasi berikut:

```env
ADMIN_NAME="Administrator Mojokerto"
ADMIN_EMAIL=admin@mojokerto.go.id
ADMIN_USERNAME=admin_mojokerto
ADMIN_PASSWORD=Admin12345!
```

Ganti seluruh nilai tersebut, terutama `ADMIN_PASSWORD`, sebelum menjalankan seeder
di server publik. Halaman login tersedia di `/login`.

## Dataset bawaan

`DatabaseSeeder` menjalankan `AdminUserSeeder` dan `DatasetSeeder`. Snapshot saat ini
berisi:

| Data | Jumlah |
| --- | ---: |
| Kategori | 18 |
| Pasar | 4 |
| Komoditas | 60 |
| Harga komoditas | 92.400 |
| Prediction run | 3 |
| Profil prediksi | 120 |
| Hasil prediksi | 450 |
| Infografis | 6 |

Dataset terkompresi berada di `database/seeders/data/*.jsonl.gz` dan wajib ikut
di-commit ke repository. Zlib harus aktif agar PHP dapat membacanya.

Untuk memperbarui snapshot berdasarkan isi database lokal:

```bash
php artisan dataset:export-seeder
```

Setelah itu, commit kembali berkas `.jsonl.gz` yang berubah. Perintah ekspor hanya
menyertakan data bisnis. Akun pengguna, session, cache, queue, log aktivitas, alamat
IP, dan riwayat perubahan harga sengaja tidak dipublikasikan.

`CommodityDemoSeeder` adalah seeder demo lama dan tidak dipanggil oleh
`DatabaseSeeder`.

## Prediksi otomatis

Pasang dependensi Python bila server akan menjalankan training prediksi:

```bash
python -m pip install -r requirements-prediction.txt
```

Sesuaikan `PYTHON_BINARY` atau `PREDICTION_PYTHON_BINARY` di `.env` jika executable
Python menggunakan nama atau lokasi lain. Prediksi dapat dijalankan langsung dengan:

```bash
php artisan predictions:auto --sync
```

Untuk pemrosesan melalui queue, jalankan worker:

```bash
php artisan queue:work --queue=predictions,default --tries=1 --timeout=7200
```

## Deployment ke hosting

Untuk instalasi baru pada server production:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate:fresh --seed --force
php artisan storage:link
php artisan optimize
```

Pastikan document root domain diarahkan ke direktori `public`, kemudian atur:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain.example
```

Folder `storage` dan `bootstrap/cache` harus dapat ditulis oleh web server. Pada
database yang sudah berisi data, jangan gunakan `migrate:fresh`; gunakan:

```bash
php artisan migrate --force
```

Jika queue dan prediksi otomatis digunakan, jalankan worker melalui Supervisor atau
fitur process manager dari hosting. Untuk scheduler Laravel, tambahkan cron berikut:

```cron
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

Scheduler menjalankan backup database harian pukul 01.30 dan menyimpan tujuh backup
terakhir.

## Pengembangan dan pengujian

Untuk menjalankan server Laravel, Vite, queue worker, dan log secara bersamaan:

```bash
composer run dev
```

Jalankan seluruh automated test dengan:

```bash
php artisan test
```

## Catatan infografis

Seeder menyimpan metadata dan path infografis. Pastikan file gambar yang dirujuk juga
tersedia pada storage/public server agar gambar dapat ditampilkan setelah deployment.

## Lisensi

Proyek ini menggunakan lisensi MIT sebagaimana tercantum pada `composer.json`.
