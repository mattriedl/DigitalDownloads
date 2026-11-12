<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdZone;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemLink;
use App\Models\PaymentGateway;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataModelTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name = 'Software'): Category
    {
        return Category::create(['name' => $name]);
    }

    private function item(Category $category, array $attrs = []): Item
    {
        return Item::create(array_merge([
            'category_id' => $category->id,
            'title' => 'Test App',
            'status' => Item::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ], $attrs));
    }

    public function test_slugs_are_generated_and_kept_unique(): void
    {
        $cat = $this->category();
        $a = $this->item($cat);
        $b = $this->item($cat);

        $this->assertSame('test-app', $a->slug);
        $this->assertSame('test-app-2', $b->slug);

        // Changing the title keeps the slug (URLs stay stable for SEO).
        $a->update(['title' => 'Renamed App']);
        $this->assertSame('test-app', $a->fresh()->slug);
    }

    public function test_published_scope_hides_drafts_and_future_scheduled_items(): void
    {
        $cat = $this->category();
        $this->item($cat, ['title' => 'Live']);
        $this->item($cat, ['title' => 'Draft', 'status' => Item::STATUS_DRAFT]);
        $this->item($cat, ['title' => 'Later', 'status' => Item::STATUS_SCHEDULED, 'published_at' => now()->addDay()]);
        $this->item($cat, ['title' => 'Due', 'status' => Item::STATUS_SCHEDULED, 'published_at' => now()->subMinute()]);

        $this->assertEqualsCanonicalizing(['Live', 'Due'], Item::published()->pluck('title')->all());
    }

    public function test_category_item_counts_follow_items(): void
    {
        $a = $this->category('A');
        $b = $this->category('B');
        $item = $this->item($a);
        $this->item($a, ['status' => Item::STATUS_DRAFT]);

        $this->assertSame(1, $a->fresh()->items_count);

        $item->update(['category_id' => $b->id]);
        $this->assertSame(0, $a->fresh()->items_count);
        $this->assertSame(1, $b->fresh()->items_count);

        $item->delete();
        $this->assertSame(0, $b->fresh()->items_count);
    }

    public function test_rating_uses_only_approved_reviews(): void
    {
        $item = $this->item($this->category());
        $users = User::factory()->count(3)->create();

        Review::create(['item_id' => $item->id, 'user_id' => $users[0]->id, 'rating' => 5, 'status' => Review::STATUS_APPROVED]);
        Review::create(['item_id' => $item->id, 'user_id' => $users[1]->id, 'rating' => 2, 'status' => Review::STATUS_APPROVED]);
        Review::create(['item_id' => $item->id, 'user_id' => $users[2]->id, 'rating' => 1, 'status' => Review::STATUS_PENDING]);

        $item->refresh();
        $this->assertSame(2, $item->rating_count);
        $this->assertSame(3.5, $item->rating_avg);
    }

    public function test_link_is_flagged_broken_after_repeated_failures_and_recovers(): void
    {
        $item = $this->item($this->category());
        $link = ItemLink::create(['item_id' => $item->id, 'url' => 'https://example.com/file']);

        $link->recordCheck(404);
        $link->recordCheck(null);
        $this->assertFalse($link->is_broken);

        $link->recordCheck(500);
        $this->assertTrue($link->is_broken);

        $link->recordCheck(200);
        $this->assertFalse($link->is_broken);
        $this->assertSame(0, $link->failed_checks);
    }

    public function test_ads_respect_schedule_and_prefer_category_targeting(): void
    {
        $zone = AdZone::create(['key' => 'sidebar_top', 'name' => 'Sidebar']);
        $cat = $this->category();

        Ad::create(['ad_zone_id' => $zone->id, 'name' => 'general', 'type' => 'code', 'code' => 'x']);
        Ad::create(['ad_zone_id' => $zone->id, 'name' => 'targeted', 'type' => 'code', 'code' => 'x', 'category_id' => $cat->id]);
        Ad::create(['ad_zone_id' => $zone->id, 'name' => 'expired', 'type' => 'code', 'code' => 'x', 'ends_at' => now()->subDay()]);

        for ($i = 0; $i < 10; $i++) {
            $this->assertSame('targeted', Ad::pickFor('sidebar_top', $cat->id)->name);
            $this->assertSame('general', Ad::pickFor('sidebar_top')->name);
        }

        $zone->update(['is_active' => false]);
        $this->assertNull(Ad::pickFor('sidebar_top'));
    }

    public function test_settings_round_trip_types_and_encryption(): void
    {
        $settings = app(Settings::class);
        $settings->set('ads_enabled', false);
        $settings->set('countdown_seconds', 15);
        $settings->set('social_links', ['x' => 'https://x.com/site']);
        $settings->set('captcha_secret_key', 'top-secret', 'integrations', encrypted: true);

        $settings->flush();

        $this->assertFalse(setting('ads_enabled'));
        $this->assertSame(15, setting('countdown_seconds'));
        $this->assertSame(['x' => 'https://x.com/site'], setting('social_links'));
        $this->assertSame('top-secret', setting('captcha_secret_key'));
        $this->assertSame('fallback', setting('missing_key', 'fallback'));
        $this->assertDatabaseMissing('settings', ['value' => json_encode('top-secret')]);
    }

    public function test_defaults_seeder_is_idempotent_and_never_overwrites_admin_changes(): void
    {
        $this->seed(DefaultsSeeder::class);
        app(Settings::class)->set('countdown_seconds', 30, 'ads');
        $counts = [AdZone::count(), Setting::count(), PaymentGateway::count()];

        $this->seed(DefaultsSeeder::class);

        $this->assertSame($counts, [AdZone::count(), Setting::count(), PaymentGateway::count()]);
        $this->assertSame(30, setting('countdown_seconds'));
        $this->assertSame(0, PaymentGateway::enabled()->count(), 'Gateways must start switched off');
    }
}
