<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Contracts\PushSender::class, \App\Services\WebPushSender::class);
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Üretimde APP_URL https ise bağlantılar her zaman https üretsin (ters vekil arkasında karışık içerik olmasın).
        if ($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
