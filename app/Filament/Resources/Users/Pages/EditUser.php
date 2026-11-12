<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->hidden(fn () => $this->record->is(auth()->user())),
        ];
    }

    /** role, banned_at and email_verified_at are not mass-assignable on purpose; only admins set them here. */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->is(auth()->user())) {
            unset($data['role'], $data['banned_at']);
        }
        $record->forceFill($data)->save();

        return $record;
    }
}
