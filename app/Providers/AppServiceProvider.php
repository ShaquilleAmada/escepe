<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Paksa HTTPS untuk seluruh asset, route, dan redirect jika di production atau APP_URL diawali https://
        if (str_starts_with((string) config('app.url'), 'https://') || app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
