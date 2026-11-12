<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('slug')->alphaDash()->maxLength(255)->unique(ignoreRecord: true)->helperText('Leave blank to create it from the name.'),
                TextInput::make('price')->numeric()->required()->minValue(0)->prefix('$'),
                Select::make('interval')->label('Billed')->options(['month' => 'Monthly', 'year' => 'Yearly'])->required()->default('month'),
                TextInput::make('trial_days')->numeric()->default(0)->suffix('days'),
                TextInput::make('currency')->default('USD')->maxLength(3)->required(),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                CheckboxList::make('perks')->columnSpanFull()->columns(2)->options([
                    'no_ads' => 'No ads',
                    'no_countdown' => 'Skip the download countdown',
                    'priority_downloads' => 'Priority downloads',
                    'members_only_items' => 'Access to members-only items',
                ]),
            ]),
            Section::make('Status')->columnSpan(1)->schema([
                Toggle::make('is_active')->label('Offered on the site')->default(true)
                    ->helperText('Plans only appear once memberships are switched on in Settings.'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }
}
