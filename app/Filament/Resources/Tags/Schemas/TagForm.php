<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('slug')->alphaDash()->maxLength(120)->unique(ignoreRecord: true)
                ->helperText('Leave blank to create it from the name.'),
        ]);
    }
}
