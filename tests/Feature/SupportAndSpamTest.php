<?php

namespace Tests\Feature;

use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Filament\Resources\SupportTickets\RelationManagers\MessagesRelationManager;
use App\Jobs\SendSms;
use App\Mail\TemplateMail;
use App\Models\ContactMessage;
use App\Models\NotificationTemplate;
use App\Models\SupportTicket;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportAndSpamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultsSeeder::class);
        app(Settings::class)->set('admin_notify_email', 'owner@example.com', 'notifications');
    }

    public function test_member_opens_a_ticket_with_a_file_and_the_admin_is_emailed(): void
    {
        Mail::fake();
        Storage::fake('local');
        $member = User::factory()->create();

        $this->actingAs($member)->get('/account/tickets')->assertOk();
        $this->get('/account/tickets/new')->assertOk();

        $this->post('/account/tickets', [
            'subject' => 'Mirror is down',
            'priority' => 'normal',
            'message' => 'The second mirror for Photo Tool gives a 404.',
            'attachments' => [UploadedFile::fake()->image('error.png')],
        ])->assertRedirect();

        $ticket = SupportTicket::with('messages.attachments')->firstOrFail();
        $this->assertSame('open', $ticket->status);
        $this->assertCount(1, $ticket->messages->first()->attachments);
        Mail::assertQueued(TemplateMail::class, fn ($m) => $m->hasTo('owner@example.com') && str_contains($m->renderedSubject, 'Mirror is down'));

        $file = $ticket->messages->first()->attachments->first();
        $this->get("/account/tickets/{$ticket->id}")->assertOk()->assertSee('error.png');
        $this->get("/account/tickets/{$ticket->id}/files/{$file->id}")->assertOk()->assertDownload('error.png');
    }

    public function test_tickets_and_their_files_are_private(): void
    {
        $owner = User::factory()->create();
        $ticket = SupportTicket::create(['user_id' => $owner->id, 'subject' => 'Mine', 'status' => 'open']);

        $this->actingAs(User::factory()->create())->get("/account/tickets/{$ticket->id}")->assertNotFound();
        $this->post("/account/tickets/{$ticket->id}/reply", ['message' => 'Hi'])->assertNotFound();

        $staff = User::factory()->create();
        $staff->forceFill(['role' => User::ROLE_EDITOR])->save();
        $this->actingAs($staff)->get("/account/tickets/{$ticket->id}")->assertOk();
    }

    public function test_member_reply_reopens_a_closed_ticket_and_staff_reply_emails_the_member(): void
    {
        Mail::fake();
        $member = User::factory()->create();
        $ticket = SupportTicket::create(['user_id' => $member->id, 'subject' => 'Help', 'status' => 'open', 'last_reply_at' => now()]);

        $this->actingAs($member)->post("/account/tickets/{$ticket->id}/close")->assertRedirect();
        $this->assertSame('closed', $ticket->fresh()->status);

        $this->post("/account/tickets/{$ticket->id}/reply", ['message' => 'Still broken'])->assertRedirect();
        $this->assertSame('customer_reply', $ticket->fresh()->status);

        $admin = User::factory()->create();
        $admin->forceFill(['role' => User::ROLE_ADMIN])->save();
        $this->actingAs($admin);

        Livewire::test(MessagesRelationManager::class, ['ownerRecord' => $ticket, 'pageClass' => ViewSupportTicket::class])
            ->callAction(TestAction::make('reply')->table(), ['body' => 'Fixed now.'])
            ->assertHasNoErrors();

        $this->assertSame('answered', $ticket->fresh()->status);
        Mail::assertQueued(TemplateMail::class, fn ($m) => $m->hasTo($member->email) && str_contains($m->renderedBody, 'Fixed now.'));
    }

    public function test_captcha_is_off_until_keys_are_set_then_required(): void
    {
        $this->get('/contact')->assertDontSee('cf-turnstile');

        $settings = app(Settings::class);
        $settings->setMany(['captcha_provider' => 'turnstile', 'captcha_site_key' => 'site-key'], 'integrations');
        $settings->set('captcha_secret_key', 'secret', 'integrations', encrypted: true);

        $this->get('/contact')->assertSee('class="cf-turnstile"', false)->assertSee('challenges.cloudflare.com/turnstile');
        $this->get('/login')->assertSee('cf-turnstile');
        $this->get('/register')->assertSee('cf-turnstile');

        $message = ['name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Please add an app.'];

        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()
            ->push(['success' => false])
            ->push(['success' => true])]);

        // No token: rejected without calling Cloudflare.
        $this->post('/contact', $message)->assertSessionHasErrors('cf-turnstile-response');
        Http::assertSentCount(0);

        // Cloudflare says no, then yes.
        $this->post('/contact', $message + ['cf-turnstile-response' => 'bad'])->assertSessionHasErrors('cf-turnstile-response');
        $this->post('/contact', $message + ['cf-turnstile-response' => 'good'])->assertSessionHasNoErrors();

        $this->assertSame(1, ContactMessage::count());
        $this->assertNull(ContactMessage::first()->getAttribute('cf-turnstile-response'));
    }

    public function test_sign_in_checks_the_captcha_before_the_password(): void
    {
        $settings = app(Settings::class);
        $settings->setMany(['captcha_provider' => 'recaptcha', 'captcha_site_key' => 'k'], 'integrations');
        $settings->set('captcha_secret_key', 's', 'integrations', encrypted: true);
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('g-recaptcha-response');
        $this->assertGuest();

        Http::fake(['www.google.com/*' => Http::response(['success' => true])]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'g-recaptcha-response' => 'ok'])->assertRedirect('/account');
    }

    public function test_admin_alerts_are_texted_when_sms_is_on_for_that_alert(): void
    {
        Mail::fake();
        Queue::fake();
        $settings = app(Settings::class);
        $settings->setMany(['sms_provider' => 'twilio', 'sms_from' => '+15035550000', 'admin_notify_phone' => '+15035551234'], 'notifications');
        $settings->set('sms_api_key', 'AC123:token', 'notifications', encrypted: true);

        // SMS is off by default for every template.
        $this->post('/contact', ['name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Please add an app.']);
        Queue::assertNotPushed(SendSms::class);

        NotificationTemplate::where('key', 'admin_contact_message')->update(['sms_enabled' => true]);
        $this->post('/contact', ['name' => 'Bo', 'email' => 'bo@example.com', 'message' => 'Another request.']);

        Queue::assertPushed(SendSms::class, fn ($job) => $job->to === '+15035551234' && str_contains($job->body, 'contact message from Bo'));
    }

    public function test_sms_job_calls_twilio(): void
    {
        $settings = app(Settings::class);
        $settings->setMany(['sms_provider' => 'twilio', 'sms_from' => '+15035550000'], 'notifications');
        $settings->set('sms_api_key', 'AC123:token', 'notifications', encrypted: true);
        Http::fake(['api.twilio.com/*' => Http::response(['sid' => 'SM1'], 201)]);

        (new SendSms('+15035551234', 'Hello'))->handle();

        Http::assertSent(fn ($request) => str_contains($request->url(), '/Accounts/AC123/Messages.json')
            && $request['To'] === '+15035551234' && $request['From'] === '+15035550000' && $request['Body'] === 'Hello');
    }
}
