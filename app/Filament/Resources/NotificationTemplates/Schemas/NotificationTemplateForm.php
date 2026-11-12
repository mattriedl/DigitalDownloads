<?php

namespace App\Filament\Resources\NotificationTemplates\Schemas;

use App\Models\NotificationTemplate;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NotificationTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make('Email')->columnSpan(2)->schema([
                TextInput::make('subject')->required()->maxLength(255),
                Textarea::make('email_body')->label('Message')->rows(10)->required(),
                Textarea::make('sms_body')->label('SMS text')->rows(3)->maxLength(320)
                    ->helperText('Only sent if SMS is enabled for this template and an SMS provider is set.'),
            ]),
            Section::make('Settings')->columnSpan(1)->schema([
                TextInput::make('name')->disabled()->dehydrated(false),
                Toggle::make('email_enabled')->label('Send email'),
                Toggle::make('sms_enabled')->label('Send SMS'),
                TextInput::make('variables_help')->label('Placeholders you can use')
                    ->formatStateUsing(fn (?NotificationTemplate $record) => collect($record?->variables ?? [])
                        ->map(fn ($v) => '{{'.$v.'}}')->implode('  '))
                    ->disabled()->dehydrated(false),
            ]),
        ]);
    }
}
