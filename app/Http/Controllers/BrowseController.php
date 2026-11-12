<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The site-wide lists: most downloaded, newest, top rated, a tag, and search results. */
class BrowseController extends Controller
{
    public function popular(): View
    {
        return $this->list('Most downloaded', 'The downloads people grab most, across every category.', Item::published()->popular(), ranked: true);
    }

    public function latest(): View
    {
        return $this->list('New and updated', 'Recently added and updated downloads.', Item::published()->latest('published_at'));
    }

    public function topRated(): View
    {
        return $this->list('Top rated', 'Downloads with the best scores from people who use them.', Item::published()->topRated(), ranked: true);
    }

    public function tag(Tag $tag): View
    {
        $query = Item::published()->whereHas('tags', fn ($q) => $q->whereKey($tag->id))->popular();

        // Tag pages with only a couple of items are thin content: keep them out of search results.
        return $this->list(ucfirst($tag->name).' downloads', null, $query, noindex: (clone $query)->count() < 3);
    }

    public function search(Request $request): View
    {
        $q = trim(mb_substr((string) $request->query('q'), 0, 100));

        $items = $q === ''
            ? null
            : Item::published()->with('category')->search($q)->popular()
                ->paginate((int) setting('items_per_page', 24))->withQueryString();

        return view('browse.search', ['q' => $q, 'items' => $items]);
    }

    private function list(string $title, ?string $intro, $query, bool $ranked = false, bool $noindex = false): View
    {
        $items = $query->with('category')->paginate((int) setting('items_per_page', 24));
        // Page 2+ of a list stays crawlable but is not a search landing page.
        $noindex = $noindex || $items->currentPage() > 1;

        return view('browse.list', compact('title', 'intro', 'items', 'ranked', 'noindex'));
    }
}
