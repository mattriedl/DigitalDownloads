<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
    }

    public function test_rich_text_from_editors_cannot_run_scripts(): void
    {
        $evil = '<p>Hello <strong>there</strong></p><script>steal()</script><img src="/x.png" onerror="steal()"><a href="javascript:steal()">x</a>';
        $item = Item::published()->firstOrFail();
        $item->update(['description' => $evil]);
        $page = Page::create(['title' => 'About us', 'slug' => 'about-us-test', 'body' => $evil, 'is_active' => true]);

        foreach (['/download/'.$item->slug, '/page/'.$page->slug] as $url) {
            $this->get($url)->assertOk()
                ->assertSee('<strong>there</strong>', false)
                ->assertDontSee('steal()', false);
        }
    }

    public function test_a_user_banned_mid_session_is_signed_out(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/account')->assertOk();

        $user->forceFill(['banned_at' => now()])->save();
        $this->get('/account')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_sign_ups_are_rate_limited_per_ip(): void
    {
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->post('/register', [
                'name' => "Person $i", 'email' => "person$i@example.com",
                'password' => 'a-long-password-1', 'password_confirmation' => 'a-long-password-1', 'terms' => '1',
            ]);
            auth()->logout();
        }
        $response->assertSessionHasErrors('email');
        $this->assertSame(5, User::where('email', 'like', 'person%')->count());
    }

    public function test_error_pages_render_in_the_site_style(): void
    {
        Route::get('/_boom', fn () => throw new \RuntimeException('kaboom'));
        Route::get('/_busy', fn () => abort(503));
        config(['app.debug' => false]);

        $this->get('/_boom')->assertStatus(500)->assertSee('Something went wrong on our side')->assertDontSee('kaboom');
        $this->get('/_busy')->assertStatus(503)->assertSee('Back in a few minutes');

        $item = Item::published()->has('links')->with('links')->firstOrFail();
        $this->get('/download/'.$item->slug.'/go/'.$item->links->first()->id)->assertStatus(403)->assertSee('You can’t open this page', false);

        // The maintenance page pre-renders without a request or database.
        try {
            $this->artisan('down', ['--render' => 'errors::503'])->assertSuccessful();
            $this->assertStringContainsString('Back in a few minutes', json_decode(file_get_contents(storage_path('framework/down')), true)['template']);
        } finally {
            @unlink(storage_path('framework/down'));
            @unlink(storage_path('framework/maintenance.php'));
        }
    }
}
