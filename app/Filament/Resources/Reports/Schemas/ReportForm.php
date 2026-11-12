<?php

namespace App\Filament\Resources\Reports\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Schema;

/** Staff only change the status and leave a note; the visitor's report stays as sent. */
class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            ToggleButtons::make('status')->inline()->required()
                ->options(['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'])
                ->colors(['open' => 'warning', 'resolved' => 'success', 'dismissed' => 'gray']),
            Textarea::make('admin_note')->label('Internal note')->rows(4),
        ]);
    }
}
