<?php

namespace App\Filament\Resources\PaymentGateways\Pages;

use App\Filament\Resources\PaymentGateways\PaymentGatewayResource;
use Filament\Resources\Pages\EditRecord;

class EditPaymentGateway extends EditRecord
{
    protected static string $resource = PaymentGatewayResource::class;

    /** Credentials are hidden from serialization, so load them into the form explicitly. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['credentials'] = $this->record->credentials ?? [];

        return $data;
    }

    /** A blank secret field means "keep the saved value". */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $saved = $this->record->credentials ?? [];
        foreach ($data['credentials'] ?? [] as $key => $value) {
            if (blank($value) && filled($saved[$key] ?? null)) {
                $data['credentials'][$key] = $saved[$key];
            }
        }

        return $data;
    }
}
