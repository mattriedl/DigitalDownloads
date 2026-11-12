<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells Bing, Yandex and other IndexNow search engines that a page was added or changed,
 * so it is crawled within minutes instead of days. (Google does not use IndexNow; it reads the sitemap.)
 */
class PingIndexNow implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /** @param  array<int, string>  $urls */
    public function __construct(public array $urls) {}

    public function handle(): void
    {
        $key = (string) setting('indexnow_key');
        $siteUrl = (string) setting('site_url', config('app.url'));
        $host = parse_url($siteUrl, PHP_URL_HOST);

        // Only live sites on a real domain; never ping from local or staging copies.
        if ($key === '' || ! $host || ! app()->isProduction() || str_contains($host, 'localhost')) {
            return;
        }

        $response = Http::timeout(15)->post('https://api.indexnow.org/indexnow', [
            'host' => $host,
            'key' => $key,
            'keyLocation' => rtrim($siteUrl, '/').'/'.$key.'.txt',
            'urlList' => array_values(array_unique($this->urls)),
        ]);

        if (! in_array($response->status(), [200, 202], true)) {
            Log::warning('IndexNow ping failed: HTTP '.$response->status());
        }
    }
}
