<?php

namespace App\Filament\Resources\Redirects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RedirectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('from_path')->label('Old path')->required()->maxLength(255)->placeholder('/download/old-name')
                ->unique(ignoreRecord: true),
            TextInput::make('to_url')->label('Send visitors to')->required()->maxLength(255)->placeholder('/download/new-name or https://...'),
            Select::make('status_code')->label('Type')->required()->default(301)
                ->options([301 => '301 Permanent (best for SEO)', 302 => '302 Temporary', 410 => '410 Gone (removed for good)']),
        ]);
    }
}
