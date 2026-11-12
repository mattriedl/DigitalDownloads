<?php

namespace App\Rules;

use App\Support\Captcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Fails when the spam-check widget's token is missing or rejected. */
class CaptchaPassed implements ValidationRule
{
    /** Run even when the field is missing, so skipping the widget doesn't skip the check. */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Captcha::verify(is_string($value) ? $value : null, request()->ip())) {
            $fail('Please confirm you are not a robot.');
        }
    }
}
