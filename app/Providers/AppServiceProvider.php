<?php

namespace App\Providers;

use App\Support\AdTracker;
use App\Support\Notifier;
use App\Support\Settings;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
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
        $this->app->scoped(AdTracker::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());

        // Remember when and from where people last signed in (shown in Admin → Users).
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now(), 'last_login_ip' => request()->ip()])->saveQuietly();
        });

        Event::listen(Registered::class, function (Registered $event) {
            Notifier::send('welcome', $event->user->email, ['name' => $event->user->name]);
        });
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
