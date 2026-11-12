<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasSlug, RecordsAdminActivity;

    public const PERKS = ['no_ads', 'no_countdown', 'priority_downloads', 'members_only_items'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'perks' => 'array',
            'gateway_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function hasPerk(string $perk): bool
    {
        return in_array($perk, $this->perks ?? [], true);
    }
}
