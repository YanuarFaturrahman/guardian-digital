<?php

return [

    // Naikkan versi ini setiap kebijakan privasi berubah. Semua pengguna diminta setuju ulang.
    'policy_version' => env('PRIVACY_POLICY_VERSION', '2026-11-05'),

    // Pengendali data (isi dengan nama lembaga/penyelenggara sebenarnya).
    'controller_name' => env('PRIVACY_CONTROLLER_NAME', 'Pengelola Guardian Digital'),
    'contact_email' => env('PRIVACY_CONTACT_EMAIL', ''),

    // Pengguna di bawah usia ini (siswa) wajib mendapat persetujuan orang tua/wali.
    'consent_age' => (int) env('PRIVACY_CONSENT_AGE', 18),

    // Batas wajar usia siswa saat mendaftar.
    'min_student_age' => 6,
];
