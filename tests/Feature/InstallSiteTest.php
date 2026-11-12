<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallSiteTest extends TestCase
{
    use RefreshDatabase;

    private array $args = [
        '--name' => 'Free Apps Hub',
        '--url' => 'https://freeapps.test/',
        '--email' => 'hello@freeapps.test',
        '--admin-email' => 'owner@freeapps.test',
        '--admin-password' => 'a-long-enough-password',
        '--no-interaction' => true,
    ];

    public function test_installer_sets_up_the_site_and_first_admin(): void
    {
        $this->artisan('site:install', $this->args)->assertSuccessful();

        $this->assertSame('Free Apps Hub', setting('site_name'));
        $this->assertSame('https://freeapps.test', setting('site_url'));
        $this->assertNotNull(setting('installed_at'));

        $admin = User::where('email', 'owner@freeapps.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('a-long-enough-password', $admin->password));

        $this->assertDatabaseHas('ad_zones', ['key' => 'download_countdown_main']);
        $this->assertDatabaseHas('pages', ['slug' => 'dmca']);
        $this->assertDatabaseCount('items', 0); // no demo content unless --demo
    }

    public function test_installer_refuses_to_run_twice_without_force(): void
    {
        $this->artisan('site:install', $this->args)->assertSuccessful();
        $this->artisan('site:install', $this->args)->assertFailed();
        $this->artisan('site:install', $this->args + ['--force' => true])->assertSuccessful();
    }

    public function test_installer_rejects_weak_password_and_bad_url(): void
    {
        $this->artisan('site:install', ['--admin-password' => 'short'] + $this->args)->assertFailed();
        $this->artisan('site:install', ['--url' => 'not a url'] + $this->args)->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_flag_loads_sample_content(): void
    {
        $this->artisan('site:install', $this->args + ['--demo' => true])->assertSuccessful();

        $this->assertGreaterThan(0, Item::published()->count());
        $this->assertGreaterThan(0, ItemLink::count());
    }
}
