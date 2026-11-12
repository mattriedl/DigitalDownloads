<?php

namespace App\Support;

use App\Jobs\SendSms;
use App\Mail\TemplateMail;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends emails from the admin-editable templates. Emails are queued so pages stay fast,
 * and a mail failure never breaks the action that triggered it.
 */
class Notifier
{
    public static function send(string $templateKey, ?string $email, array $vars = []): void
    {
        if (! $email) {
            return;
        }

        $template = NotificationTemplate::where('key', $templateKey)->first();
        if (! $template || ! $template->email_enabled) {
            return;
        }

        try {
            Mail::to($email)->queue(new TemplateMail($template, $vars));
        } catch (Throwable $e) {
            Log::warning("Could not send {$templateKey} email: ".$e->getMessage());
        }
    }

    /**
     * Alerts for the site owner: email to "Send admin alerts to", plus a text message to
     * "Text admin alerts to" when the template has SMS switched on and Twilio is set up.
     */
    public static function admin(string $templateKey, array $vars = []): void
    {
        static::send($templateKey, setting('admin_notify_email') ?: setting('site_email'), $vars);
        static::sms($templateKey, setting('admin_notify_phone'), $vars);
    }

    public static function sms(string $templateKey, ?string $phone, array $vars = []): void
    {
        if (! $phone || setting('sms_provider') !== 'twilio') {
            return;
        }

        $template = NotificationTemplate::where('key', $templateKey)->first();
        if (! $template || ! $template->sms_enabled || blank($template->sms_body)) {
            return;
        }

        $vars += ['site_name' => setting('site_name', config('app.name'))];

        try {
            SendSms::dispatch($phone, NotificationTemplate::render($template->sms_body, $vars));
        } catch (Throwable $e) {
            Log::warning("Could not queue {$templateKey} SMS: ".$e->getMessage());
        }
    }
}
