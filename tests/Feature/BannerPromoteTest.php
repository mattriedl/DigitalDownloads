<?php

namespace Tests\Feature;

use App\Filament\Resources\Ads\Pages\CreateAd;
use App\Models\Ad;
use App\Models\AdZone;
use App\Models\User;
use App\Support\BannerPromote;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BannerPromoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
    }

    public function test_new_sites_get_a_fitting_bannerpromote_ad_in_every_zone(): void
    {
        $this->assertSame(AdZone::count(), Ad::where('type', Ad::TYPE_BANNERPROMOTE)->count());

        $this->assertSame(['leaderboard', null], $this->spots('header_leaderboard'));
        $this->assertSame(['medium-rectangle', null], $this->spots('sidebar_top'));
        $this->assertSame(['half-page', 'medium-rectangle'], $this->spots('sidebar_middle'));
        $this->assertSame(['large-rectangle', 'medium-rectangle'], $this->spots('download_countdown_main'));
        $this->assertSame(['mobile-banner', null], $this->spots('sticky_mobile_footer'));

        // Every spot used is one BannerPromote offers.
        foreach (Ad::all() as $ad) {
            $this->assertArrayHasKey($ad->spot, BannerPromote::SPOTS);
        }
    }

    public function test_ads_removed_by_the_admin_do_not_come_back_on_deploy(): void
    {
        Ad::where('type', Ad::TYPE_BANNERPROMOTE)->delete();

        $this->seed(DefaultsSeeder::class); // runs on every deploy

        $this->assertSame(0, Ad::count());
    }

    public function test_pages_show_spots_and_load_the_script_once(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('data-bp-spot="leaderboard"', $html);
        $this->assertStringContainsString('data-bp-mobile-spot="medium-rectangle"', $html);
        $this->assertSame(1, substr_count($html, 'src="https://bannerpromote.com/embed.js.php"'));
        $this->assertStringNotContainsString('Ad slot:', $html);
    }

    public function test_script_is_skipped_when_ads_are_off_or_none_are_bannerpromote(): void
    {
        app(Settings::class)->set('ads_enabled', false, 'ads');
        $this->assertStringNotContainsString('embed.js.php', $this->get('/')->getContent());

        app(Settings::class)->set('ads_enabled', true, 'ads');
        Ad::query()->update(['is_active' => false]);
        $this->assertStringNotContainsString('embed.js.php', $this->get('/')->getContent());
    }

    public function test_admin_can_create_a_bannerpromote_ad(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $zone = AdZone::where('key', 'item_in_description')->firstOrFail();

        Livewire::actingAs($admin)->test(CreateAd::class)
            ->fillForm([
                'name' => 'Square test',
                'ad_zone_id' => $zone->id,
                'type' => Ad::TYPE_BANNERPROMOTE,
                'spot' => 'square',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('ads', ['name' => 'Square test', 'spot' => 'square', 'type' => 'bannerpromote']);
    }

    private function spots(string $zoneKey): array
    {
        $ad = Ad::whereHas('zone', fn ($q) => $q->where('key', $zoneKey))->firstOrFail();

        return [$ad->spot, $ad->mobile_spot];
    }
}
