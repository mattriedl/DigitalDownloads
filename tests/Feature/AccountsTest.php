<?php

namespace Tests\Feature;

use App\Mail\TemplateMail;
use App\Models\Comment;
use App\Models\Item;
use App\Models\Review;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountsTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([DefaultsSeeder::class, DemoSeeder::class]);
        $this->item = Item::published()->firstOrFail();
    }

    private function member(bool $verified = true): User
    {
        return User::factory()->create(['email_verified_at' => $verified ? now() : null]);
    }

    public function test_sign_in_and_sign_up_pages_load(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_visitor_can_sign_up_and_gets_a_welcome_email(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'a-good-password',
            'password_confirmation' => 'a-good-password',
            'terms' => '1',
        ])->assertRedirect('/account');

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_USER, $user->role);
        $this->assertAuthenticatedAs($user);
        Mail::assertQueued(TemplateMail::class, fn ($mail) => $mail->hasTo('ana@example.com') && str_contains($mail->renderedBody, 'Ana'));
    }

    public function test_sign_up_needs_terms_rejects_bots_and_can_be_closed(): void
    {
        $base = ['name' => 'Bo', 'email' => 'bo@example.com', 'password' => 'a-good-password', 'password_confirmation' => 'a-good-password'];

        $this->post('/register', $base)->assertSessionHasErrors('terms');
        $this->post('/register', $base + ['terms' => '1', 'website' => 'spam'])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'bo@example.com']);

        app(Settings::class)->set('registration_enabled', false);
        $this->get('/register')->assertNotFound();
        $this->post('/register', $base + ['terms' => '1'])->assertForbidden();
    }

    public function test_banned_members_cannot_sign_in_and_sign_in_is_recorded(): void
    {
        $user = $this->member();
        $user->forceFill(['banned_at' => now()])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $user->forceFill(['banned_at' => null])->save();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/account');
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_account_page_shows_saved_downloads_and_history(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post("/download/{$this->item->slug}/save")->assertRedirect();
        $this->assertTrue($user->favorites()->whereKey($this->item->id)->exists());

        $this->get('/account')->assertOk()->assertSee($this->item->title);
        $this->get("/download/{$this->item->slug}")->assertSee('aria-pressed="true"', false);

        $this->post("/download/{$this->item->slug}/save");
        $this->assertFalse($user->favorites()->whereKey($this->item->id)->exists());
    }

    public function test_reviews_need_a_confirmed_email_and_follow_the_approval_setting(): void
    {
        $unverified = $this->member(verified: false);
        $this->actingAs($unverified)->post("/download/{$this->item->slug}/review", ['rating' => 5])
            ->assertRedirect(route('verification.notice'));

        $user = $this->member();
        $this->actingAs($user)->post("/download/{$this->item->slug}/review", ['rating' => 4, 'title' => 'Good'])
            ->assertRedirect();
        $this->assertSame(Review::STATUS_PENDING, Review::firstWhere('user_id', $user->id)->status);
        $this->assertSame(0, $this->item->fresh()->rating_count); // pending reviews don't count yet

        // Writing again updates the same review.
        app(Settings::class)->set('reviews_require_approval', false, 'community');
        $this->post("/download/{$this->item->slug}/review", ['rating' => 2]);
        $this->assertSame(1, Review::where('user_id', $user->id)->count());
        $this->assertSame(2.0, $this->item->fresh()->rating_avg);

        $this->post("/download/{$this->item->slug}/review", ['rating' => 9])->assertSessionHasErrors('rating');
    }

    public function test_comments_are_held_for_approval_by_default(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post("/download/{$this->item->slug}/comment", ['body' => 'Works on Windows 11?'])->assertRedirect();

        $comment = Comment::firstOrFail();
        $this->assertSame(Comment::STATUS_PENDING, $comment->status);
        $this->get("/download/{$this->item->slug}")->assertDontSee('Works on Windows 11?');

        $comment->update(['status' => Comment::STATUS_APPROVED]);
        $this->get("/download/{$this->item->slug}")->assertSee('Works on Windows 11?');

        $this->item->update(['allow_comments' => false]);
        $this->post("/download/{$this->item->slug}/comment", ['body' => 'Another one'])->assertNotFound();
    }

    public function test_members_can_update_their_profile_and_delete_their_account(): void
    {
        $user = $this->member();
        $this->actingAs($user)->get('/account/settings')->assertOk();

        $this->put('/user/profile-information', ['name' => 'New Name', 'email' => $user->email])->assertRedirect();
        $this->assertSame('New Name', $user->fresh()->name);

        $this->delete('/account', ['password' => 'wrong'])->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->delete('/account', ['password' => 'password'])->assertRedirect('/');
        $this->assertModelMissing($user);
        $this->assertGuest();
    }

    public function test_staff_cannot_delete_themselves_from_the_site(): void
    {
        $admin = $this->member();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();

        $this->actingAs($admin)->delete('/account', ['password' => 'password'])->assertForbidden();
        $this->assertModelExists($admin);
    }

    public function test_google_sign_in_is_off_until_configured(): void
    {
        $this->get('/auth/google/redirect')->assertNotFound();
        $this->get('/login')->assertDontSee('Continue with Google');

        app(Settings::class)->setMany(['google_login_enabled' => true, 'google_client_id' => 'abc.apps.googleusercontent.com'], 'integrations');
        app(Settings::class)->set('google_client_secret', 'secret', 'integrations', encrypted: true);

        $this->get('/login')->assertSee('Continue with Google');
        $this->get('/auth/google/redirect')->assertRedirectContains('accounts.google.com');
    }

    public function test_guests_are_asked_to_sign_in_for_member_actions(): void
    {
        $this->post("/download/{$this->item->slug}/save")->assertRedirect('/login');
        $this->get('/account')->assertRedirect('/login');
        $this->get("/download/{$this->item->slug}")->assertSee('to rate and review');
    }
}
