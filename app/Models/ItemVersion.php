<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemVersion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['released_at' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
