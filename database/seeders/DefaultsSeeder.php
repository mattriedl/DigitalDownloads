<?php

namespace Database\Seeders;

use App\Models\AdZone;
use App\Models\MenuItem;
use App\Models\NotificationTemplate;
use App\Models\Page;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Database\Seeder;

/**
 * Everything a brand-new site needs: ad zones, default settings, payment gateways (off),
 * notification templates and starter policy pages.
 *
 * Safe to run again on an existing site: it only adds what is missing and never
 * overwrites anything an admin has changed.
 */
class DefaultsSeeder extends Seeder
{
    public function run(): void
    {
        $this->adZones();
        $this->settings();
        $this->gateways();
        $this->templates();
        $this->pages();
        $this->menus();
    }

    /** Ad slots the dark, ad-heavy theme renders. Width/height are the standard IAB sizes. */
    private function adZones(): void
    {
        $zones = [
            ['header_leaderboard', 'Header leaderboard', 'Top of every page, desktop', 728, 90],
            ['header_mobile', 'Header mobile banner', 'Top of every page, phones', 320, 50],
            ['home_between_sections', 'Homepage between sections', 'Between homepage item rows', 728, 90],
            ['category_top', 'Category page top', 'Above the item grid on category and search pages', 728, 90],
            ['sidebar_top', 'Sidebar top', 'First sidebar slot on every page', 300, 250],
            ['sidebar_middle', 'Sidebar skyscraper', 'Tall sidebar slot', 300, 600],
            ['sidebar_bottom', 'Sidebar bottom', 'Last sidebar slot', 300, 250],
            ['item_above_details', 'Item page above details', 'Above the item description', 728, 90],
            ['item_in_description', 'Item page in description', 'Inside the item description', 300, 250],
            ['item_below_download', 'Item page below download button', 'Right under the download button', 336, 280],
            ['download_countdown_top', 'Download page top', 'Top of the countdown page', 728, 90],
            ['download_countdown_main', 'Download page main', 'Beside the countdown timer', 336, 280],
            ['download_countdown_bottom', 'Download page bottom', 'Under the countdown timer', 728, 90],
            ['blog_in_article', 'Blog in-article', 'Inside blog posts', 336, 280],
            ['footer_leaderboard', 'Footer leaderboard', 'Above the footer', 728, 90],
            ['sticky_mobile_footer', 'Sticky mobile footer', 'Pinned to the bottom on phones', 320, 50],
        ];

        foreach ($zones as [$key, $name, $description, $w, $h]) {
            AdZone::firstOrCreate(['key' => $key], [
                'name' => $name,
                'description' => $description,
                'width' => $w,
                'height' => $h,
            ]);
        }
    }

    private function settings(): void
    {
        $defaults = [
            'general' => [
                'site_name' => config('app.name', 'Downloads'),
                'site_tagline' => 'Free downloads, updated daily',
                'site_url' => config('app.url'),
                'site_email' => null,
                'timezone' => 'America/Los_Angeles',
                'items_per_page' => 24,
                'registration_enabled' => true,
                'maintenance_message' => 'We are making improvements and will be back shortly.',
            ],
            'branding' => [
                'logo' => null,
                'logo_dark' => null,
                'favicon' => null,
                'theme' => 'dark', // dark, light, auto
                'primary_color' => '#22c55e',
                'accent_color' => '#f59e0b',
                'footer_text' => null,
                'social_links' => [],
            ],
            'seo' => [
                'seo_home_title' => null,
                'seo_home_description' => null,
                'seo_title_suffix' => null, // e.g. " | Site Name"; empty = site name
                'og_image' => null,
                'google_analytics_id' => null,
                'google_site_verification' => null,
                'bing_site_verification' => null,
                'indexnow_key' => bin2hex(random_bytes(16)),
                'robots_txt_extra' => null,
            ],
            'ads' => [
                'ads_enabled' => true,
                'countdown_enabled' => true,
                'countdown_seconds' => 10,
                'adsense_publisher_id' => null,
                'ads_txt' => null,
                'consent_banner_enabled' => true,
                'consent_cmp_code' => null, // Google-certified CMP snippet for EU/UK visitors
            ],
            'community' => [
                'reviews_enabled' => true,
                'reviews_require_approval' => true,
                'comments_enabled' => true,
                'comments_require_approval' => true,
            ],
            'integrations' => [
                'captcha_provider' => 'turnstile', // turnstile, recaptcha, none
                'captcha_site_key' => null,
                'live_chat_code' => null,
                'head_code' => null,
                'body_end_code' => null,
                'custom_css' => null,
            ],
            'notifications' => [
                'mail_from_name' => null,
                'mail_from_address' => null,
                'admin_notify_email' => null,
                'sms_provider' => null,
            ],
            'payments' => [
                'memberships_enabled' => false,
                'currency' => 'USD',
            ],
        ];

        $settings = app(Settings::class);
        $existing = Setting::pluck('key')->flip();

        foreach ($defaults as $group => $values) {
            foreach ($values as $key => $value) {
                if (! $existing->has($key)) {
                    $settings->set($key, $value, $group);
                }
            }
        }

        // Secret keys are stored encrypted.
        foreach (['captcha_secret_key', 'sms_api_key'] as $key) {
            if (! $existing->has($key)) {
                $settings->set($key, null, 'integrations', encrypted: true);
            }
        }
    }

    /** Built in on every site, switched off until memberships launch. */
    private function gateways(): void
    {
        $gateways = [
            ['stripe', 'Stripe (cards, Apple Pay, Google Pay)', ['publishable_key' => null, 'secret_key' => null, 'webhook_secret' => null]],
            ['paypal', 'PayPal', ['client_id' => null, 'client_secret' => null, 'webhook_id' => null]],
            ['nowpayments', 'Crypto (NOWPayments)', ['api_key' => null, 'ipn_secret' => null]],
        ];

        foreach ($gateways as $i => [$code, $name, $credentials]) {
            PaymentGateway::firstOrCreate(['code' => $code], [
                'name' => $name,
                'is_enabled' => false,
                'mode' => 'test',
                'credentials' => $credentials,
                'currencies' => ['USD'],
                'sort_order' => $i,
            ]);
        }
    }

    private function templates(): void
    {
        $templates = [
            ['welcome', 'Welcome email', 'Welcome to {{site_name}}', "Hi {{name}},\n\nThanks for joining {{site_name}}. Save your favorite downloads and get updates when they change.\n\n{{site_url}}", ['name', 'site_name', 'site_url']],
            ['ticket_reply', 'Support ticket reply', 'Re: {{subject}}', "Hi {{name}},\n\nWe replied to your support ticket \"{{subject}}\":\n\n{{message}}\n\nView it here: {{ticket_url}}", ['name', 'subject', 'message', 'ticket_url']],
            ['admin_new_ticket', 'Admin: new support ticket', 'New ticket: {{subject}}', "{{name}} opened a ticket:\n\n{{message}}\n\n{{ticket_url}}", ['name', 'subject', 'message', 'ticket_url']],
            ['admin_new_report', 'Admin: new report', 'New {{type}} report on {{item_title}}', "A visitor reported \"{{item_title}}\" ({{type}}):\n\n{{message}}\n\n{{admin_url}}", ['type', 'item_title', 'message', 'admin_url']],
            ['admin_contact_message', 'Admin: contact form message', 'Contact form: {{subject}}', "From {{name}} <{{email}}>:\n\n{{message}}", ['name', 'email', 'subject', 'message']],
            ['admin_broken_links', 'Admin: broken links found', '{{count}} broken download links', "The nightly link check flagged {{count}} links as broken.\n\n{{admin_url}}", ['count', 'admin_url']],
            ['subscription_started', 'Membership started', 'Your {{plan}} membership is active', "Hi {{name}},\n\nYour {{plan}} membership is active. Enjoy ad-free downloads.\n\nNext billing date: {{renews_at}}", ['name', 'plan', 'renews_at']],
            ['subscription_canceled', 'Membership canceled', 'Your {{plan}} membership was canceled', "Hi {{name}},\n\nYour {{plan}} membership was canceled. Member perks stay on until {{ends_at}}.", ['name', 'plan', 'ends_at']],
            ['payment_receipt', 'Payment receipt', 'Receipt for {{amount}}', "Hi {{name}},\n\nWe received your payment of {{amount}} for {{plan}} on {{date}}.\n\nReference: {{reference}}", ['name', 'amount', 'plan', 'date', 'reference']],
        ];

        foreach ($templates as [$key, $name, $subject, $body, $vars]) {
            NotificationTemplate::firstOrCreate(['key' => $key], [
                'name' => $name,
                'subject' => $subject,
                'email_body' => $body,
                'variables' => $vars,
            ]);
        }
    }

    /** Starter policy pages. Admins should review and edit these for each site. */
    private function pages(): void
    {
        $pages = [
            ['About Us', 'about', 'page', '<p>Tell visitors what this site offers and who runs it.</p>'],
            ['Privacy Policy', 'privacy-policy', 'policy', '<p>Describe what data the site collects (accounts, cookies, analytics, advertising) and how visitors can contact you or delete their data.</p>'],
            ['Terms of Service', 'terms-of-service', 'policy', '<p>Set the rules for using the site and its downloads.</p>'],
            ['DMCA Policy', 'dmca', 'policy', '<p>Explain how copyright owners can send a takedown notice, which details to include, and your designated agent contact. Notices can also be sent through the Report button on any item.</p>'],
            ['Cookie Policy', 'cookie-policy', 'policy', '<p>List the cookies used for sign-in, analytics and advertising, and how visitors can change their consent.</p>'],
        ];

        foreach ($pages as $i => [$title, $slug, $type, $body]) {
            Page::firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'type' => $type,
                'body' => $body,
                'show_in_footer' => true,
                'sort_order' => $i,
            ]);
        }
    }

    private function menus(): void
    {
        if (MenuItem::exists()) {
            return;
        }

        foreach ([['Home', '/'], ['Categories', '/categories'], ['Popular', '/popular'], ['New', '/latest'], ['Blog', '/blog']] as $i => [$label, $url]) {
            MenuItem::create(['menu' => 'header', 'label' => $label, 'url' => $url, 'sort_order' => $i]);
        }
        MenuItem::create(['menu' => 'footer', 'label' => 'Contact', 'url' => '/contact', 'sort_order' => 0]);
    }
}
