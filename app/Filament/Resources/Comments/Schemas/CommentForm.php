<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Models\Comment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('item_id')->relationship('item', 'title')->disabled(),
            Select::make('user_id')->relationship('user', 'name')->label('Author')->disabled(),
            ToggleButtons::make('status')->inline()->required()->columnSpanFull()
                ->options([Comment::STATUS_PENDING => 'Pending', Comment::STATUS_APPROVED => 'Approved', Comment::STATUS_SPAM => 'Spam'])
                ->colors([Comment::STATUS_PENDING => 'warning', Comment::STATUS_APPROVED => 'success', Comment::STATUS_SPAM => 'danger']),
            Textarea::make('body')->rows(5)->required()->columnSpanFull(),
        ]);
    }
}
