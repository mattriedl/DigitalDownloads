<?php

namespace App\Filament\Resources\SupportTickets\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'assignee']))
            ->recordUrl(fn ($record) => route('filament.admin.resources.support-tickets.view', $record))
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('subject')->searchable()->limit(60)->description(fn ($record) => $record->user?->name),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title())
                    ->color(fn ($state) => match ($state) {
                        'closed' => 'gray', 'answered' => 'success', default => 'warning',
                    }),
                TextColumn::make('priority')->badge()->color(fn ($state) => $state === 'high' ? 'danger' : 'gray'),
                TextColumn::make('assignee.name')->label('Assigned')->placeholder('—'),
                TextColumn::make('last_reply_at')->label('Last reply')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->multiple()->options([
                    'open' => 'Open', 'answered' => 'Answered', 'customer_reply' => 'Customer replied', 'closed' => 'Closed',
                ])->default(['open', 'customer_reply']),
                SelectFilter::make('priority')->options(['low' => 'Low', 'normal' => 'Normal', 'high' => 'High']),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }
}
