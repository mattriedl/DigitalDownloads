<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                TextInput::make('phone')->tel()->maxLength(32),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->minLength(12)
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state) => Hash::make($state))
                    ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep the current password.' : 'At least 12 characters.'),
            ]),
            Section::make('Access')->columnSpan(1)->schema([
                Select::make('role')
                    ->required()
                    ->default(User::ROLE_USER)
                    ->options([
                        User::ROLE_USER => 'User (site visitor)',
                        User::ROLE_EDITOR => 'Editor (catalog and content)',
                        User::ROLE_ADMIN => 'Admin (everything)',
                    ])
                    ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                    ->helperText(fn (?User $record) => $record?->is(auth()->user()) ? 'You cannot change your own role.' : null),
                DateTimePicker::make('email_verified_at')->label('Email verified'),
                DateTimePicker::make('banned_at')->label('Banned since')
                    ->helperText('Set a date to block this account. Clear it to unban.')
                    ->disabled(fn (?User $record) => $record?->is(auth()->user())),
                DateTimePicker::make('last_login_at')->disabled()->dehydrated(false)->visibleOn('edit'),
            ]),
        ]);
    }
}
