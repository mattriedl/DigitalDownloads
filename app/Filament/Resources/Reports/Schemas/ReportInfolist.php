<?php

namespace App\Filament\Resources\Reports\Schemas;

use App\Models\Report;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Section::make('Report')->columnSpanFull()->columns(3)->schema([
                TextEntry::make('type')->badge()->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->title()),
                TextEntry::make('status')->badge()->color(fn ($state) => match ($state) {
                    'resolved' => 'success', 'dismissed' => 'gray', default => 'warning',
                }),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('item.title')->label('Item')->placeholder('—')
                    ->url(fn (Report $r) => $r->item ? route('filament.admin.resources.items.edit', $r->item) : null),
                TextEntry::make('link.url')->label('Download link')->placeholder('—')->limit(60),
                TextEntry::make('link.last_status_code')->label('Last check')->placeholder('Not checked yet'),
                TextEntry::make('name')->placeholder('—'),
                TextEntry::make('email')->placeholder('—')->copyable(),
                TextEntry::make('user.name')->label('Account')->placeholder('Guest'),
                TextEntry::make('message')->columnSpanFull()->placeholder('No message'),
            ]),
            Section::make('Staff')->columnSpanFull()->schema([
                TextEntry::make('admin_note')->label('Internal note')->placeholder('—'),
                TextEntry::make('resolved_at')->dateTime()->placeholder('Not resolved'),
            ]),
        ]);
    }
}
