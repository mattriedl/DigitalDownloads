<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Fills `slug` from `title` (or `name`) on create and keeps it unique within the table.
 * Admins can still type their own slug; it is normalized and de-duplicated the same way.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->slug ?: ($model->title ?? $model->name ?? '');
            if ($model->slug && ! $model->isDirty('slug')) {
                return;
            }
            $model->slug = static::uniqueSlug(Str::slug($source) ?: Str::random(8), $model->getKey());
        });
    }

    public static function uniqueSlug(string $base, $ignoreId = null): string
    {
        $slug = $base;
        $i = 2;
        while (static::withoutGlobalScopes()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->when(method_exists(static::class, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
