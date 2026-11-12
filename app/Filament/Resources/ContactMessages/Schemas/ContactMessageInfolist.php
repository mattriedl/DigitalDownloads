<?php

namespace App\Filament\Resources\ContactMessages\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactMessageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable()->url(fn ($record) => 'mailto:'.$record->email),
                TextEntry::make('created_at')->label('Received')->dateTime(),
                TextEntry::make('subject')->columnSpanFull()->placeholder('—'),
                TextEntry::make('message')->columnSpanFull()->prose(),
            ]),
        ]);
    }
}
