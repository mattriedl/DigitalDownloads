<?php

namespace App\Support;

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

    /** Alerts for the site owner, sent to "Send admin alerts to" in Site settings. */
    public static function admin(string $templateKey, array $vars = []): void
    {
        static::send($templateKey, setting('admin_notify_email') ?: setting('site_email'), $vars);
    }
}
