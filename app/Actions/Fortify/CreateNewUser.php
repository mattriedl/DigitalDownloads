<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\Captcha;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        // Registration can be closed per site in Site settings.
        abort_unless(setting('registration_enabled', true), 403, 'Sign-ups are closed.');

        // At most 5 new accounts per IP address per hour.
        $key = 'register:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Too many sign-ups from your network. Please try again in an hour.']);
        }
        RateLimiter::hit($key, 3600);

        // Bots fill the hidden field; people never see it.
        if (filled($input['website'] ?? null)) {
            throw ValidationException::withMessages(['email' => 'Sign-up failed. Please try again.']);
        }

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'terms' => ['accepted'],
            ...Captcha::rules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);
    }
}
