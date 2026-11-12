<?php

namespace App\Models\Concerns;

use App\Jobs\PingIndexNow;
use App\Models\Redirect;

/**
 * For models with their own public page (items, categories, posts, pages):
 *  - when an admin changes the slug, the old address 301-redirects to the new one,
 *    so search rankings and shared links survive the rename;
 *  - when a page goes live or changes, IndexNow search engines are told about it.
 *
 * The model sets `protected static string $publicPrefix = '/download/';`
 * and may override isPubliclyVisible().
 */
trait HasPublicUrl
{
    public static function bootHasPublicUrl(): void
    {
        static::updated(function ($model) {
            $old = $model->getOriginal('slug');
            if (! $model->wasChanged('slug') || blank($old)) {
                return;
            }

            $from = Redirect::normalizePath(static::$publicPrefix.$old);
            $to = static::$publicPrefix.$model->slug;

            // The new address must never redirect anywhere (e.g. renamed back to an earlier slug).
            Redirect::where('from_path', Redirect::normalizePath($to))->delete();
            // Older redirects that pointed at the old address now go straight to the new one (no chains).
            Redirect::where('to_url', $from)->update(['to_url' => $to]);
            Redirect::updateOrCreate(['from_path' => $from], ['to_url' => $to, 'status_code' => 301]);
        });

        static::saved(function ($model) {
            if (! $model->isPubliclyVisible()) {
                return;
            }
            if (! $model->wasRecentlyCreated && ! $model->wasChanged(['slug', 'status', 'published_at', 'title', 'name', 'description', 'content', 'body'])) {
                return;
            }

            $urls = [url(static::$publicPrefix.$model->slug)];
            if ($model->wasChanged('slug') && $model->getOriginal('slug')) {
                $urls[] = url(static::$publicPrefix.$model->getOriginal('slug'));
            }
            PingIndexNow::dispatch($urls)->afterCommit();
        });
    }

    public function publicPath(): string
    {
        return static::$publicPrefix.$this->slug;
    }

    /** Live right now? (Scheduled posts are picked up by the sitemap when their time comes.) */
    public function isPubliclyVisible(): bool
    {
        if (array_key_exists('noindex', $this->attributes) && $this->noindex) {
            return false;
        }
        if (array_key_exists('is_active', $this->attributes)) {
            return (bool) $this->is_active;
        }
        if (array_key_exists('status', $this->attributes)) {
            return in_array($this->status, ['published', 'scheduled'], true)
                && $this->published_at !== null && $this->published_at->lte(now());
        }

        return true;
    }
}
