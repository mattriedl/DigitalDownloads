<?php

namespace App\Models\Concerns;

use App\Support\SiteData;
use Illuminate\Support\Facades\Cache;

/**
 * Clears the cached navigation and homepage lists when a model that feeds them changes,
 * so admin edits show up on the site straight away.
 *
 * (Registered from the model itself: hooking these events from a service provider
 * stops the model's own booted() hooks from being registered.)
 */
trait FlushesSiteCache
{
    public static function bootFlushesSiteCache(): void
    {
        $flush = function () {
            SiteData::flush();
            Cache::forget('home_page_v2');
            Cache::forget('sidebar_popular');
        };

        static::saved($flush);
        static::deleted($flush);
    }
}
