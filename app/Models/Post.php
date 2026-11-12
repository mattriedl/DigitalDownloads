<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasPublicUrl;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsAdminActivity;
use App\Support\Images;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use FlushesSiteCache, HasPublicUrl, HasSlug, RecordsAdminActivity, SoftDeletes;

    protected static string $publicPrefix = '/blog/';

    protected $guarded = ['id', 'view_count'];

    protected function casts(): array
    {
        return [
            'faq' => 'array',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Drop the old resized copies when the image is replaced or removed.
        static::saved(function (Post $m) {
            if ($m->wasChanged('image')) {
                Images::forget($m->getOriginal('image'));
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', ['published', 'scheduled'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
