<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Visitors report a broken link, wrong info or a copyright problem from the item page. */
class ReportController extends Controller
{
    public function store(Request $request, Item $item): RedirectResponse
    {
        // Bots fill every field; people never see this one.
        if (filled($request->input('website'))) {
            return back()->with('status', 'Thanks, we received your report.');
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(Report::TYPES)],
            'item_link_id' => ['nullable', Rule::exists('item_links', 'id')->where('item_id', $item->id)],
            'message' => ['nullable', 'string', 'max:3000', Rule::requiredIf(fn () => in_array($request->input('type'), ['dmca', 'other'], true))],
            'name' => ['nullable', 'string', 'max:120', Rule::requiredIf(fn () => $request->input('type') === 'dmca')],
            'email' => ['nullable', 'email', 'max:190', Rule::requiredIf(fn () => $request->input('type') === 'dmca')],
        ], [
            'message.required' => 'Tell us what the problem is.',
            'name.required' => 'Copyright notices need the name of the owner or their agent.',
            'email.required' => 'Copyright notices need an email address so we can reply.',
        ]);

        Report::create($data + ['item_id' => $item->id, 'user_id' => $request->user()?->id]);

        return redirect()->to(route('items.show', $item).'#report')
            ->with('status', 'Thanks, we received your report and will look at it soon.');
    }
}
