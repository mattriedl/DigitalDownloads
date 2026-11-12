<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

/**
 * The same SEO fields on items, categories, pages and posts, with the house rules built in:
 * title under 70 characters, description 150-160, and no years (keeps content evergreen).
 */
class SeoFields
{
    public static function section(string $titleField = 'title', ?string $descriptionField = null): Section
    {
        return Section::make('Search engine listing')
            ->description('Leave blank to use the title and short description. Do not put years in these fields.')
            ->collapsible()
            ->collapsed()
            ->schema([
                TextInput::make('seo_title')
                    ->label('SEO title')
                    ->maxLength(70)
                    ->live(debounce: 400)
                    ->helperText(fn (?string $state) => Str::length((string) $state).'/70 characters')
                    ->rule(static::noYearsRule()),
                Textarea::make('seo_description')
                    ->label('Meta description')
                    ->rows(3)
                    ->maxLength(160)
                    ->live(debounce: 400)
                    ->helperText(function (?string $state) {
                        $n = Str::length((string) $state);

                        return $n === 0 ? 'Aim for 150-160 characters.' : "{$n}/160 characters".($n < 150 ? ' (aim for 150-160)' : '');
                    })
                    ->rule(static::noYearsRule()),
            ]);
    }

    /** Reject 4-digit years like 2019 or 2026 so titles stay evergreen. */
    public static function noYearsRule(): \Closure
    {
        return fn () => function (string $attribute, mixed $value, \Closure $fail) {
            if (is_string($value) && preg_match('/\b(19|20)\d{2}\b/', $value)) {
                $fail('Leave years out so the listing stays evergreen.');
            }
        };
    }
}
