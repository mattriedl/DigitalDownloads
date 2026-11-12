<?php

namespace App\Filament\Resources\Items\Tables;

use App\Filament\Resources\Items\Schemas\ItemForm;
use App\Models\Item;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount([
                'links as broken_links_count' => fn ($q) => $q->where('is_broken', true),
            ]))
            ->columns([
                ImageColumn::make('thumbnail')->disk('public')->label('')->square()->size(40),
                TextColumn::make('title')->searchable()->sortable()->limit(50)
                    ->description(fn (Item $record) => $record->category?->name),
                TextColumn::make('status')->badge()->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        Item::STATUS_PUBLISHED => 'success',
                        Item::STATUS_SCHEDULED => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('broken_links_count')->label('Broken links')
                    ->badge(fn ($state) => $state > 0)->color('danger')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : '—'),
                TextColumn::make('download_count')->label('Downloads')->numeric()->sortable(),
                TextColumn::make('rating_avg')->label('Rating')->sortable()
                    ->formatStateUsing(fn ($state, Item $record) => $record->rating_count ? number_format($state, 1).' ★' : '—'),
                ToggleColumn::make('is_featured')->label('Featured'),
                TextColumn::make('published_at')->dateTime('M j, Y')->sortable(),
                TextColumn::make('version')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('view_count')->label('Views')->numeric()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    Item::STATUS_DRAFT => 'Draft',
                    Item::STATUS_PUBLISHED => 'Published',
                    Item::STATUS_SCHEDULED => 'Scheduled',
                ]),
                SelectFilter::make('category_id')->label('Category')->options(fn () => ItemForm::categoryOptions())->searchable(),
                TernaryFilter::make('is_featured')->label('Featured'),
                Filter::make('broken')->label('Has broken links')
                    ->query(fn (Builder $q) => $q->whereHas('links', fn ($l) => $l->where('is_broken', true))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->label('View')
                    ->color('gray')
                    ->url(fn (Item $record) => url('/download/'.$record->slug), shouldOpenInNewTab: true)
                    ->visible(fn (Item $record) => $record->isPublished()),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(fn (Item $item) => $item->update([
                            'status' => Item::STATUS_PUBLISHED,
                            'published_at' => $item->published_at ?? now(),
                        ])))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Move to draft')
                        ->icon(Heroicon::OutlinedPauseCircle)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => Item::STATUS_DRAFT]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('move')
                        ->label('Move to category')
                        ->icon(Heroicon::OutlinedFolderArrowDown)
                        ->schema([Select::make('category_id')->label('Category')->options(fn () => ItemForm::categoryOptions())->required()->searchable()])
                        ->action(fn (Collection $records, array $data) => $records->each->update(['category_id' => $data['category_id']]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }
}
