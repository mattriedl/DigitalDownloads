<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Small WebP copies of uploaded images, so a list of 24 items doesn't download 24 full-size uploads.
 *
 * The copy is made the first time a page asks for it and then served straight from
 * storage/app/public/resized (no PHP involved). Falls back to the original if GD can't do it.
 */
class Images
{
    /** Never upscale; never try to decode huge files on a web request. */
    private const MAX_SOURCE_BYTES = 8 * 1024 * 1024;

    public static function url(?string $path, int $width): ?string
    {
        if (blank($path)) {
            return null;
        }

        $resized = static::resizedPath($path, $width);
        $disk = Storage::disk('public');

        if ($disk->exists($resized) || static::make($path, $resized, $width)) {
            return asset('storage/'.$resized).'?v='.substr(md5((string) $disk->lastModified($path)), 0, 6);
        }

        return asset('storage/'.$path);
    }

    public static function resizedPath(string $path, int $width): string
    {
        return 'resized/'.$width.'/'.preg_replace('/\.[^.\/]+$/', '', ltrim($path, '/')).'.webp';
    }

    /** Remove every resized copy of an image (after it is replaced or deleted). */
    public static function forget(?string $path): void
    {
        if (blank($path)) {
            return;
        }
        $disk = Storage::disk('public');
        foreach ($disk->directories('resized') as $dir) {
            $disk->delete(static::resizedPath($path, (int) basename($dir)));
        }
    }

    private static function make(string $path, string $target, int $width): bool
    {
        $disk = Storage::disk('public');

        try {
            if (! function_exists('imagewebp') || ! $disk->exists($path) || $disk->size($path) > self::MAX_SOURCE_BYTES) {
                return false;
            }

            $source = @imagecreatefromstring($disk->get($path));
            if (! $source) {
                return false;
            }

            $w = imagesx($source);
            $h = imagesy($source);
            $newW = min($width, $w);
            $newH = max(1, (int) round($h * $newW / $w));

            $canvas = imagecreatetruecolor($newW, $newH);
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $w, $h);

            ob_start();
            imagewebp($canvas, null, 80);
            $disk->put($target, ob_get_clean());

            return true;
        } catch (Throwable $e) {
            Log::warning("Could not resize {$path}: ".$e->getMessage());

            return false;
        }
    }
}
