<?php

/*
 | Pengaturan privasi dan perlindungan data pribadi (UU No. 27 Tahun 2022 tentang PDP).
 | Ubah nilai lewat file .env, atau langsung di sini.
 */
return [

    // Jika false, gerbang persetujuan dimatikan (berguna saat masa transisi). Sebaiknya true.
    'enforce' => (bool) env('PRIVACY_ENFORCE', true),

    // Naikkan versi ini setiap kali kebijakan berubah bermakna.
    // Semua pengguna (dan orang tua siswa) diminta menyetujui ulang.
    'policy_version' => env('PRIVACY_POLICY_VERSION', '2026-11-05'),
    'effective_date' => env('PRIVACY_EFFECTIVE_DATE', '5 November 2026'),

    // Identitas pengendali data yang tampil di kebijakan privasi. WAJIB diisi dengan data sebenarnya.
    'controller_name' => env('PRIVACY_CONTROLLER_NAME', 'Pengelola Guardian Digital'),
    'contact_email' => env('PRIVACY_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'hello@example.com')),
    'contact_address' => env('PRIVACY_CONTACT_ADDRESS', ''),

    // Di bawah usia ini = anak, wajib persetujuan orang tua / wali.
    'adult_age' => 18,

    // Masa berlaku tautan persetujuan yang dikirim ke email orang tua.
    'consent_link_days' => 14,
];
