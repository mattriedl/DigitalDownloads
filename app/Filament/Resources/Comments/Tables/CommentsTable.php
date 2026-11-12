<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Models\Comment;
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

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['item', 'user']))
            ->columns([
                TextColumn::make('body')->limit(90)->wrap()->searchable(),
                TextColumn::make('item.title')->label('Item')->limit(30)->searchable(),
                TextColumn::make('user.name')->label('Author')->searchable(),
                TextColumn::make('status')->badge()->color(fn ($state) => match ($state) {
                    Comment::STATUS_APPROVED => 'success', Comment::STATUS_SPAM => 'danger', default => 'warning',
                }),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'spam' => 'Spam'])->default('pending'),
            ])
            ->recordActions([
                Action::make('approve')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (Comment $c) => $c->status !== Comment::STATUS_APPROVED)
                    ->action(fn (Comment $c) => $c->update(['status' => Comment::STATUS_APPROVED])),
                Action::make('spam')->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (Comment $c) => $c->status !== Comment::STATUS_SPAM)
                    ->action(fn (Comment $c) => $c->update(['status' => Comment::STATUS_SPAM])),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('approve')->icon(Heroicon::OutlinedCheck)
                    ->action(fn (Collection $records) => $records->each->update(['status' => Comment::STATUS_APPROVED]))
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('spam')->label('Mark as spam')->icon(Heroicon::OutlinedNoSymbol)
                    ->action(fn (Collection $records) => $records->each->update(['status' => Comment::STATUS_SPAM]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }
}
