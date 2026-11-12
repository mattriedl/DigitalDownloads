<?php

namespace App\Models;

use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpFoundation\Response;

class Redirect extends Model
{
    use RecordsAdminActivity;

    protected $guarded = ['id', 'hits'];

    /** Store paths in one form ("/old-page") so lookups always match. */
    public static function normalizePath(string $path): string
    {
        $path = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?? $path, '/');

        return $path === '/' ? $path : rtrim($path, '/');
    }

    /** The redirect response for a request path, or null when none is set up. */
    public static function responseFor(string $path): ?Response
    {
        $redirect = static::where('from_path', static::normalizePath('/'.ltrim($path, '/')))->first();
        if (! $redirect) {
            return null;
        }

        $redirect->increment('hits');
        abort_if($redirect->status_code === 410, 410);

        return redirect()->to($redirect->to_url, $redirect->status_code === 302 ? 302 : 301);
    }

    protected static function booted(): void
    {
        static::saving(fn (Redirect $r) => $r->from_path = static::normalizePath($r->from_path));
    }
}
