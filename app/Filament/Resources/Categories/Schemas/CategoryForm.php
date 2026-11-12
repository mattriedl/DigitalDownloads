<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\SeoFields;
use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make()
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('slug')
                            ->helperText('Leave blank to create it from the name.')
                            ->maxLength(255)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->placeholder('None (top-level category)')
                            ->relationship('parent', 'name', fn ($query, ?Category $record) => $query
                                ->whereNull('parent_id')
                                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey())))
                            ->searchable()
                            ->preload(),
                        TextInput::make('icon')
                            ->helperText('Icon name, e.g. monitor, smartphone, gamepad, book, music.')
                            ->maxLength(50),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Shown at the top of the category page; good for SEO.'),
                    ]),
                Grid::make(1)
                    ->columnSpan(1)
                    ->schema([
                        Section::make('Visibility')->schema([
                            Toggle::make('is_active')->label('Active')->default(true),
                            Toggle::make('show_on_home')->label('Show on homepage')->default(true),
                            TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers show first.'),
                        ]),
                    ]),
                SeoFields::section()->columnSpan(2),
            ]);
    }
}
