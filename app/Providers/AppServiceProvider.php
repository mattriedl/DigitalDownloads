<?php

namespace App\Providers;

use App\Support\Settings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Each install's admin-set name and URL override .env defaults once the site is set up.
        $settings = $this->app->make(Settings::class);
        if ($name = $settings->get('site_name')) {
            config(['app.name' => $name]);
        }
        if ($url = $settings->get('site_url')) {
            config(['app.url' => $url]);
            if (str_starts_with($url, 'https://') && $this->app->isProduction()) {
                URL::forceScheme('https');
            }
        }
    }
}
