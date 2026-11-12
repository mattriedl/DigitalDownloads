<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Models\Review;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('item_id')->relationship('item', 'title')->disabled(),
            Select::make('user_id')->relationship('user', 'name')->label('Reviewer')->disabled(),
            Select::make('rating')->options([1 => '1 ★', 2 => '2 ★', 3 => '3 ★', 4 => '4 ★', 5 => '5 ★'])->required(),
            ToggleButtons::make('status')->inline()->required()
                ->options([Review::STATUS_PENDING => 'Pending', Review::STATUS_APPROVED => 'Approved', Review::STATUS_REJECTED => 'Rejected'])
                ->colors([Review::STATUS_PENDING => 'warning', Review::STATUS_APPROVED => 'success', Review::STATUS_REJECTED => 'danger']),
            TextInput::make('title')->maxLength(255)->columnSpanFull(),
            Textarea::make('body')->rows(5)->columnSpanFull(),
        ]);
    }
}
