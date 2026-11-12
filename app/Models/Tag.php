<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasSlug, RecordsAdminActivity;

    protected $guarded = ['id'];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class);
    }
}
