<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'author']))
            ->columns([
                ImageColumn::make('image')->disk('public')->label('')->size(40),
                TextColumn::make('title')->searchable()->limit(60)->description(fn ($record) => $record->category?->name),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'published' => 'success', 'scheduled' => 'warning', default => 'gray',
                }),
                TextColumn::make('author.name')->label('Author')->toggleable(),
                TextColumn::make('view_count')->label('Views')->numeric()->sortable(),
                TextColumn::make('published_at')->dateTime('M j, Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'scheduled' => 'Scheduled']),
                SelectFilter::make('blog_category_id')->label('Category')->relationship('category', 'name'),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([
                DeleteBulkAction::make(), RestoreBulkAction::make(), ForceDeleteBulkAction::make(),
            ])]);
    }
}
