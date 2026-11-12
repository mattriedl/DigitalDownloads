<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->modifyQueryUsing(fn ($query) => $query->withCount(['subscriptions as active_count' => fn ($s) => $s->whereIn('status', ['active', 'trialing'])]))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('price')->money(fn ($record) => $record->currency)
                    ->description(fn ($record) => $record->interval === 'year' ? 'per year' : 'per month'),
                TextColumn::make('active_count')->label('Active members'),
                ToggleColumn::make('is_active')->label('Offered'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
