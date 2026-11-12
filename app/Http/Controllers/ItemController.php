<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function show(Request $request, Item $item): View
    {
        abort_unless($item->isPublished() || $request->user()?->isStaff(), 404);

        $item->load([
            'category.parent',
            'activeLinks',
            'screenshots',
            'versions' => fn ($q) => $q->take(10),
            'tags',
            'reviews' => fn ($q) => $q->approved()->with('user:id,name')->latest()->take(10),
            'comments' => fn ($q) => $q->approved()->whereNull('parent_id')->with(['user:id,name', 'replies.user:id,name'])->latest()->take(20),
        ]);

        // Count one view per visitor session.
        $seen = $request->session()->get('viewed_items', []);
        if (! in_array($item->id, $seen, true)) {
            $item->increment('view_count');
            $request->session()->put('viewed_items', array_slice([...$seen, $item->id], -200));
        }

        $related = Item::published()
            ->where('category_id', $item->category_id)
            ->whereKeyNot($item->id)
            ->popular()->take(6)->get();

        $user = $request->user();

        return view('items.show', [
            'item' => $item,
            'isSaved' => $user ? $user->favorites()->whereKey($item->id)->exists() : false,
            'myReview' => $user ? $item->reviews()->where('user_id', $user->id)->first() : null,
            'related' => $related,
            'reportTypes' => collect(Report::TYPES)->mapWithKeys(fn ($t) => [$t => match ($t) {
                'broken_link' => 'Download link does not work',
                'dmca' => 'Copyright complaint (DMCA)',
                'wrong_info' => 'Wrong or outdated information',
                default => 'Something else',
            }]),
        ]);
    }
}
