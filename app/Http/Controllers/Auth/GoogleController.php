<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/** "Continue with Google". Keys are set per site in Admin → Site settings → Integrations. */
class GoogleController extends Controller
{
    public function redirect(): SymfonyRedirect
    {
        $this->configure();

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $this->configure();

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'Google sign-in did not complete. Please try again.']);
        }

        if (! $google->getEmail()) {
            return redirect()->route('login')->withErrors(['email' => 'Your Google account did not share an email address.']);
        }

        $user = User::where('google_id', $google->getId())->first()
            ?? User::where('email', $google->getEmail())->first();

        if (! $user) {
            abort_unless(setting('registration_enabled', true), 403, 'Sign-ups are closed.');
            $user = new User;
            $user->forceFill([
                'name' => $google->getName() ?: Str::before($google->getEmail(), '@'),
                'email' => $google->getEmail(),
                'email_verified_at' => now(), // Google already verified it
            ]);
        }

        if ($user->isBanned()) {
            return redirect()->route('login')->withErrors(['email' => 'This account has been suspended. Contact us if you think this is a mistake.']);
        }

        $user->forceFill(['google_id' => $google->getId()]);
        if (! $user->avatar && $google->getAvatar()) {
            $user->avatar = $google->getAvatar();
        }
        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    private function configure(): void
    {
        abort_unless(setting('google_login_enabled') && setting('google_client_id') && setting('google_client_secret'), 404);

        config(['services.google' => [
            'client_id' => setting('google_client_id'),
            'client_secret' => setting('google_client_secret'),
            'redirect' => url('/auth/google/callback'),
        ]]);
    }
}
