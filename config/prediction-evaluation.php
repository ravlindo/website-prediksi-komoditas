<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dataset evaluasi model per pasar
    |--------------------------------------------------------------------------
    |
    | Ganti file CSV pada lokasi ini bila hasil analisis diperbarui. File JSON
    | asli disimpan berdampingan sebagai arsip, tetapi tampilan memakai CSV
    | karena setiap baris masih membawa identitas model kandidat/final.
    |
    */
    'market_comparison_file' => storage_path(
        'app/private/prediction-evaluations/pasar-mojosari-kedungmaling-2026/hasil_prediksi_harga.csv'
    ),
];
