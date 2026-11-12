<?php

namespace App\Filament\Resources\NotificationTemplates\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class NotificationTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->description(fn ($record) => $record->subject),
                ToggleColumn::make('email_enabled')->label('Email'),
                ToggleColumn::make('sms_enabled')->label('SMS'),
            ])
            ->recordActions([EditAction::make()]);
    }
}
