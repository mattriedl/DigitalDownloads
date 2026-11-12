<?php

namespace App\Filament\Resources\ActivityLogs\Tables;

use App\Models\ActivityLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('M j, g:i a')->sortable(),
                TextColumn::make('user.name')->label('Who')->placeholder('System'),
                TextColumn::make('action')->badge()->color(fn ($state) => match ($state) {
                    'deleted' => 'danger', 'created' => 'success', default => 'gray',
                }),
                TextColumn::make('subject_type')->label('What')
                    ->formatStateUsing(fn ($state, ActivityLog $r) => class_basename($state).' #'.$r->subject_id),
                TextColumn::make('label')->label('Record')->state(fn (ActivityLog $r) => $r->properties['label'] ?? null)->limit(50),
            ])
            ->filters([
                SelectFilter::make('action')->options(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'settings' => 'Settings']),
                SelectFilter::make('user_id')->label('Who')->relationship('user', 'name', fn ($query) => $query->whereIn('role', ['admin', 'editor'])),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
