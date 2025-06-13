<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'), // Default untuk sesi web (misalnya, admin panel)
        'passwords' => env('AUTH_PASSWORD_BROKER', 'admins'), // Default untuk password reset
    ],

    'guards' => [
        'web' => [ // Guard untuk autentikasi web (session-based)
            'driver' => 'session',
            'provider' => 'multi_role_users', // Jika admin Anda menggunakan model Admin dan provider 'admins'
        ],

        'admin' => [ // Jika Anda memiliki guard admin terpisah untuk web
            'driver' => 'session',
            'provider' => 'admins',
        ],

        // ---- TAMBAHKAN GUARD UNTUK API MENGGUNAKAN SANCTUM (INI YANG PALING PENTING UNTUK API ANDA) ----
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'multi_role_users',
        ],
        // --------------------------------------------------------------------
    ],

     'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        'multi_role_users' => [ // <-- TAMBAHKAN PROVIDER KUSTOM BARU INI
            'driver' => 'custom_user_provider_driver', // <-- Nama driver yang akan kita daftarkan
            // Model tidak spesifik di sini, karena provider kustom akan menangani Teacher dan Student
            // Anda bisa menempatkan model default jika diperlukan, misalnya App\Models\User::class,
            // tetapi provider kustom yang akan memutuskan model mana yang digunakan.
        ],

        // Provider 'teachers' dan 'students' yang asli tidak perlu dihapus,
        // tetapi tidak akan digunakan oleh guard 'web' atau 'sanctum' jika Anda mengarahkan ke 'multi_role_users'.
        // Anda bisa mengomentari atau menghapusnya jika yakin tidak ada guard lain yang memakainya.
        /*
        'teachers' => [
            'driver' => 'eloquent',
            'model' => App\Models\Teacher::class,
        ],
        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\Student::class,
        ],
        */
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'teachers' => [
            'provider' => 'multi_role_users', // Ubah ke provider kustom
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'students' => [
            'provider' => 'multi_role_users', // Ubah ke provider kustom
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];