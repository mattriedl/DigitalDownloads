<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupportTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columnSpanFull()->columns(5)->schema([
                TextEntry::make('subject')->columnSpan(2)->weight('bold'),
                TextEntry::make('user.name')->label('From')->helperText(fn ($record) => $record->user?->email),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title())
                    ->color(fn ($state) => match ($state) {
                        'closed' => 'gray', 'answered' => 'success', default => 'warning',
                    }),
                TextEntry::make('priority')->badge()->color(fn ($state) => $state === 'high' ? 'danger' : 'gray'),
                TextEntry::make('created_at')->label('Opened')->dateTime(),
                TextEntry::make('last_reply_at')->label('Last reply')->since(),
                TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Nobody'),
            ]),
        ]);
    }
}
