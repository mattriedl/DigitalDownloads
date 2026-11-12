<?php

namespace App\Filament\Resources\PaymentGateways\Tables;

use App\Models\PaymentGateway;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentGatewaysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name'),
                IconColumn::make('is_enabled')->label('Enabled')->boolean(),
                TextColumn::make('mode')->badge()->color(fn ($state) => $state === 'live' ? 'success' : 'warning'),
                TextColumn::make('keys')->label('Keys')
                    ->state(fn (PaymentGateway $g) => collect($g->credentials ?? [])->filter()->isNotEmpty() ? 'Saved' : 'Not set')
                    ->badge()->color(fn ($state) => $state === 'Saved' ? 'success' : 'gray'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
