<?php

namespace App\Filament\Resources\BlogCategories\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('slug')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)->helperText('Leave blank to create it from the name.'),
            SeoFields::section()->columnSpanFull(),
        ]);
    }
}
