<?php

namespace App\Filament\Resources\BlogCategories\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BlogCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->modifyQueryUsing(fn ($query) => $query->withCount('posts'))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('slug')->color('gray'),
                TextColumn::make('posts_count')->label('Posts'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
