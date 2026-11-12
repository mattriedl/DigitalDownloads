<?php

namespace App\Filament\Resources\PaymentGateways\Schemas;

use App\Models\PaymentGateway;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PaymentGatewayForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('API keys')
                ->columnSpan(2)
                ->description('Copy these from the gateway dashboard. Use test keys until memberships go live.')
                ->schema(fn (?PaymentGateway $record) => collect($record?->credentials ?? [])
                    ->keys()
                    ->map(fn (string $key) => TextInput::make("credentials.{$key}")
                        ->label(Str::of($key)->replace('_', ' ')->title())
                        ->password(self::isSecret($key))
                        ->revealable(self::isSecret($key))
                        ->maxLength(500))
                    ->all()),
            Section::make('Status')->columnSpan(1)->schema([
                TextInput::make('name')->required()->maxLength(255),
                Toggle::make('is_enabled')->label('Enabled')
                    ->helperText('Visitors only see gateways once memberships are switched on in Settings.'),
                ToggleButtons::make('mode')->inline()->required()
                    ->options(['test' => 'Test', 'live' => 'Live'])
                    ->colors(['test' => 'warning', 'live' => 'success']),
                TagsInput::make('currencies')->placeholder('USD'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    private static function isSecret(string $key): bool
    {
        return Str::contains($key, ['secret', 'api_key']);
    }
}
