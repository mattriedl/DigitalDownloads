<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Items\Pages\CreateItem;
use App\Filament\Resources\Items\Pages\EditItem;
use App\Filament\Resources\PaymentGateways\Pages\EditPaymentGateway;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\PaymentGateway;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($this->admin);
    }

    public function test_admin_can_create_an_item_with_external_download_links(): void
    {
        $category = Category::create(['name' => 'Utilities']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'title' => 'Handy Zip Tool',
                'category_id' => $category->id,
                'short_description' => 'Compress files quickly.',
                'status' => Item::STATUS_PUBLISHED,
                'links' => [
                    ['label' => 'Official site', 'type' => 'external', 'url' => 'https://example.com/zip', 'is_active' => true],
                    ['label' => 'Mirror', 'type' => 'external', 'url' => 'https://mirror.example.org/zip', 'is_active' => true],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = Item::where('title', 'Handy Zip Tool')->firstOrFail();
        $this->assertSame('handy-zip-tool', $item->slug);
        $this->assertTrue($item->isPublished());
        $this->assertSame($this->admin->id, $item->created_by);
        $this->assertSame(['Official site', 'Mirror'], $item->links()->pluck('label')->all());
        $this->assertSame(1, $category->fresh()->items_count);
        $this->assertDatabaseHas('activity_log', ['action' => 'created', 'subject_type' => Item::class, 'subject_id' => $item->id]);
    }

    public function test_seo_fields_enforce_length_and_no_years(): void
    {
        $category = Category::create(['name' => 'Utilities']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'title' => 'Best Zip Tool 2026',
                'category_id' => $category->id,
                'status' => Item::STATUS_DRAFT,
                'seo_title' => str_repeat('a', 71),
                'links' => [['label' => 'Get it', 'type' => 'external', 'url' => 'https://example.com']],
            ])
            ->call('create')
            ->assertHasFormErrors(['title', 'seo_title']);
    }

    public function test_external_link_requires_a_url(): void
    {
        $category = Category::create(['name' => 'Utilities']);

        Livewire::test(CreateItem::class)
            ->fillForm([
                'title' => 'No Link Tool',
                'category_id' => $category->id,
                'status' => Item::STATUS_DRAFT,
                'links' => [['label' => 'Get it', 'type' => 'external', 'url' => '']],
            ])
            ->call('create')
            ->assertHasErrors();

        $this->assertDatabaseMissing('items', ['title' => 'No Link Tool']);
    }

    public function test_editing_an_item_keeps_its_slug(): void
    {
        $item = Item::create(['category_id' => Category::create(['name' => 'X'])->id, 'title' => 'Original Name', 'status' => 'draft']);

        Livewire::test(EditItem::class, ['record' => $item->slug])
            ->fillForm(['title' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('original-name', $item->fresh()->slug);
        $this->assertSame('New Name', $item->fresh()->title);
    }

    public function test_settings_page_saves_values_and_keeps_secrets(): void
    {
        app(Settings::class)->set('captcha_secret_key', 'old-secret', 'integrations', encrypted: true);

        Livewire::test(SiteSettings::class)
            ->assertSet('data.captcha_secret_key', null) // secrets never reach the browser
            ->fillForm([
                'site_name' => 'Free Apps Hub',
                'site_url' => 'https://freeapps.test/',
                'countdown_seconds' => 15,
                'ads_enabled' => false,
                'captcha_secret_key' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Free Apps Hub', setting('site_name'));
        $this->assertSame('https://freeapps.test', setting('site_url'));
        $this->assertSame(15, setting('countdown_seconds'));
        $this->assertFalse(setting('ads_enabled'));
        $this->assertSame('old-secret', setting('captcha_secret_key'));
        $this->assertTrue(ActivityLog::where('action', 'settings')->exists());
    }

    public function test_admin_can_promote_and_ban_users_but_not_demote_themselves(): void
    {
        $user = User::factory()->create();

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['role' => User::ROLE_EDITOR, 'banned_at' => now()->toDateTimeString()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(User::ROLE_EDITOR, $user->fresh()->role);
        $this->assertTrue($user->fresh()->isBanned());

        Livewire::test(EditUser::class, ['record' => $this->admin->id])
            ->fillForm(['name' => 'Renamed Admin'])
            ->call('save');
        $this->assertSame(User::ROLE_ADMIN, $this->admin->fresh()->role);
    }

    public function test_gateway_secret_left_blank_keeps_the_saved_key(): void
    {
        $stripe = PaymentGateway::where('code', 'stripe')->firstOrFail();
        $stripe->update(['credentials' => ['publishable_key' => 'pk_test_1', 'secret_key' => 'sk_test_1', 'webhook_secret' => null]]);

        Livewire::test(EditPaymentGateway::class, ['record' => $stripe->id])
            ->fillForm(['credentials.publishable_key' => 'pk_test_2', 'credentials.secret_key' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $credentials = $stripe->fresh()->credentials;
        $this->assertSame('pk_test_2', $credentials['publishable_key']);
        $this->assertSame('sk_test_1', $credentials['secret_key']);
        $this->assertFalse($stripe->fresh()->is_enabled);
    }

    public function test_staff_reply_answers_the_ticket(): void
    {
        $customer = User::factory()->create();
        $ticket = SupportTicket::create(['user_id' => $customer->id, 'subject' => 'Link broken', 'status' => 'open', 'last_reply_at' => now()]);
        $ticket->messages()->create(['user_id' => $customer->id, 'body' => 'The mirror is down.']);

        Livewire::test(MessagesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewSupportTicket::class])
            ->callAction(TestAction::make('reply')->table(), ['body' => 'Fixed, thanks!'])
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame('answered', $ticket->status);
        $this->assertSame($this->admin->id, $ticket->assigned_to);
        $this->assertTrue($ticket->messages()->where('is_staff', true)->where('body', 'Fixed, thanks!')->exists());
    }
}
