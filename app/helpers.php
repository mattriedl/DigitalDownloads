<?php

use App\Support\Settings;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    /**
     * Read a per-site setting: setting('site_name'), setting('ads_enabled', true).
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(Settings::class)->get($key, $default);
    }
}

if (! function_exists('safe_html')) {
    /**
     * Rich text written in the admin (item descriptions, posts, pages), cleaned before display:
     * formatting, links, images and tables stay; scripts, event handlers and javascript: links go.
     * Editors aren't admins, so their HTML must not be able to run in an admin's browser.
     */
    function safe_html(?string $html): string
    {
        return blank($html) ? '' : Str::sanitizeHtml($html);
    }
}
