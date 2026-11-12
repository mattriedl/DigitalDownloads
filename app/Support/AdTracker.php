<?php

namespace App\Support;

use App\Models\Ad;
use Illuminate\Support\Facades\DB;

/**
 * Counts ad impressions for a page view in memory and writes them once,
 * after the response is sent, so ads never slow the page down.
 */
class AdTracker
{
    /** @var array<int,int> ad id => impressions this request */
    private array $impressions = [];

    private bool $registered = false;

    /** Set when a BannerPromote ad is on the page, so the layout loads its script once. */
    public bool $needsBannerPromote = false;

    public function impression(Ad $ad): void
    {
        $this->impressions[$ad->id] = ($this->impressions[$ad->id] ?? 0) + 1;

        if (! $this->registered) {
            $this->registered = true;
            app()->terminating(fn () => $this->flush());
        }
    }

    public function flush(): void
    {
        $counts = $this->impressions;
        $this->impressions = [];

        foreach ($counts as $adId => $n) {
            static::bump($adId, 'impressions', $n);
        }
    }

    public static function click(Ad $ad): void
    {
        static::bump($ad->id, 'clicks', 1);
    }

    private static function bump(int $adId, string $column, int $n): void
    {
        Ad::whereKey($adId)->increment($column, $n);

        // One row per ad per day; insert it if missing, then add to it.
        $date = today()->toDateString();
        DB::table('ad_stats')->insertOrIgnore(['ad_id' => $adId, 'date' => $date, 'impressions' => 0, 'clicks' => 0]);
        DB::table('ad_stats')->where('ad_id', $adId)->where('date', $date)->increment($column, $n);
    }
}
