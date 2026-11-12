<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Models\Report;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        $types = collect(Report::TYPES)->mapWithKeys(fn ($t) => [$t => str($t)->replace('_', ' ')->title()->toString()])->all();

        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'user']))
            ->columns([
                TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => $types[$state] ?? $state)
                    ->color(fn ($state) => $state === 'dmca' ? 'danger' : 'info'),
                TextColumn::make('item.title')->label('Item')->limit(40)->placeholder('—')->searchable(),
                TextColumn::make('message')->limit(60)->wrap()->placeholder('—'),
                TextColumn::make('email')->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    'resolved' => 'success', 'dismissed' => 'gray', default => 'warning',
                }),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])->default('open'),
                SelectFilter::make('type')->options($types),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('resolve')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Report $r) => $r->status === 'open')
                    ->action(fn (Report $r) => $r->update(['status' => 'resolved', 'resolved_at' => now()])),
                Action::make('unpublish')->label('Unpublish item')->icon(Heroicon::OutlinedEyeSlash)->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Moves the item to draft so it disappears from the site (use for DMCA takedowns).')
                    ->visible(fn (Report $r) => $r->item && $r->item->status !== 'draft')
                    ->action(function (Report $r) {
                        $r->item->update(['status' => 'draft']);
                        $r->update(['status' => 'resolved', 'resolved_at' => now(), 'admin_note' => trim($r->admin_note."\nItem unpublished.")]);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('resolve')->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'resolved', 'resolved_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('dismiss')->icon(Heroicon::OutlinedXMark)
                    ->action(fn (Collection $records) => $records->each->update(['status' => 'dismissed', 'resolved_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }
}
