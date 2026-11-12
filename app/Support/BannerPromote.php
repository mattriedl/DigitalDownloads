<?php

namespace App\Support;

/**
 * BannerPromote.com banner spots. An ad placed on the page is just
 * <div data-gb-spot="leaderboard"></div> plus the embed script, loaded once per page.
 */
class BannerPromote
{
    public const DEFAULT_SCRIPT = 'https://bannerpromote.com/embed.js.php';

    /** spot name => [label, width, height] */
    public const SPOTS = [
        'medium-rectangle' => ['Medium Rectangle', 300, 250],
        'leaderboard' => ['Leaderboard', 728, 90],
        'full-banner' => ['Full Banner', 468, 60],
        'mobile-banner' => ['Mobile Banner', 320, 50],
        'skyscraper' => ['Skyscraper', 160, 600],
        'half-page' => ['Half Page', 300, 600],
        'large-rectangle' => ['Large Rectangle', 336, 280],
        'square' => ['Square', 250, 250],
    ];

    /** The spot that fits each of the theme's ad zones, and what phones get instead (if different). */
    public const ZONE_SPOTS = [
        'header_leaderboard' => ['leaderboard', null],
        'header_mobile' => ['mobile-banner', null],
        'home_between_sections' => ['leaderboard', 'medium-rectangle'],
        'category_top' => ['leaderboard', 'mobile-banner'],
        'sidebar_top' => ['medium-rectangle', null],
        'sidebar_middle' => ['half-page', 'medium-rectangle'],
        'sidebar_bottom' => ['medium-rectangle', null],
        'item_above_details' => ['leaderboard', 'medium-rectangle'],
        'item_in_description' => ['medium-rectangle', null],
        'item_below_download' => ['large-rectangle', 'medium-rectangle'],
        'download_countdown_top' => ['leaderboard', 'mobile-banner'],
        'download_countdown_main' => ['large-rectangle', 'medium-rectangle'],
        'download_countdown_bottom' => ['leaderboard', 'medium-rectangle'],
        'blog_in_article' => ['large-rectangle', 'medium-rectangle'],
        'footer_leaderboard' => ['leaderboard', 'mobile-banner'],
        'sticky_mobile_footer' => ['mobile-banner', null],
    ];

    public static function options(): array
    {
        return collect(self::SPOTS)->map(fn ($s) => "{$s[0]} ({$s[1]}×{$s[2]})")->all();
    }

    public static function size(?string $spot): ?array
    {
        return isset(self::SPOTS[$spot]) ? [self::SPOTS[$spot][1], self::SPOTS[$spot][2]] : null;
    }

    public static function scriptUrl(): string
    {
        $url = (string) setting('bannerpromote_script_url', self::DEFAULT_SCRIPT);

        return preg_match('#^https://#i', $url) ? $url : self::DEFAULT_SCRIPT;
    }
}
