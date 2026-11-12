<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Settings;
use Database\Seeders\DefaultsSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Set up one site on one domain: database tables, defaults, site identity and the first admin.
 *
 * Interactive:      php artisan site:install
 * Non-interactive:  php artisan site:install --name="Free Apps" --url=https://example.com \
 *                       --admin-email=me@example.com --admin-password=... --no-interaction
 */
class InstallSite extends Command
{
    protected $signature = 'site:install
        {--name= : Site name shown in the header and titles}
        {--url= : Full site URL, e.g. https://example.com}
        {--email= : Public contact email for the site}
        {--admin-name= : First admin\'s display name}
        {--admin-email= : First admin\'s email (login)}
        {--admin-password= : First admin\'s password (min 12 characters)}
        {--demo : Also load sample categories and items (staging only)}
        {--force : Run even if the site is already installed}';

    protected $description = 'Install the download portal on this domain (tables, defaults, site identity, first admin)';

    public function handle(Settings $settings): int
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot connect to the database. Check DB_* values in .env first.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        if (empty(config('app.key'))) {
            $this->error('APP_KEY is missing. Run: php artisan key:generate');

            return self::FAILURE;
        }

        $alreadyInstalled = $settings->get('installed_at') !== null;
        if ($alreadyInstalled && ! $this->option('force')) {
            $this->warn('This site is already installed. Use --force to run the installer again.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: text('Site name', default: config('app.name'), required: true);
        $url = rtrim($this->option('url') ?: text('Site URL', default: config('app.url'), required: true), '/');
        $email = $this->option('email') ?: ($this->input->isInteractive() ? text('Public contact email (optional)') : null);
        $adminName = $this->option('admin-name') ?: ($this->input->isInteractive() ? text('Admin name', default: 'Admin') : 'Admin');
        $adminEmail = $this->option('admin-email') ?: text('Admin email', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
        $adminPassword = $this->option('admin-password') ?: password('Admin password (min 12 characters)', required: true);

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error("\"{$url}\" is not a valid URL.");

            return self::FAILURE;
        }
        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$adminEmail}\" is not a valid email.");

            return self::FAILURE;
        }
        if (Str::length($adminPassword) < 12) {
            $this->error('The admin password must be at least 12 characters.');

            return self::FAILURE;
        }

        $this->components->task('Creating database tables', fn () => Artisan::call('migrate', ['--force' => true]) === 0);
        $this->components->task('Adding default settings, ad zones and pages', fn () => $this->seed(DefaultsSeeder::class));

        $this->components->task('Saving site identity', function () use ($settings, $name, $url, $email) {
            $settings->set('site_name', $name, 'general');
            $settings->set('site_url', $url, 'general');
            if ($email) {
                $settings->set('site_email', $email, 'general');
            }

            return true;
        });

        $this->components->task('Creating the admin account', function () use ($adminName, $adminEmail, $adminPassword) {
            $admin = User::firstOrNew(['email' => $adminEmail]);
            $admin->name = $adminName;
            $admin->password = Hash::make($adminPassword);
            $admin->forceFill(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()])->save();

            return true;
        });

        if ($this->option('demo')) {
            $this->components->task('Loading demo content', fn () => $this->seed(DemoSeeder::class));
        }

        $this->components->task('Linking public storage', function () {
            if (! file_exists(public_path('storage'))) {
                Artisan::call('storage:link');
            }

            return true;
        });

        if (! $alreadyInstalled) {
            $settings->set('installed_at', now()->toIso8601String(), 'system');
        }

        $this->newLine();
        $this->components->info("{$name} is installed.");
        $this->line("  Site:  {$url}");
        $this->line("  Admin: {$url}/admin  (sign in as {$adminEmail})");

        return self::SUCCESS;
    }

    private function seed(string $class): bool
    {
        return Artisan::call('db:seed', ['--class' => $class, '--force' => true]) === 0;
    }
}
