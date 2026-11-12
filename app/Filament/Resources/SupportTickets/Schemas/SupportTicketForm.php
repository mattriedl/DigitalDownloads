<?php

namespace App\Filament\Resources\SupportTickets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupportTicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('subject')->required()->maxLength(255)->columnSpanFull(),
            Select::make('status')->required()->options([
                'open' => 'Open', 'answered' => 'Answered', 'customer_reply' => 'Customer replied', 'closed' => 'Closed',
            ]),
            Select::make('priority')->required()->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High']),
            Select::make('assigned_to')->label('Assigned to')->placeholder('Nobody')
                ->relationship('assignee', 'name', fn ($query) => $query->whereIn('role', ['admin', 'editor'])),
        ]);
    }
}
