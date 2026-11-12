<?php

namespace App\Filament\Resources\MenuItems\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->defaultGroup(Group::make('menu')->titlePrefixedWithLabel(false)->getTitleFromRecordUsing(fn ($record) => ucfirst($record->menu).' menu'))
            ->modifyQueryUsing(fn ($query) => $query->with('parent'))
            ->columns([
                TextColumn::make('label')->description(fn ($record) => $record->parent ? 'in '.$record->parent->label : null),
                TextColumn::make('url')->label('Link')->color('gray'),
            ])
            ->filters([SelectFilter::make('menu')->options(['header' => 'Header', 'footer' => 'Footer'])])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
