<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount(['downloads', 'favorites']))
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->description(fn (User $u) => $u->email),
                TextColumn::make('email')->searchable()->hidden(),
                TextColumn::make('role')->badge()->color(fn ($state) => match ($state) {
                    User::ROLE_ADMIN => 'danger', User::ROLE_EDITOR => 'warning', default => 'gray',
                }),
                TextColumn::make('status')->state(fn (User $u) => $u->isBanned() ? 'Banned' : ($u->email_verified_at ? 'Active' : 'Unverified'))
                    ->badge()->color(fn ($state) => match ($state) {
                        'Banned' => 'danger', 'Active' => 'success', default => 'gray',
                    }),
                TextColumn::make('downloads_count')->label('Downloads')->sortable(),
                TextColumn::make('favorites_count')->label('Favorites')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_login_at')->since()->sortable()->placeholder('Never'),
                TextColumn::make('created_at')->label('Joined')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->options([User::ROLE_USER => 'User', User::ROLE_EDITOR => 'Editor', User::ROLE_ADMIN => 'Admin']),
                TernaryFilter::make('banned_at')->label('Banned')->nullable(),
                TernaryFilter::make('email_verified_at')->label('Verified')->nullable(),
            ])
            ->recordActions([
                Action::make('ban')->icon(Heroicon::OutlinedNoSymbol)->color('danger')->requiresConfirmation()
                    ->visible(fn (User $u) => ! $u->isBanned() && ! $u->is(auth()->user()))
                    ->action(fn (User $u) => $u->forceFill(['banned_at' => now()])->save()),
                Action::make('unban')->icon(Heroicon::OutlinedCheckCircle)->color('success')
                    ->visible(fn (User $u) => $u->isBanned())
                    ->action(fn (User $u) => $u->forceFill(['banned_at' => null])->save()),
                EditAction::make(),
            ]);
    }
}
