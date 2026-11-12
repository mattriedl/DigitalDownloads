<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Support\SeoFields;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(2)->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('title')->required()->maxLength(255),
                    TextInput::make('slug')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)
                        ->prefix('/page/')
                        ->helperText('Leave blank to create it from the title.'),
                    RichEditor::make('body')->columnSpanFull(),
                ]),
                SeoFields::section(),
            ]),
            Section::make('Settings')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Published')->default(true),
                Select::make('type')->options(['page' => 'Page', 'policy' => 'Policy'])->default('page')->required(),
                Toggle::make('show_in_footer')->label('Link in footer'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }
}
