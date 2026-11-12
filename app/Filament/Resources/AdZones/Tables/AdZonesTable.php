<?php

namespace App\Filament\Resources\AdZones\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class AdZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'ads',
                'ads as running_ads_count' => fn ($a) => $a->running(),
            ]))
            ->columns([
                TextColumn::make('name')->searchable()->description(fn ($record) => $record->description),
                TextColumn::make('key')->color('gray')->fontFamily('mono')->copyable(),
                TextColumn::make('size')->state(fn ($record) => $record->width && $record->height ? "{$record->width}×{$record->height}" : '—'),
                TextColumn::make('running_ads_count')->label('Running ads')->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
