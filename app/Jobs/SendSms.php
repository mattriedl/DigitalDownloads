<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends one text message through Twilio. Credentials come from Site settings:
 * "SMS API key" holds AccountSID:AuthToken, "Send SMS from" holds the Twilio number.
 */
class SendSms implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $to, public string $body) {}

    public function handle(): void
    {
        $credentials = (string) setting('sms_api_key');
        $from = (string) setting('sms_from');

        if (setting('sms_provider') !== 'twilio' || ! str_contains($credentials, ':') || $from === '') {
            Log::info('SMS not sent: Twilio is not fully set up in Site settings.');

            return;
        }

        [$sid, $token] = explode(':', $credentials, 2);

        $response = Http::asForm()
            ->withBasicAuth($sid, $token)
            ->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'To' => $this->to,
                'From' => $from,
                'Body' => Str::limit($this->body, 320),
            ]);

        if ($response->failed()) {
            Log::warning('Twilio SMS failed: '.$response->json('message', $response->status()));
            $response->throw();
        }
    }
}
