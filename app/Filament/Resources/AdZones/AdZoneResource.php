<?php

namespace App\Filament\Resources\AdZones;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\AdZones\Pages\CreateAdZone;
use App\Filament\Resources\AdZones\Pages\EditAdZone;
use App\Filament\Resources\AdZones\Pages\ListAdZones;
use App\Filament\Resources\AdZones\Schemas\AdZoneForm;
use App\Filament\Resources\AdZones\Tables\AdZonesTable;
use App\Models\AdZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AdZoneResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = AdZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Advertising';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ad zones';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AdZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdZonesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdZones::route('/'),
            'create' => CreateAdZone::route('/create'),
            'edit' => EditAdZone::route('/{record}/edit'),
        ];
    }
}
