<?php

namespace App\Support;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Navigation data every public page needs (category tree, menus, footer pages),
 * cached so a page view does not repeat these queries. Flushed when an admin edits them.
 */
class SiteData
{
    private const KEY = 'site_nav_v1';

    public function categories(): Collection
    {
        return $this->all()['categories'];
    }

    public function menu(string $name): Collection
    {
        return $this->all()['menus'][$name] ?? collect();
    }

    public function footerPages(): Collection
    {
        return $this->all()['footer_pages'];
    }

    public static function flush(): void
    {
        Cache::forget(self::KEY);
    }

    private function all(): array
    {
        return once(fn () => Cache::remember(self::KEY, now()->addHour(), fn () => [
            'categories' => Category::active()->topLevel()
                ->with(['children' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('sort_order')->orderBy('name')
                ->get(),
            'menus' => MenuItem::whereNull('parent_id')->with('children')->orderBy('sort_order')->get()->groupBy('menu')->all(),
            'footer_pages' => Page::active()->where('show_in_footer', true)->orderBy('sort_order')->get(['title', 'slug']),
        ]));
    }
}
