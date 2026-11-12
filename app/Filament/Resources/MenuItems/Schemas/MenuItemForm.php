<?php

namespace App\Filament\Resources\MenuItems\Schemas;

use App\Models\MenuItem;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MenuItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('menu')->options(['header' => 'Header menu', 'footer' => 'Footer menu'])->default('header')->required()->live(),
            Select::make('parent_id')->label('Inside (dropdown)')->placeholder('Top level')
                ->options(fn (Get $get, ?MenuItem $record) => MenuItem::whereNull('parent_id')
                    ->where('menu', $get('menu') ?? 'header')
                    ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->pluck('label', 'id')),
            TextInput::make('label')->required()->maxLength(100),
            TextInput::make('url')->label('Link')->required()->maxLength(255)->placeholder('/popular or https://...'),
            Toggle::make('new_tab')->label('Open in new tab'),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }
}
