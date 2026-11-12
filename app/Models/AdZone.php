<?php

namespace App\Models;

use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A fixed ad slot in the theme (header leaderboard, sidebar, download countdown page, ...). */
class AdZone extends Model
{
    use RecordsAdminActivity;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
}
