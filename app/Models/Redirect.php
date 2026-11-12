<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    protected $guarded = ['id', 'hits'];

    /** Store paths in one form ("/old-page") so lookups always match. */
    public static function normalizePath(string $path): string
    {
        $path = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    protected static function booted(): void
    {
        static::saving(fn (Redirect $r) => $r->from_path = static::normalizePath($r->from_path));
    }
}
