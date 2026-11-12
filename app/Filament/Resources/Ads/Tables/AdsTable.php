<?php

namespace App\Filament\Resources\Ads\Tables;

use App\Models\Ad;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['zone', 'category']))
            ->columns([
                TextColumn::make('name')->searchable()->description(fn (Ad $ad) => $ad->category ? 'Only in '.$ad->category->name : null),
                TextColumn::make('zone.name')->label('Zone')->sortable(),
                TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => $state === Ad::TYPE_CODE ? 'Code' : 'Image'),
                TextColumn::make('impressions')->numeric()->sortable(),
                TextColumn::make('clicks')->numeric()->sortable(),
                TextColumn::make('ctr')->label('CTR')->state(fn (Ad $ad) => $ad->ctr().'%'),
                TextColumn::make('ends_at')->label('Ends')->dateTime('M j, Y')->placeholder('Never')->sortable(),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([
                SelectFilter::make('ad_zone_id')->label('Zone')->relationship('zone', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()->label('Duplicate')
                    ->mutateRecordDataUsing(fn (array $data) => [...$data, 'name' => $data['name'].' (copy)', 'is_active' => false])
                    ->excludeAttributes(['impressions', 'clicks']),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
