<?php

namespace App\Support;

use App\Rules\CaptchaPassed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cloudflare Turnstile or Google reCAPTCHA (v2 checkbox) on public forms.
 * Switched on per site by choosing a provider and entering both keys in Site settings.
 */
class Captcha
{
    private const PROVIDERS = [
        'turnstile' => [
            'script' => 'https://challenges.cloudflare.com/turnstile/v0/api.js',
            'class' => 'cf-turnstile',
            'field' => 'cf-turnstile-response',
            'verify' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ],
        'recaptcha' => [
            'script' => 'https://www.google.com/recaptcha/api.js',
            'class' => 'g-recaptcha',
            'field' => 'g-recaptcha-response',
            'verify' => 'https://www.google.com/recaptcha/api/siteverify',
        ],
    ];

    public static function enabled(): bool
    {
        return isset(self::PROVIDERS[setting('captcha_provider')])
            && filled(setting('captcha_site_key'))
            && filled(setting('captcha_secret_key'));
    }

    public static function provider(): array
    {
        return self::PROVIDERS[setting('captcha_provider')] ?? self::PROVIDERS['turnstile'];
    }

    /** Check the token the widget added to the form. */
    public static function verify(?string $token, ?string $ip = null): bool
    {
        if (! static::enabled()) {
            return true;
        }
        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(10)->post(static::provider()['verify'], array_filter([
                'secret' => setting('captcha_secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));

            return (bool) $response->json('success');
        } catch (Throwable $e) {
            // If the captcha service itself is down, let people through rather than lock everyone out.
            Log::warning('Captcha check failed: '.$e->getMessage());

            return true;
        }
    }

    /** Validation rules to merge into a form's rules: [field => rules], empty when off. */
    public static function rules(): array
    {
        return static::enabled() ? [static::provider()['field'] => [new CaptchaPassed]] : [];
    }
}
