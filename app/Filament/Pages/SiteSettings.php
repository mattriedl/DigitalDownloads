<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\AdminOnly;
use App\Models\ActivityLog;
use App\Support\Settings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * Everything that makes one site different from the others: name, branding, SEO defaults,
 * ad and countdown options, integrations. Values live in this site's own `settings` table.
 */
class SiteSettings extends Page
{
    use AdminOnly;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Site settings';

    protected static ?string $title = 'Site settings';

    protected static ?string $slug = 'settings';

    /** Which group each field belongs to, and which are stored encrypted. */
    private const FIELDS = [
        'general' => ['site_name', 'site_tagline', 'site_url', 'site_email', 'timezone', 'items_per_page', 'registration_enabled', 'maintenance_message'],
        'branding' => ['logo', 'logo_dark', 'favicon', 'theme', 'primary_color', 'accent_color', 'footer_text', 'social_links'],
        'seo' => ['seo_home_title', 'seo_home_description', 'seo_title_suffix', 'og_image', 'google_analytics_id', 'google_site_verification', 'bing_site_verification', 'robots_txt_extra'],
        'ads' => ['ads_enabled', 'countdown_enabled', 'countdown_seconds', 'adsense_publisher_id', 'ads_txt', 'consent_banner_enabled', 'consent_cmp_code'],
        'community' => ['reviews_enabled', 'reviews_require_approval', 'comments_enabled', 'comments_require_approval'],
        'integrations' => ['captcha_provider', 'captcha_site_key', 'captcha_secret_key', 'live_chat_code', 'head_code', 'body_end_code', 'custom_css'],
        'notifications' => ['mail_from_name', 'mail_from_address', 'admin_notify_email', 'sms_provider', 'sms_api_key'],
        'payments' => ['memberships_enabled', 'currency'],
    ];

    private const ENCRYPTED = ['captcha_secret_key', 'sms_api_key'];

    public ?array $data = [];

    public function mount(Settings $settings): void
    {
        $values = [];
        foreach (self::FIELDS as $keys) {
            foreach ($keys as $key) {
                $values[$key] = $settings->get($key);
            }
        }
        // Secrets are never sent to the browser; a blank field keeps the saved value.
        foreach (self::ENCRYPTED as $key) {
            $values[$key] = null;
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Tabs::make('settings')->persistTabInQueryString()->tabs([
                Tab::make('General')->icon(Heroicon::OutlinedHome)->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('site_name')->required()->maxLength(60),
                        TextInput::make('site_tagline')->maxLength(120),
                        TextInput::make('site_url')->label('Site URL')->url()->required(),
                        TextInput::make('site_email')->label('Public contact email')->email(),
                        Select::make('timezone')->searchable()->options(fn () => array_combine(timezone_identifiers_list(), timezone_identifiers_list())),
                        TextInput::make('items_per_page')->numeric()->minValue(6)->maxValue(100),
                        Toggle::make('registration_enabled')->label('Allow new accounts'),
                        Textarea::make('maintenance_message')->rows(2)->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Branding')->icon(Heroicon::OutlinedSwatch)->schema([
                    Section::make()->columns(3)->schema([
                        FileUpload::make('logo')->image()->disk('public')->directory('branding')->maxSize(2048),
                        FileUpload::make('logo_dark')->label('Logo for dark backgrounds')->image()->disk('public')->directory('branding')->maxSize(2048),
                        FileUpload::make('favicon')->image()->disk('public')->directory('branding')->maxSize(512),
                        ToggleButtons::make('theme')->inline()->options(['dark' => 'Dark', 'light' => 'Light', 'auto' => 'Match visitor'])->required(),
                        ColorPicker::make('primary_color')->required(),
                        ColorPicker::make('accent_color')->required(),
                        Textarea::make('footer_text')->rows(2)->columnSpanFull(),
                        KeyValue::make('social_links')->keyLabel('Network')->valueLabel('URL')
                            ->keyPlaceholder('facebook')->valuePlaceholder('https://...')->columnSpanFull(),
                    ]),
                ]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    Section::make('Homepage listing')->columns(2)->schema([
                        TextInput::make('seo_home_title')->label('Homepage SEO title')->maxLength(70)
                            ->helperText('Under 70 characters, no years.'),
                        TextInput::make('seo_title_suffix')->label('Title suffix')->placeholder(' | Site name')
                            ->helperText('Added to every page title. Blank uses " | site name".'),
                        Textarea::make('seo_home_description')->label('Homepage meta description')->rows(2)->maxLength(160)
                            ->helperText('150-160 characters.')->columnSpanFull(),
                        FileUpload::make('og_image')->label('Default social share image')->image()->disk('public')->directory('branding')
                            ->helperText('1200×630 works best.'),
                    ]),
                    Section::make('Search engines and analytics')->columns(3)->schema([
                        TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXX'),
                        TextInput::make('google_site_verification')->label('Google Search Console code'),
                        TextInput::make('bing_site_verification')->label('Bing Webmaster code'),
                        Textarea::make('robots_txt_extra')->label('Extra robots.txt rules')->rows(3)->columnSpanFull(),
                    ]),
                ]),
                Tab::make('Ads & downloads')->icon(Heroicon::OutlinedMegaphone)->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('ads_enabled')->label('Show ads on the site'),
                        Toggle::make('countdown_enabled')->label('Show a countdown page before each download')->live(),
                        TextInput::make('countdown_seconds')->numeric()->minValue(0)->maxValue(60)->suffix('seconds')
                            ->visible(fn (Get $get) => $get('countdown_enabled')),
                        TextInput::make('adsense_publisher_id')->label('AdSense publisher ID')->placeholder('pub-0000000000000000'),
                        Textarea::make('ads_txt')->label('ads.txt')->rows(4)->columnSpanFull()
                            ->helperText('Served at /ads.txt. AdSense needs a line like: google.com, pub-XXXX, DIRECT, f08c47fec0942fa0'),
                        Toggle::make('consent_banner_enabled')->label('Show the cookie consent banner'),
                        Textarea::make('consent_cmp_code')->label('Consent management (CMP) code')->rows(4)->columnSpanFull()
                            ->helperText('AdSense requires a Google-certified CMP for visitors in the EU, UK and Switzerland. Paste its snippet here.'),
                    ]),
                ]),
                Tab::make('Reviews & comments')->icon(Heroicon::OutlinedChatBubbleLeftRight)->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('reviews_enabled')->label('Allow reviews'),
                        Toggle::make('reviews_require_approval')->label('Hold reviews for approval'),
                        Toggle::make('comments_enabled')->label('Allow comments'),
                        Toggle::make('comments_require_approval')->label('Hold comments for approval'),
                    ]),
                ]),
                Tab::make('Integrations')->icon(Heroicon::OutlinedPuzzlePiece)->schema([
                    Section::make('Spam protection')->columns(3)->schema([
                        Select::make('captcha_provider')->options(['turnstile' => 'Cloudflare Turnstile', 'recaptcha' => 'Google reCAPTCHA', 'none' => 'None']),
                        TextInput::make('captcha_site_key'),
                        TextInput::make('captcha_secret_key')->password()->revealable()
                            ->placeholder(fn () => setting('captcha_secret_key') ? 'Saved (leave blank to keep)' : null),
                    ]),
                    Section::make('Custom code')->schema([
                        Textarea::make('live_chat_code')->label('Live chat widget code')->rows(3),
                        Textarea::make('head_code')->label('Code in <head>')->rows(3),
                        Textarea::make('body_end_code')->label('Code before </body>')->rows(3),
                        Textarea::make('custom_css')->label('Custom CSS')->rows(5),
                    ]),
                ]),
                Tab::make('Email & SMS')->icon(Heroicon::OutlinedEnvelope)->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('mail_from_name')->label('Send emails as (name)'),
                        TextInput::make('mail_from_address')->label('Send emails from')->email(),
                        TextInput::make('admin_notify_email')->label('Send admin alerts to')->email()
                            ->helperText('New tickets, reports, contact messages and broken links.'),
                        Select::make('sms_provider')->placeholder('None')->options(['twilio' => 'Twilio', 'vonage' => 'Vonage']),
                        TextInput::make('sms_api_key')->label('SMS API key')->password()->revealable()
                            ->placeholder(fn () => setting('sms_api_key') ? 'Saved (leave blank to keep)' : null),
                    ]),
                ]),
                Tab::make('Memberships')->icon(Heroicon::OutlinedCreditCard)->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('memberships_enabled')->label('Offer paid memberships')
                            ->helperText('Shows plans and checkout on the site. Set up plans and a payment gateway first.'),
                        TextInput::make('currency')->maxLength(3),
                    ]),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save settings')->submit('save')->keyBindings(['mod+s']),
                    ])->sticky(),
                ]),
        ]);
    }

    public function save(Settings $settings): void
    {
        $data = $this->form->getState();
        $changed = [];

        foreach (self::FIELDS as $group => $keys) {
            foreach ($keys as $key) {
                $value = $data[$key] ?? null;
                $encrypted = in_array($key, self::ENCRYPTED, true);

                if ($encrypted && blank($value)) {
                    continue; // keep the saved secret
                }
                if ($key === 'site_url' && is_string($value)) {
                    $value = rtrim($value, '/');
                }
                if (! $encrypted && $settings->get($key) === $value) {
                    continue;
                }

                $settings->set($key, $value, $group, $encrypted);
                $changed[$key] = $encrypted ? '(secret changed)' : Str::limit(is_scalar($value) ? (string) $value : json_encode($value), 100);
            }
        }

        if ($changed) {
            ActivityLog::record('settings', null, ['label' => 'Site settings', 'changes' => $changed]);
        }

        Notification::make()->success()->title($changed ? 'Settings saved' : 'Nothing changed')->send();
    }
}
