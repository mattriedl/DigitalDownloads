<?php

namespace App\Filament\Resources\Ads\Schemas;

use App\Filament\Resources\Items\Schemas\ItemForm;
use App\Models\Ad;
use App\Models\AdZone;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class AdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Grid::make(1)->columnSpan(2)->schema([
                Section::make()->columns(2)->schema([
                    TextInput::make('name')->required()->maxLength(255)->helperText('For your reference only.'),
                    Select::make('ad_zone_id')
                        ->label('Zone')
                        ->required()
                        ->searchable()
                        ->options(fn () => AdZone::orderBy('name')->get()->mapWithKeys(fn ($z) => [
                            $z->id => $z->name.($z->width ? " ({$z->width}×{$z->height})" : ''),
                        ])),
                    ToggleButtons::make('type')->inline()->required()->live()->columnSpanFull()
                        ->options([Ad::TYPE_CODE => 'Ad network code (AdSense etc.)', Ad::TYPE_IMAGE => 'Image banner'])
                        ->default(Ad::TYPE_CODE),
                    Textarea::make('code')
                        ->label('Ad code')
                        ->rows(8)
                        ->extraInputAttributes(['class' => 'font-mono text-sm'])
                        ->helperText('Paste the snippet from AdSense or another network.')
                        ->required(fn (Get $get) => $get('type') === Ad::TYPE_CODE)
                        ->visible(fn (Get $get) => $get('type') === Ad::TYPE_CODE)
                        ->columnSpanFull(),
                    FileUpload::make('image_path')
                        ->label('Banner image')
                        ->image()
                        ->disk('public')
                        ->directory('ads')
                        ->maxSize(2048)
                        ->required(fn (Get $get) => $get('type') === Ad::TYPE_IMAGE)
                        ->visible(fn (Get $get) => $get('type') === Ad::TYPE_IMAGE),
                    TextInput::make('target_url')
                        ->label('Click-through URL')
                        ->url()
                        ->required(fn (Get $get) => $get('type') === Ad::TYPE_IMAGE)
                        ->visible(fn (Get $get) => $get('type') === Ad::TYPE_IMAGE),
                ]),
            ]),
            Grid::make(1)->columnSpan(1)->schema([
                Section::make('Delivery')->schema([
                    Toggle::make('is_active')->label('Active')->default(true),
                    Select::make('category_id')
                        ->label('Only in category')
                        ->placeholder('All categories')
                        ->options(fn () => ItemForm::categoryOptions())
                        ->searchable()
                        ->helperText('Targeted ads replace general ads in that category.'),
                    TextInput::make('weight')->numeric()->default(1)->minValue(1)->maxValue(100)
                        ->helperText('When several ads share a zone, higher weight shows more often.'),
                    DateTimePicker::make('starts_at')->seconds(false),
                    DateTimePicker::make('ends_at')->seconds(false)->after('starts_at'),
                    Toggle::make('hide_for_members')->label('Hide for members')->default(true),
                ]),
            ]),
        ]);
    }
}
