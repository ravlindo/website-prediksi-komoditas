<?php

return [
    /*
    | Kalender resmi yang dipakai untuk penanda linimasa grafik.
    | Sumber 2025: SKB 3 Menteri No. 1017/2024, No. 2/2024, No. 2/2024
    | Sumber 2026: SKB 3 Menteri No. 1497/2025, No. 2/2025, No. 5/2025
    */
    'sources' => [
        2025 => 'https://www.kemenkopmk.go.id/sites/default/files/pengumuman/2024-10/SKB%203%20Menteri%20Libur%20Nasional%20dan%20Cuti%20Bersama%20Tahun%202025.pdf',
        2026 => 'https://jdih.menpan.go.id/dokumen-hukum/keputusan-bersama-menteri-agama-menteri-ketenagakerjaan-dan-menteri-pendayagunaan-aparatur-negara-2045',
    ],

    'dates' => [
        // Hari libur nasional dan cuti bersama 2025.
        '2025-01-01' => ['name' => 'Tahun Baru 2025 Masehi', 'type' => 'national'],
        '2025-01-27' => ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'type' => 'national'],
        '2025-01-28' => ['name' => 'Cuti Bersama Tahun Baru Imlek', 'type' => 'collective_leave'],
        '2025-01-29' => ['name' => 'Tahun Baru Imlek 2576 Kongzili', 'type' => 'national'],
        '2025-03-28' => ['name' => 'Cuti Bersama Hari Suci Nyepi', 'type' => 'collective_leave'],
        '2025-03-29' => ['name' => 'Hari Suci Nyepi Tahun Baru Saka 1947', 'type' => 'national'],
        '2025-03-31' => ['name' => 'Hari Raya Idul Fitri 1446 H', 'type' => 'national'],
        '2025-04-01' => ['name' => 'Hari Raya Idul Fitri 1446 H', 'type' => 'national'],
        '2025-04-02' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2025-04-03' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2025-04-04' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2025-04-07' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2025-04-18' => ['name' => 'Wafat Yesus Kristus', 'type' => 'national'],
        '2025-04-20' => ['name' => 'Hari Kebangkitan Yesus Kristus', 'type' => 'national'],
        '2025-05-01' => ['name' => 'Hari Buruh Internasional', 'type' => 'national'],
        '2025-05-12' => ['name' => 'Hari Raya Waisak 2569 BE', 'type' => 'national'],
        '2025-05-13' => ['name' => 'Cuti Bersama Hari Raya Waisak', 'type' => 'collective_leave'],
        '2025-05-29' => ['name' => 'Kenaikan Yesus Kristus', 'type' => 'national'],
        '2025-05-30' => ['name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'type' => 'collective_leave'],
        '2025-06-01' => ['name' => 'Hari Lahir Pancasila', 'type' => 'national'],
        '2025-06-06' => ['name' => 'Hari Raya Idul Adha 1446 H', 'type' => 'national'],
        '2025-06-27' => ['name' => '1 Muharam 1447 H', 'type' => 'national'],
        '2025-08-17' => ['name' => 'Hari Proklamasi Kemerdekaan', 'type' => 'national'],
        '2025-08-18' => ['name' => 'Cuti Bersama HUT ke-80 Republik Indonesia', 'type' => 'collective_leave'],
        '2025-09-05' => ['name' => 'Maulid Nabi Muhammad SAW', 'type' => 'national'],
        '2025-12-25' => ['name' => 'Kelahiran Yesus Kristus', 'type' => 'national'],
        '2025-12-26' => ['name' => 'Cuti Bersama Kelahiran Yesus Kristus', 'type' => 'collective_leave'],

        // Hari libur nasional dan cuti bersama 2026.
        '2026-01-01' => ['name' => 'Tahun Baru 2026 Masehi', 'type' => 'national'],
        '2026-01-16' => ['name' => 'Isra Mikraj Nabi Muhammad SAW', 'type' => 'national'],
        '2026-02-16' => ['name' => 'Cuti Bersama Tahun Baru Imlek', 'type' => 'collective_leave'],
        '2026-02-17' => ['name' => 'Tahun Baru Imlek 2577 Kongzili', 'type' => 'national'],
        '2026-03-18' => ['name' => 'Cuti Bersama Hari Suci Nyepi', 'type' => 'collective_leave'],
        '2026-03-19' => ['name' => 'Hari Suci Nyepi Tahun Baru Saka 1948', 'type' => 'national'],
        '2026-03-20' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2026-03-21' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
        '2026-03-22' => ['name' => 'Hari Raya Idul Fitri 1447 H', 'type' => 'national'],
        '2026-03-23' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2026-03-24' => ['name' => 'Cuti Bersama Hari Raya Idul Fitri', 'type' => 'collective_leave'],
        '2026-04-03' => ['name' => 'Wafat Yesus Kristus', 'type' => 'national'],
        '2026-04-05' => ['name' => 'Hari Kebangkitan Yesus Kristus', 'type' => 'national'],
        '2026-05-01' => ['name' => 'Hari Buruh Internasional', 'type' => 'national'],
        '2026-05-14' => ['name' => 'Kenaikan Yesus Kristus', 'type' => 'national'],
        '2026-05-15' => ['name' => 'Cuti Bersama Kenaikan Yesus Kristus', 'type' => 'collective_leave'],
        '2026-05-27' => ['name' => 'Hari Raya Idul Adha 1447 H', 'type' => 'national'],
        '2026-05-28' => ['name' => 'Cuti Bersama Hari Raya Idul Adha', 'type' => 'collective_leave'],
        '2026-05-31' => ['name' => 'Hari Raya Waisak 2570 BE', 'type' => 'national'],
        '2026-06-01' => ['name' => 'Hari Lahir Pancasila', 'type' => 'national'],
        '2026-06-16' => ['name' => '1 Muharam 1448 H', 'type' => 'national'],
        '2026-08-17' => ['name' => 'Hari Proklamasi Kemerdekaan', 'type' => 'national'],
        '2026-08-25' => ['name' => 'Maulid Nabi Muhammad SAW', 'type' => 'national'],
        '2026-12-24' => ['name' => 'Cuti Bersama Kelahiran Yesus Kristus', 'type' => 'collective_leave'],
        '2026-12-25' => ['name' => 'Kelahiran Yesus Kristus', 'type' => 'national'],
    ],
];
