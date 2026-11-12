<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Item;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Reviews and comments from signed-in, verified members. */
class FeedbackController extends Controller
{
    public function review(Request $request, Item $item): RedirectResponse
    {
        abort_unless($item->isPublished() && setting('reviews_enabled', true), 404);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:3000'],
        ], ['rating.required' => 'Choose a star rating.']);

        $held = (bool) setting('reviews_require_approval', true);

        // One review per person per item; writing again updates it.
        Review::updateOrCreate(
            ['item_id' => $item->id, 'user_id' => $request->user()->id],
            $data + ['status' => $held ? Review::STATUS_PENDING : Review::STATUS_APPROVED],
        );

        return redirect()->to(route('items.show', $item).'#reviews')
            ->with('status', $held ? 'Thanks! Your review will appear once it has been checked.' : 'Thanks for your review!');
    }

    public function comment(Request $request, Item $item): RedirectResponse
    {
        abort_unless($item->isPublished() && $item->allow_comments && setting('comments_enabled', true), 404);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $parentId = isset($data['parent_id'])
            ? Comment::where('item_id', $item->id)->whereNull('parent_id')->whereKey($data['parent_id'])->value('id')
            : null;

        $held = (bool) setting('comments_require_approval', true);

        Comment::create([
            'item_id' => $item->id,
            'user_id' => $request->user()->id,
            'parent_id' => $parentId,
            'body' => $data['body'],
            'status' => $held ? Comment::STATUS_PENDING : Comment::STATUS_APPROVED,
        ]);

        return redirect()->to(route('items.show', $item).'#comments')
            ->with('status', $held ? 'Thanks! Your comment will appear once it has been checked.' : 'Comment posted.');
    }
}
