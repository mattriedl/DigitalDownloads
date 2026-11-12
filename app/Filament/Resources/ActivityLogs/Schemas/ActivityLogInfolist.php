<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(4)->schema([
                TextEntry::make('created_at')->label('When')->dateTime(),
                TextEntry::make('user.name')->label('Who')->placeholder('System'),
                TextEntry::make('action')->badge(),
                TextEntry::make('subject_type')->label('What')->formatStateUsing(fn ($state, $record) => class_basename($state).' #'.$record->subject_id),
                KeyValueEntry::make('properties.changes')->label('Changed fields (new values)')->columnSpanFull(),
            ]),
        ]);
    }
}
