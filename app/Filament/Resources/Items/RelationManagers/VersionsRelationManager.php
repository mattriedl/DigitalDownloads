<?php

namespace App\Filament\Resources\Items\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Version history shown on the item page. */
class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Version history';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('version')->required()->maxLength(50),
            DatePicker::make('released_at')->default(now()),
            Textarea::make('notes')->label('What changed')->rows(4)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('version')
            ->defaultSort('released_at', 'desc')
            ->columns([
                TextColumn::make('version'),
                TextColumn::make('released_at')->date()->sortable(),
                TextColumn::make('notes')->limit(80)->wrap(),
            ])
            ->headerActions([CreateAction::make()->label('Add version')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
