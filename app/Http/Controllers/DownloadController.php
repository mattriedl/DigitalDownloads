<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Item;
use App\Models\ItemLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Download flow:
 *  1. countdown(): the ad page with a timer (skipped when the countdown is off or for members).
 *  2. go(): reached only through a signed, short-lived link; records the download and
 *     redirects to the external site, or streams a file hosted on this site.
 */
class DownloadController extends Controller
{
    public function countdown(Request $request, Item $item, ?ItemLink $link = null): View|RedirectResponse
    {
        abort_unless($item->isPublished(), 404);

        $links = $item->activeLinks()->get();
        $link = $link && $link->item_id === $item->id && $link->is_active ? $link : $links->first();
        abort_if($link === null, 404);

        $goUrl = URL::temporarySignedRoute('items.go', now()->addMinutes(30), ['item' => $item, 'link' => $link]);

        $user = $request->user();
        $seconds = (int) setting('countdown_seconds', 10);
        $skip = ! setting('countdown_enabled', true) || $seconds <= 0 || ($user && $user->isMember());

        if ($skip) {
            return redirect()->to($goUrl);
        }

        return view('items.countdown', [
            'item' => $item->load('category'),
            'link' => $link,
            'links' => $links,
            'seconds' => min($seconds, 60),
            'goUrl' => $goUrl,
        ]);
    }

    public function go(Request $request, Item $item, ItemLink $link): Response
    {
        abort_unless($item->isPublished() && $link->item_id === $item->id && $link->is_active, 404);

        if ($link->type === ItemLink::TYPE_HOSTED) {
            abort_unless($link->file_path && Storage::disk('local')->exists($link->file_path), 404);
            abort_unless($link->isSafeToServe(), 503, 'This file is still being checked for viruses. Please try again in a few minutes.');

            $this->record($request, $item, $link);

            return Storage::disk('local')->download($link->file_path);
        }

        abort_unless(filter_var($link->url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $link->url), 404);

        $this->record($request, $item, $link);

        return redirect()->away($link->url);
    }

    /** One counted download per visitor, item and hour, so refreshes don't inflate the numbers. */
    private function record(Request $request, Item $item, ItemLink $link): void
    {
        $ipHash = Download::hashIp($request->ip());
        $key = 'dl:'.$item->id.':'.($request->user()?->id ?? $ipHash);

        if (! Cache::add($key, true, now()->addHour())) {
            return;
        }

        Download::create([
            'item_id' => $item->id,
            'item_link_id' => $link->id,
            'user_id' => $request->user()?->id,
            'ip_hash' => $ipHash,
            'referrer' => mb_substr((string) $request->headers->get('referer'), 0, 255) ?: null,
        ]);

        Item::whereKey($item->id)->increment('download_count');
        ItemLink::whereKey($link->id)->increment('click_count');
    }
}
