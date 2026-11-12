<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Support\AdTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class AdController extends Controller
{
    /** Image ads link here so clicks are counted, then go on to the advertiser. */
    public function click(Ad $ad): RedirectResponse
    {
        abort_unless($ad->type === Ad::TYPE_IMAGE && filter_var($ad->target_url, FILTER_VALIDATE_URL), 404);

        AdTracker::click($ad);

        return redirect()->away($ad->target_url);
    }

    /** ads.txt as entered in Site settings (required by AdSense and most ad networks). */
    public function adsTxt(): Response
    {
        $body = trim((string) setting('ads_txt'));
        if ($body === '' && ($pub = setting('adsense_publisher_id'))) {
            $body = 'google.com, '.$pub.', DIRECT, f08c47fec0942fa0';
        }

        abort_if($body === '', 404);

        return response($body."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
