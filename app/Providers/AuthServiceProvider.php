<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use App\Providers\CustomUserProvider; // Pastikan namespace ini sesuai dengan lokasi CustomUserProvider Anda

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Ini adalah cara yang benar untuk mendaftarkan Custom User Provider sebagai DRIVER autentikasi.
        // Nama 'custom_user_provider_driver' adalah nama driver yang akan kita gunakan di config/auth.php
        Auth::extend('custom_user_provider_driver', function ($app, $config) {
            return $app->make(CustomUserProvider::class);
        });
    }
}