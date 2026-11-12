<?php

namespace App\Filament\Resources\AdZones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('key')
                ->required()
                ->alphaDash()
                ->maxLength(100)
                ->unique(ignoreRecord: true)
                ->disabledOn('edit')
                ->helperText('Used by the theme to place this slot. Built-in zones cannot be renamed.'),
            TextInput::make('description')->maxLength(255)->columnSpanFull(),
            TextInput::make('width')->numeric()->suffix('px'),
            TextInput::make('height')->numeric()->suffix('px'),
            Toggle::make('is_active')->label('Show ads in this zone')->default(true),
        ]);
    }
}
