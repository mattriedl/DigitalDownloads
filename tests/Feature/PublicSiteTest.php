<?php

namespace Tests\Feature;

use App\Models\Ad;
use App\Models\AdZone;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Download;
use App\Models\Item;
use App\Models\Page;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Report;
use App\Support\AdTracker;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
        $this->item = Item::published()->with('links')->firstOrFail();
        Post::create(['title' => 'Safe downloads', 'body' => '<p>Hi</p>', 'status' => 'published', 'published_at' => now()->subDay(),
            'faq' => [['question' => 'Is it free?', 'answer' => 'Yes.']]]);
    }

    public function test_every_public_page_loads(): void
    {
        $category = Category::whereNull('parent_id')->firstOrFail();
        $child = Category::whereNotNull('parent_id')->firstOrFail();

        foreach ([
            '/', '/categories', "/category/{$category->slug}", "/category/{$child->slug}?sort=rating",
            '/popular', '/latest', '/top-rated', '/search', '/search?q=Sample', '/tag/free',
            "/download/{$this->item->slug}", "/download/{$this->item->slug}/get",
            '/blog', '/blog/safe-downloads', '/page/dmca', '/contact',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_item_page_shows_details_download_button_and_structured_data(): void
    {
        $this->get("/download/{$this->item->slug}")
            ->assertSee($this->item->title)
            ->assertSee(route('items.get', [$this->item, $this->item->links->first()]), false)
            ->assertSee('"@type":"SoftwareApplication"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_drafts_and_inactive_categories_are_hidden(): void
    {
        $this->item->update(['status' => Item::STATUS_DRAFT]);
        $this->get("/download/{$this->item->slug}")->assertNotFound();
        $this->get("/download/{$this->item->slug}/get")->assertNotFound();

        $this->item->category->update(['is_active' => false]);
        $this->get("/category/{$this->item->category->slug}")->assertNotFound();
    }

    public function test_countdown_page_hands_out_a_signed_link_that_records_one_download_and_redirects(): void
    {
        $link = $this->item->links->first();
        $before = $this->item->download_count;

        $this->get("/download/{$this->item->slug}/get")
            ->assertOk()
            ->assertSee('data-countdown="10"', false);

        // The real link only works with a valid signature.
        $this->get("/download/{$this->item->slug}/go/{$link->id}")->assertForbidden();

        $signed = URL::temporarySignedRoute('items.go', now()->addMinutes(30), ['item' => $this->item, 'link' => $link]);
        $this->get($signed)->assertRedirect($link->url);
        $this->get($signed)->assertRedirect($link->url); // refresh: not counted twice

        $this->assertSame($before + 1, $this->item->fresh()->download_count);
        $this->assertSame(1, Download::where('item_id', $this->item->id)->count());
        $this->assertSame(1, $link->fresh()->click_count);
    }

    public function test_countdown_can_be_switched_off(): void
    {
        app(Settings::class)->set('countdown_enabled', false, 'ads');

        $response = $this->get("/download/{$this->item->slug}/get");
        $response->assertRedirect();
        $this->assertStringContainsString('/go/', $response->headers->get('Location'));
    }

    public function test_links_from_another_item_cannot_be_used(): void
    {
        $other = Item::published()->whereKeyNot($this->item->id)->firstOrFail();
        $foreignLink = $other->links()->first();

        $signed = URL::temporarySignedRoute('items.go', now()->addMinutes(30), ['item' => $this->item, 'link' => $foreignLink]);
        $this->get($signed)->assertNotFound();
    }

    public function test_visitors_can_report_a_problem_and_dmca_needs_contact_details(): void
    {
        $this->post("/download/{$this->item->slug}/report", ['type' => 'broken_link', 'message' => 'Mirror is down'])
            ->assertRedirect();
        $this->assertDatabaseHas('reports', ['item_id' => $this->item->id, 'type' => 'broken_link', 'status' => 'open']);

        $this->post("/download/{$this->item->slug}/report", ['type' => 'dmca', 'message' => 'This is mine'])
            ->assertSessionHasErrors(['name', 'email']);

        // Bots that fill the hidden field are silently ignored.
        $this->post("/download/{$this->item->slug}/report", ['type' => 'other', 'message' => 'spam', 'website' => 'http://spam.test']);
        $this->assertSame(1, Report::count());
    }

    public function test_contact_form_saves_messages_and_ignores_bots(): void
    {
        $this->post('/contact', ['name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Please add a new app.'])
            ->assertRedirect('/contact');
        $this->post('/contact', ['name' => 'Bot', 'email' => 'bot@example.com', 'message' => 'Buy things now!!', 'website' => 'x']);
        $this->post('/contact', ['name' => '', 'email' => 'nope', 'message' => 'short'])->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertSame(['Ana'], ContactMessage::pluck('name')->all());
    }

    public function test_search_finds_items_and_handles_no_results(): void
    {
        $this->get('/search?q='.urlencode($this->item->title))->assertSee($this->item->title);
        $this->get('/search?q=zzzznothing')->assertSee('No downloads match');
    }

    public function test_admin_redirects_and_gone_pages_work(): void
    {
        Redirect::create(['from_path' => '/old-app', 'to_url' => '/download/'.$this->item->slug, 'status_code' => 301]);
        Redirect::create(['from_path' => '/removed', 'to_url' => '/', 'status_code' => 410]);

        $this->get('/old-app/')->assertStatus(301)->assertRedirect('/download/'.$this->item->slug);
        $this->get('/removed')->assertStatus(410);
        $this->get('/does-not-exist')->assertNotFound()->assertSee("couldn't find that page", false);
        $this->assertSame(1, Redirect::where('from_path', '/old-app')->value('hits'));
    }

    public function test_ads_render_count_impressions_and_track_clicks(): void
    {
        $zone = AdZone::where('key', 'sidebar_top')->firstOrFail();
        $zone->ads()->update(['is_active' => false]); // leave only the test ad in this zone
        $ad = Ad::create(['ad_zone_id' => $zone->id, 'name' => 'Sponsor', 'type' => Ad::TYPE_IMAGE, 'image_path' => 'ads/x.png', 'target_url' => 'https://sponsor.test']);

        $this->get('/')->assertSee(route('ads.click', $ad), false);
        app(AdTracker::class)->flush();
        $this->assertSame(1, $ad->fresh()->impressions);

        $this->get(route('ads.click', $ad))->assertRedirect('https://sponsor.test');
        $this->assertSame(1, $ad->fresh()->clicks);
        $this->assertDatabaseHas('ad_stats', ['ad_id' => $ad->id, 'clicks' => 1]);

        app(Settings::class)->set('ads_enabled', false, 'ads');
        $this->get('/')->assertDontSee(route('ads.click', $ad), false)->assertDontSee('Ad slot:');
    }

    public function test_ads_txt_is_served_from_settings(): void
    {
        $this->get('/ads.txt')->assertNotFound();

        app(Settings::class)->set('adsense_publisher_id', 'pub-123', 'ads');
        $this->get('/ads.txt')->assertOk()->assertSeeText('google.com, pub-123, DIRECT, f08c47fec0942fa0');
    }

    public function test_site_settings_change_name_and_theme(): void
    {
        app(Settings::class)->setMany(['site_name' => 'Free Apps Hub', 'theme' => 'light']);

        $this->get('/')->assertSee('<title>Free Apps Hub</title>', false)->assertDontSee('class="dark"', false);
    }

    public function test_inactive_pages_are_hidden(): void
    {
        Page::where('slug', 'dmca')->update(['is_active' => false]);
        $this->get('/page/dmca')->assertNotFound();
    }

    public function test_blog_post_has_faq_schema(): void
    {
        $this->get('/blog/safe-downloads')->assertSee('"@type":"FAQPage"', false)->assertSee('Is it free?');
    }
}
