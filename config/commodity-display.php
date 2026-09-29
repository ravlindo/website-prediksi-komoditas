<?php

/*
| Urutan mengikuti susunan SISKAPERBAPO, sedangkan nomor kategori dibuat
| berurutan 01-18 sesuai 18 kategori yang benar-benar ada di hasil Excel.
*/
return [
    'categories' => [
        'beras' => ['number' => 1, 'order' => 1],
        'gula' => ['number' => 2, 'order' => 2],
        'minyak goreng' => ['number' => 3, 'order' => 3],
        'daging' => ['number' => 4, 'order' => 4],
        'telur ayam' => ['number' => 5, 'order' => 5],
        'susu' => ['number' => 6, 'order' => 6],
        'garam beryodium' => ['number' => 7, 'order' => 7],
        'tepung terigu' => ['number' => 8, 'order' => 8],
        'kacang kedelai' => ['number' => 9, 'order' => 9],
        'mie instant' => ['number' => 10, 'order' => 10],
        'cabe' => ['number' => 11, 'order' => 11],
        'bawang' => ['number' => 12, 'order' => 12],
        'sayur mayur' => ['number' => 13, 'order' => 13],
        'semen' => ['number' => 14, 'order' => 14],
        'ikan segar' => ['number' => 15, 'order' => 15],
        'besi beton (sni murni)' => ['number' => 16, 'order' => 16],
        'paku' => ['number' => 17, 'order' => 17],
        'pupuk' => ['number' => 18, 'order' => 18],
    ],

    'commodities' => [
        'beras premium', 'beras medium',
        'gula kristal putih',
        'minyak goreng curah', 'minyak goreng kemasan premium', 'minyak goreng kemasan sederhana', 'minyak goreng minyakita',
        'daging sapi paha belakang', 'daging ayam ras', 'daging ayam kampung',
        'telur ayam ras', 'telur ayam kampung',
        'kental manis', 'susu kental manis merk bendera', 'susu kental manis merk indomilk',
        'susu bubuk', 'susu bubuk merk bendera (instant)', 'susu bubuk merk indomilk (instant)',
        'bata', 'halus',
        'terigu protein sedang (kemasan)',
        'kedelai impor', 'kedelai lokal',
        'indomie rasa kari ayam',
        'cabe merah keriting', 'cabe merah besar', 'cabe rawit merah',
        'bawang merah', 'bawang putih sinco/honan',
        'kol/kubis', 'kentang', 'tomat merah', 'wortel', 'buncis',
        'semen gresik', 'semen tiga roda', 'semen padang', 'semen tonasa', 'semen bosowa', 'semen dynamix',
        'ikan bandeng', 'ikan kembung', 'ikan tuna', 'ikan tongkol', 'ikan cakalang',
        'besi beton 6 mm (12/9m)', 'besi beton 8 mm (12/9m)', 'besi beton 10 mm (12/9m)', 'besi beton 12 mm (12/9m)',
        'paku ukuran 10cm', 'paku ukuran 2 cm', 'paku ukuran 3cm', 'paku ukuran 4cm', 'paku ukuran 5cm', 'paku ukuran 7cm',
        'pupuk kcl non subsidi', 'pupuk npk non subsidi', 'pupuk sp 35 non subsidi', 'pupuk urea non subsidi', 'pupuk za non subsidi',
    ],

    'section_rows' => ['kental manis', 'susu bubuk'],
];
