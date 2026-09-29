# Prediksi Otomatis

Prediksi otomatis berjalan tanpa membuka notebook dan tanpa mengunggah CSV hasil model.

## Alur produksi

1. Admin mengimpor atau melengkapi harga harian.
2. Laravel menunggu hingga satu tanggal berisi seluruh kombinasi pasar aktif dan komoditas aktif.
3. Job `RunAutomaticPrediction` mengekspor snapshot MySQL tanpa kredensial database ke proses Python.
4. `scripts/prediction_pipeline.py` mengevaluasi sembilan kandidat model memakai validasi temporal dan final holdout.
5. Hasil hanya dianggap `LAYAK` jika MAPE tidak melebihi batas dan MAE tidak lebih buruk daripada Naive.
6. Laravel memeriksa tanggal target, horizon, nilai, interval, komoditas, dan duplikat sebelum menyimpan hasil.
7. Versi lama baru diarsipkan setelah versi baru berhasil diimpor secara transaksional.

Harga nol tetap berada di MySQL, tetapi diperlakukan sebagai data tidak tersedia ketika model dibentuk.

## Menjalankan worker saat pengembangan

Terminal utama yang sudah disiapkan project:

```powershell
composer run dev
```

Atau jalankan server dan worker pada terminal terpisah:

```powershell
php artisan serve
php artisan queue:work --queue=predictions,default --tries=1 --timeout=7200
```

Admin tidak perlu menjalankan perintah prediksi. Worker hanya merupakan proses server yang harus selalu aktif.

## Menjalankan pemeriksaan manual oleh administrator sistem

Perintah berikut tersedia untuk diagnosis atau menjalankan ulang tanggal aktif:

```powershell
php artisan predictions:auto --force
```

Tambahkan `--sync` hanya untuk diagnosis lokal karena terminal akan menunggu proses model selesai:

```powershell
php artisan predictions:auto --sync --force
```

## Dependensi Python

```powershell
python -m pip install -r requirements-prediction.txt
```

Path interpreter ditentukan oleh `PREDICTION_PYTHON_BINARY`. Pada server, gunakan virtual environment dan arahkan variabel tersebut ke executable Python milik virtual environment.

## Hosting

Gunakan VPS atau platform yang mendukung proses worker jangka panjang. Jalankan worker melalui Supervisor/systemd dengan queue `predictions,default`, timeout sedikit di atas `PREDICTION_TIMEOUT_SECONDS`, dan satu percobaan per job. Scheduler Laravel tetap dijalankan setiap menit untuk tugas terjadwal lain.

## Lokasi audit

Setiap run menyimpan snapshot input dan seluruh keluaran audit di:

```text
storage/app/private/prediction-runs/{run_id}/
```

Status, progres, error, batas data, jumlah prediksi, dan versi aktif dapat dilihat admin pada menu **Versi Model Prediksi**.
