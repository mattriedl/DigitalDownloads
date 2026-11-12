<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One per-site setting. Read through App\Support\Settings (cached), not directly. */
class Setting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }
}
