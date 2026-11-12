<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One download event. IPs are stored only as a salted hash. */
class Download extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function link(): BelongsTo
    {
        return $this->belongsTo(ItemLink::class, 'item_link_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashIp(?string $ip): ?string
    {
        return $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null;
    }
}
