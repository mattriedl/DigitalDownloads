<?php

namespace Tests\Feature;

use App\Jobs\PingIndexNow;
use App\Models\Category;
use App\Models\Item;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Support\Images;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
    }

    public function test_sitemap_index_and_children_list_public_urls_only(): void
    {
        $item = Item::published()->firstOrFail();
        $hidden = Item::published()->where('id', '!=', $item->id)->firstOrFail();
        $hidden->update(['noindex' => true]);
        $draft = Item::published()->whereNotIn('id', [$item->id, $hidden->id])->firstOrFail();
        $draft->update(['status' => Item::STATUS_DRAFT]);
        Post::create(['title' => 'Safe downloads', 'body' => '<p>Hi</p>', 'status' => 'published', 'published_at' => now()->subDay()]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('sitemap.items', 1), false)
            ->assertSee(route('sitemap.section', 'blog'), false);

        $items = $this->get('/sitemaps/items-1.xml')->assertOk()->getContent();
        $this->assertNotFalse(simplexml_load_string($items), 'Item sitemap is valid XML');
        $this->assertStringContainsString(route('items.show', $item), $items);
        $this->assertStringNotContainsString(route('items.show', $hidden), $items);
        $this->assertStringNotContainsString(route('items.show', $draft), $items);

        $this->get('/sitemaps/categories.xml')->assertOk()->assertSee(route('categories.show', Category::firstOrFail()), false);
        $this->get('/sitemaps/blog.xml')->assertOk()->assertSee('/blog/safe-downloads', false);
        $this->get('/sitemaps/pages.xml')->assertOk()->assertSee('/page/privacy', false);
        $this->get('/sitemaps/items-99.xml')->assertNotFound();
    }

    public function test_robots_txt_blocks_private_areas_and_points_to_the_sitemap(): void
    {
        app(Settings::class)->set('robots_txt_extra', 'User-agent: GPTBot'."\n".'Disallow: /');

        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /download/*/get')
            ->assertSee('User-agent: GPTBot')
            ->assertSee('Sitemap: '.route('sitemap.index'));
    }

    public function test_indexnow_key_file_is_served_only_for_the_real_key(): void
    {
        $key = setting('indexnow_key');
        $this->get("/{$key}.txt")->assertOk()->assertSeeText($key);
        $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
    }

    public function test_publishing_queues_an_indexnow_ping_and_the_job_posts_the_urls(): void
    {
        Queue::fake();
        $item = Item::create(['title' => 'Brand new tool', 'category_id' => Category::value('id'), 'status' => Item::STATUS_PUBLISHED]);
        Queue::assertPushed(PingIndexNow::class, fn ($job) => $job->urls === [url('/download/brand-new-tool')]);

        Queue::fake();
        Item::create(['title' => 'Still a draft', 'category_id' => Category::value('id'), 'status' => Item::STATUS_DRAFT]);
        Queue::assertNotPushed(PingIndexNow::class);

        // The job itself only talks to IndexNow from a live site.
        Http::fake(['api.indexnow.org/*' => Http::response('', 202)]);
        app(Settings::class)->set('site_url', 'https://example-downloads.com');
        $this->app['env'] = 'production';
        (new PingIndexNow([url($item->publicPath())]))->handle();
        Http::assertSent(fn ($request) => $request['host'] === 'example-downloads.com'
            && $request['key'] === setting('indexnow_key')
            && $request['keyLocation'] === 'https://example-downloads.com/'.setting('indexnow_key').'.txt');
    }

    public function test_changing_a_slug_leaves_a_301_from_the_old_address(): void
    {
        $item = Item::published()->firstOrFail();
        $old = $item->slug;

        $item->update(['slug' => 'renamed-tool']);
        $this->get('/download/'.$old)->assertRedirect('/download/renamed-tool')->assertStatus(301);

        // Renaming again keeps one hop, and going back to an old slug never loops.
        $item->update(['slug' => 'renamed-again']);
        $this->get('/download/'.$old)->assertRedirect('/download/renamed-again');
        $item->update(['slug' => $old]);
        $this->get('/download/'.$old)->assertOk();
        $this->assertFalse(Redirect::where('from_path', '/download/'.$old)->exists());

        $category = Category::firstOrFail();
        $oldCategory = $category->slug;
        $category->update(['slug' => 'new-category-name']);
        $this->get('/category/'.$oldCategory)->assertRedirect('/category/new-category-name');
    }

    public function test_homepage_has_website_schema_and_thin_tag_pages_are_noindexed(): void
    {
        $this->get('/')->assertOk()->assertSee('"@type":"WebSite"', false)->assertSee('search_term_string', false);

        $tag = Tag::create(['name' => 'lonely']);
        Item::published()->firstOrFail()->tags()->attach($tag);
        $this->get('/tag/'.$tag->slug)->assertOk()->assertSee('noindex, follow', false);

        Item::published()->skip(1)->take(3)->get()->each(fn ($item) => $item->tags()->attach($tag));
        $this->get('/tag/'.$tag->slug)->assertOk()->assertDontSee('noindex, follow', false);
    }

    public function test_thumbnails_are_served_as_small_webp_copies(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->image('big.png', 1200, 900)->store('thumbnails', 'public');
        $item = Item::published()->firstOrFail();
        $item->update(['thumbnail' => $path]);

        $html = $this->get('/download/'.$item->slug)->assertOk()->getContent();
        $resized = Images::resizedPath($path, 192);
        $this->assertStringContainsString('storage/'.$resized, $html);
        Storage::disk('public')->assertExists($resized);
        [$w, , $type] = getimagesizefromstring(Storage::disk('public')->get($resized));
        $this->assertSame(192, $w);
        $this->assertSame(IMAGETYPE_WEBP, $type);

        // Replacing the image clears the old copies.
        $item->update(['thumbnail' => UploadedFile::fake()->image('new.png', 300, 300)->store('thumbnails', 'public')]);
        Storage::disk('public')->assertMissing($resized);
    }
}
