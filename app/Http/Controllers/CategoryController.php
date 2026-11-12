<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Item;
use App\Support\SiteData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public const SORTS = [
        'popular' => 'Most downloaded',
        'latest' => 'Newest',
        'rating' => 'Top rated',
        'name' => 'A–Z',
    ];

    public function index(SiteData $site): View
    {
        return view('categories.index', ['categories' => $site->categories()]);
    }

    public function show(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        $category->load(['parent', 'children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')]);
        $sort = array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'popular';

        $items = Item::published()
            ->with('category')
            ->whereIn('category_id', $category->descendantIds())
            ->tap(fn ($q) => self::applySort($q, $sort))
            ->paginate((int) setting('items_per_page', 24))
            ->withQueryString();

        return view('categories.show', compact('category', 'items', 'sort'));
    }

    public static function applySort($query, string $sort): void
    {
        match ($sort) {
            'latest' => $query->latest('published_at'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'name' => $query->orderBy('title'),
            default => $query->popular(),
        };
    }
}
