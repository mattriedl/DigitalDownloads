<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Items\ItemResource;
use App\Models\Item;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class TopDownloads extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Most downloaded, last 30 days';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Item::query()
                ->with('category')
                ->withCount(['downloads as recent_downloads' => fn ($q) => $q->where('created_at', '>=', now()->subDays(30))])
                ->orderByDesc('recent_downloads')
                ->orderByDesc('download_count'))
            ->paginated([10])
            ->recordUrl(fn (Item $record) => ItemResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('title')->limit(50)->description(fn (Item $r) => $r->category?->name),
                TextColumn::make('recent_downloads')->label('Last 30 days')->numeric(),
                TextColumn::make('download_count')->label('All time')->numeric(),
                TextColumn::make('rating_avg')->label('Rating')
                    ->formatStateUsing(fn ($state, Item $r) => $r->rating_count ? number_format($state, 1).' ★' : '—'),
            ]);
    }
}
