<?php

return [

    /*
    | Verifikasi email untuk akun yang mendaftar sendiri. Akun lama otomatis dianggap terverifikasi
    | oleh migrasi. Fitur ini hanya aktif jika email benar-benar bisa terkirim (MAIL_MAILER bukan
    | "log" atau "array"), supaya pendaftar baru tidak terkunci saat SMTP belum disiapkan.
    */
    'email_verification' => (bool) env('EMAIL_VERIFICATION_REQUIRED', true),

    /*
    | Verifikasi dua langkah (TOTP) wajib untuk peran di bawah ini.
    */
    'two_factor_required' => (bool) env('TWO_FACTOR_REQUIRED', true),
    'two_factor_roles' => ['superadmin', 'school_admin'],

    /*
    | Penguncian per akun: setelah N kali salah kata sandi untuk satu email,
    | login untuk email itu dikunci selama M menit (dari semua IP).
    */
    'lockout' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'decay_minutes' => (int) env('LOGIN_LOCKOUT_MINUTES', 15),
    ],

    /*
    | Saat login, kata sandi yang terbukti lemah (mis. akun lama sebelum aturan diperketat)
    | memaksa pengguna menggantinya. Aktif otomatis di production.
    */
    'force_strong_password_on_login' => (bool) env('FORCE_STRONG_PASSWORD_ON_LOGIN', env('APP_ENV') === 'production'),

    'password' => [
        'min_length' => 8,
        'min_length_privileged' => 12, // superadmin dan admin sekolah
    ],

        // Pengganti cron untuk hosting tanpa cron job (InfinityFree). Isi LAZY_SCHEDULER=true di .env.
    'lazy_scheduler' => (bool) env('LAZY_SCHEDULER', false),
];
