<?php

namespace Tests\Feature;

use App\Jobs\ScanHostedFile;
use App\Mail\TemplateMail;
use App\Models\Item;
use App\Models\ItemLink;
use App\Support\Settings;
use App\Support\VirusScanner;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LinksAndFilesTest extends TestCase
{
    use RefreshDatabase;

    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DefaultsSeeder::class);
        app(Settings::class)->set('admin_notify_email', 'owner@example.com', 'notifications');
        $this->seed(DemoSeeder::class);
        $this->item = Item::published()->firstOrFail();
        // Keep the test focused: only this item's two links exist.
        ItemLink::where('item_id', '!=', $this->item->id)->delete();
    }

    public function test_checker_flags_a_link_after_three_failed_nights_and_emails_once(): void
    {
        Mail::fake();
        [$good, $bad] = $this->item->links()->orderBy('sort_order')->get()->all();

        Http::fake([
            $good->url => Http::response('', 200),
            $bad->url => Http::response('', 404),
        ]);

        for ($night = 1; $night <= 3; $night++) {
            $this->artisan('links:check', ['--all' => true])->assertSuccessful();
        }

        $this->assertFalse($good->fresh()->is_broken);
        $this->assertSame(200, $good->fresh()->last_status_code);
        $this->assertTrue($bad->fresh()->is_broken);
        $this->assertSame(3, $bad->fresh()->failed_checks);
        Mail::assertQueued(TemplateMail::class, 1);

        // A fourth bad night doesn't send a second email.
        $this->artisan('links:check', ['--all' => true]);
        Mail::assertQueued(TemplateMail::class, 1);
    }

    public function test_blocked_checks_are_inconclusive_and_connection_errors_count(): void
    {
        [$blocked, $down] = $this->item->links()->orderBy('sort_order')->get()->all();

        Http::fake([
            $blocked->url => Http::response('', 403),
            $down->url => fn () => throw new ConnectionException('Could not resolve host'),
        ]);

        $this->artisan('links:check', ['--all' => true])->assertSuccessful();

        $this->assertNull($blocked->fresh()->last_checked_at, '403 should not count as a check');
        $this->assertSame(1, $down->fresh()->failed_checks);
        $this->assertNull($down->fresh()->last_status_code);
    }

    public function test_recently_checked_links_are_skipped_unless_asked(): void
    {
        Http::fake(['*' => Http::response('', 200)]);
        $this->item->links()->update(['last_checked_at' => now()->subHour()]);

        $this->artisan('links:check')->expectsOutput('No links due for a check.')->assertSuccessful();
        Http::assertNothingSent();
    }

    public function test_hosted_files_wait_for_a_clean_scan_before_download(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::disk('local')->put('downloads/tool.zip', 'zip-bytes');

        $link = ItemLink::create(['item_id' => $this->item->id, 'type' => 'hosted', 'file_path' => 'downloads/tool.zip', 'label' => 'Direct']);
        $this->assertSame('pending', $link->scan_status);
        Queue::assertPushed(ScanHostedFile::class);

        $go = URL::temporarySignedRoute('items.go', now()->addMinutes(5), ['item' => $this->item, 'link' => $link]);
        $this->get($go)->assertStatus(503);

        $this->fakeScanner(['status' => 'clean', 'detail' => null]);
        (new ScanHostedFile($link->id))->handle(app(VirusScanner::class));

        $this->assertSame('clean', $link->fresh()->scan_status);
        $this->get($go)->assertOk()->assertDownload('tool.zip');
    }

    public function test_infected_files_are_deleted_and_the_admin_is_told(): void
    {
        Queue::fake();
        Mail::fake();
        Storage::fake('local');
        Storage::disk('local')->put('downloads/bad.exe', 'X5O!P%@AP');
        $link = ItemLink::create(['item_id' => $this->item->id, 'type' => 'hosted', 'file_path' => 'downloads/bad.exe']);

        $this->fakeScanner(['status' => 'infected', 'detail' => 'Win.Test.EICAR_HDB-1 FOUND']);
        (new ScanHostedFile($link->id))->handle(app(VirusScanner::class));

        $link->refresh();
        $this->assertSame('infected', $link->scan_status);
        $this->assertFalse($link->is_active);
        Storage::disk('local')->assertMissing('downloads/bad.exe');
        Mail::assertQueued(TemplateMail::class, fn ($m) => $m->hasTo('owner@example.com') && str_contains($m->renderedBody, 'EICAR'));
    }

    public function test_servers_without_clamav_mark_files_unscanned_and_still_serve_them(): void
    {
        Queue::fake();
        Storage::fake('local');
        Storage::disk('local')->put('downloads/a.zip', 'x');
        $link = ItemLink::create(['item_id' => $this->item->id, 'type' => 'hosted', 'file_path' => 'downloads/a.zip']);

        $this->fakeScanner(null);
        (new ScanHostedFile($link->id))->handle(app(VirusScanner::class));

        $this->assertSame('unscanned', $link->fresh()->scan_status);
        $this->assertTrue($link->fresh()->isSafeToServe());
    }

    public function test_new_reports_and_contact_messages_alert_the_admin(): void
    {
        Mail::fake();

        $this->post("/download/{$this->item->slug}/report", ['type' => 'broken_link', 'message' => 'Down']);
        $this->post('/contact', ['name' => 'Ana', 'email' => 'ana@example.com', 'message' => 'Please add an app.']);

        Mail::assertQueued(TemplateMail::class, 2);
        Mail::assertQueued(TemplateMail::class, fn ($m) => str_contains($m->renderedSubject, 'Contact form: (no subject)'));
    }

    private function fakeScanner(?array $result): void
    {
        $this->app->instance(VirusScanner::class, new class($result) extends VirusScanner
        {
            public function __construct(private ?array $result) {}

            public function scan(string $absolutePath): ?array
            {
                return $this->result;
            }
        });
    }
}
