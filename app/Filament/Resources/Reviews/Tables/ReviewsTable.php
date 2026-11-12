<?php

namespace App\Filament\Resources\Reviews\Tables;

use App\Models\Review;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'user']))
            ->columns([
                TextColumn::make('item.title')->label('Item')->searchable()->limit(40),
                TextColumn::make('user.name')->label('Reviewer')->searchable(),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->color('warning'),
                TextColumn::make('title')->limit(40)->description(fn (Review $r) => str($r->body)->limit(80)),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    Review::STATUS_APPROVED => 'success', Review::STATUS_REJECTED => 'danger', default => 'warning',
                }),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'])->default('pending'),
                SelectFilter::make('rating')->options([1 => '1 ★', 2 => '2 ★', 3 => '3 ★', 4 => '4 ★', 5 => '5 ★']),
            ])
            ->recordActions([
                Action::make('approve')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Review $r) => $r->status !== Review::STATUS_APPROVED)
                    ->action(fn (Review $r) => $r->update(['status' => Review::STATUS_APPROVED])),
                Action::make('reject')->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (Review $r) => $r->status !== Review::STATUS_REJECTED)
                    ->action(fn (Review $r) => $r->update(['status' => Review::STATUS_REJECTED])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('approve')->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['status' => Review::STATUS_APPROVED]))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('reject')->icon(Heroicon::OutlinedXMark)
                    ->action(fn (Collection $records) => $records->each->update(['status' => Review::STATUS_REJECTED]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }
}
