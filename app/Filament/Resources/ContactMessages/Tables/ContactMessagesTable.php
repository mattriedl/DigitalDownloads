<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')->label('')->boolean()
                    ->trueIcon(Heroicon::OutlinedEnvelopeOpen)->falseIcon(Heroicon::Envelope)
                    ->trueColor('gray')->falseColor('warning'),
                TextColumn::make('name')->searchable()->description(fn ($record) => $record->email),
                TextColumn::make('subject')->searchable()->limit(50)->placeholder('—'),
                TextColumn::make('message')->limit(70),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')->label('Read')->nullable(),
            ])
            ->recordActions([
                ViewAction::make()->after(fn ($record) => $record->read_at ?: $record->update(['read_at' => now()])),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('read')->label('Mark as read')->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->action(fn (Collection $records) => $records->each->update(['read_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }
}
