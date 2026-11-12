<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()
                    ->description(fn ($record) => $record->parent ? 'in '.$record->parent->name : 'Top level'),
                TextColumn::make('slug')->searchable()->color('gray')->toggleable(),
                TextColumn::make('items_count')->label('Items')->numeric()->sortable(),
                ToggleColumn::make('is_active')->label('Active'),
                ToggleColumn::make('show_on_home')->label('Home'),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with('parent'))
            ->filters([
                SelectFilter::make('parent_id')->label('Parent')->relationship('parent', 'name'),
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn ($record) => $record->items()->withTrashed()->exists() || $record->children()->exists())
                    ->tooltip(fn ($record) => $record->items()->withTrashed()->exists() || $record->children()->exists()
                        ? 'Move its items and subcategories first' : null),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
