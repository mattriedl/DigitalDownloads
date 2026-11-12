<?php

namespace App\Filament\Resources\SupportTickets\RelationManagers;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use App\Support\Notifier;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/** The ticket conversation, oldest first, with a Reply button for staff. */
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->required()->rows(6),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'asc')
            ->paginated(false)
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'attachments']))
            ->columns([
                TextColumn::make('user.name')->label('From')
                    ->formatStateUsing(fn ($state, TicketMessage $record) => ($state ?? 'Deleted user').($record->is_staff ? ' (staff)' : ''))
                    ->badge()
                    ->color(fn (TicketMessage $record) => $record->is_staff ? 'success' : 'gray'),
                TextColumn::make('body')->wrap()->label('Message')
                    ->description(fn (TicketMessage $record) => $record->attachments->isEmpty() ? null : new HtmlString(
                        $record->attachments->map(fn ($file) => '<a class="underline" href="'.e(route('tickets.attachment', [$record->support_ticket_id, $file->id])).'">'.e($file->original_name).'</a>')->implode(' · ')
                    )),
                TextColumn::make('created_at')->dateTime('M j, g:i a'),
            ])
            ->headerActions([
                Action::make('reply')
                    ->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->schema([
                        Textarea::make('body')->label('Your reply')->required()->rows(6),
                        FileUpload::make('attachments')->multiple()->disk('local')->directory('ticket-attachments')
                            ->maxFiles(5)->maxSize(5120)->storeFileNamesIn('attachment_names'),
                        Toggle::make('close')->label('Close the ticket after replying'),
                    ])
                    ->action(function (array $data) {
                        /** @var SupportTicket $ticket */
                        $ticket = $this->getOwnerRecord();

                        DB::transaction(function () use ($ticket, $data) {
                            $message = $ticket->messages()->create([
                                'user_id' => auth()->id(),
                                'is_staff' => true,
                                'body' => $data['body'],
                            ]);
                            foreach ($data['attachments'] ?? [] as $path) {
                                $message->attachments()->create([
                                    'path' => $path,
                                    'original_name' => $data['attachment_names'][$path] ?? basename($path),
                                    'size' => Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : 0,
                                ]);
                            }
                            $ticket->update([
                                'status' => ($data['close'] ?? false) ? 'closed' : 'answered',
                                'last_reply_at' => now(),
                                'assigned_to' => $ticket->assigned_to ?? auth()->id(),
                            ]);
                        });

                        Notifier::send('ticket_reply', $ticket->user?->email, [
                            'name' => $ticket->user?->name,
                            'subject' => $ticket->subject,
                            'message' => $data['body'],
                            'ticket_url' => route('tickets.show', $ticket),
                        ]);
                    }),
            ]);
    }
}
