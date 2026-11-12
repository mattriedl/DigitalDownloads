<?php

namespace App\Models;

use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Stripe, PayPal and crypto are installed on every site, switched off until memberships launch. */
class PaymentGateway extends Model
{
    use RecordsAdminActivity;

    protected $guarded = ['id'];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'currencies' => 'array',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true)->orderBy('sort_order');
    }

    public function credential(string $key): ?string
    {
        return $this->credentials[$key] ?? null;
    }
}
