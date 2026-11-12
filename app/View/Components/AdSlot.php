<?php

namespace App\View\Components;

use App\Models\Ad;
use App\Models\AdZone;
use App\Support\AdTracker;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-ad-slot zone="sidebar_top" :category="$item->category_id" />
 *
 * Shows one running ad for the zone. Outside production an empty zone shows a labeled
 * placeholder so the layout can be reviewed before any ads exist; on a live site it renders nothing.
 */
class AdSlot extends Component
{
    public ?Ad $ad = null;

    public ?AdZone $zoneModel = null;

    public bool $placeholder = false;

    public function __construct(public string $zone, public ?int $category = null, public string $class = '')
    {
        if (! setting('ads_enabled', true)) {
            return;
        }

        $user = auth()->user();
        $isMember = $user && method_exists($user, 'isMember') && $user->isMember();

        $this->ad = Ad::pickFor($zone, $category);

        if ($this->ad && $isMember && $this->ad->hide_for_members) {
            $this->ad = null;

            return;
        }

        if ($this->ad) {
            app(AdTracker::class)->impression($this->ad);
            if ($this->ad->type === Ad::TYPE_BANNERPROMOTE) {
                app(AdTracker::class)->needsBannerPromote = true;
            }

            return;
        }

        if (! app()->isProduction() && ! $isMember) {
            $this->placeholder = true;
            $this->zoneModel = once(fn () => AdZone::all()->keyBy('key'))->get($zone);
        }
    }

    public function shouldRender(): bool
    {
        return $this->ad !== null || $this->placeholder;
    }

    public function render(): View
    {
        return view('components.ad-slot');
    }
}
