<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
    }

    private function staff(string $role = User::ROLE_ADMIN): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }

    public function test_guests_are_sent_to_login_and_regular_users_are_blocked(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();

        $banned = $this->staff();
        $banned->forceFill(['banned_at' => now()])->save();
        $this->actingAs($banned)->get('/admin')->assertForbidden();
    }

    public function test_every_admin_page_loads_for_an_admin(): void
    {
        $this->actingAs($this->staff());

        $this->get('/admin')->assertOk();

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $pages = $resource::getPages();

            foreach (['index', 'create'] as $page) {
                if (isset($pages[$page])) {
                    $this->get($resource::getUrl($page))->assertOk();
                }
            }

            /** @var Model|null $record */
            $record = $resource::getModel()::query()->first();
            if ($record) {
                foreach (['view', 'edit'] as $page) {
                    if (isset($pages[$page])) {
                        $this->get($resource::getUrl($page, ['record' => $record]))->assertOk();
                    }
                }
            }
        }

        foreach (Filament::getPanel('admin')->getPages() as $page) {
            $this->get($page::getUrl())->assertOk();
        }
    }

    public function test_editors_cannot_open_admin_only_screens(): void
    {
        $this->actingAs($this->staff(User::ROLE_EDITOR));

        $this->get('/admin/items')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/payment-gateways')->assertForbidden();
    }
}
