<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        // Short cache: the homepage is the busiest page and changes a few times a day.
        $data = Cache::remember('home_page_v2', now()->addMinutes(5), function () {
            $base = fn () => Item::published()->with('category');

            $featured = $base()->featured()->latest('published_at')->take(8)->get();

            $sections = Category::active()->topLevel()->where('show_on_home', true)
                ->with('children:id,parent_id')
                ->orderBy('sort_order')->take(6)->get()
                ->map(fn (Category $cat) => [
                    'category' => $cat,
                    'items' => $base()->whereIn('category_id', $cat->descendantIds())
                        ->popular()->take(6)->get(),
                ])
                ->filter(fn ($s) => $s['items']->isNotEmpty())
                ->values();

            return [
                'featured' => $featured,
                'latest' => $base()->latest('published_at')->take(10)->get(),
                'trending' => $base()
                    ->withCount(['downloads as week_downloads' => fn ($q) => $q->where('created_at', '>=', now()->subDays(7))])
                    ->orderByDesc('week_downloads')->orderByDesc('download_count')
                    ->take(10)->get(),
                'sections' => $sections,
                'posts' => Post::published()->latest('published_at')->take(3)->get(['title', 'slug', 'excerpt', 'image', 'published_at']),
            ];
        });

        return view('home', $data);
    }
}
