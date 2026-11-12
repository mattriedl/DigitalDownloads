<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Read a per-site setting: setting('site_name'), setting('ads_enabled', true).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}
