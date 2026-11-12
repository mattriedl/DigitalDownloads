<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Item extends Model
{
    use HasFactory, HasSlug, RecordsAdminActivity, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SCHEDULED = 'scheduled';

    protected $guarded = ['id', 'download_count', 'view_count', 'rating_avg', 'rating_count'];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_members_only' => 'boolean',
            'allow_comments' => 'boolean',
            'noindex' => 'boolean',
            'published_at' => 'datetime',
            'rating_avg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        // Publishing without a date means "now".
        static::saving(function (Item $item) {
            if ($item->status === self::STATUS_PUBLISHED && $item->published_at === null) {
                $item->published_at = now();
            }
        });

        // Keep category counters right whenever an item is saved, moved or removed.
        $recount = function (Item $item) {
            $ids = array_filter([$item->category_id, $item->getOriginal('category_id')]);
            Category::whereIn('id', array_unique($ids))->get()->each->refreshItemsCount();
        };
        static::saved($recount);
        static::deleted($recount);
        static::restored($recount);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ItemLink::class)->orderBy('sort_order');
    }

    public function activeLinks(): HasMany
    {
        return $this->links()->where('is_active', true);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(ItemScreenshot::class)->orderBy('sort_order');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ItemVersion::class)->orderByDesc('released_at');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /** Published and live now (scheduled items appear once their time arrives). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_SCHEDULED])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular(Builder $query): Builder
    {
        return $query->orderByDesc('download_count');
    }

    public function scopeTopRated(Builder $query): Builder
    {
        return $query->where('rating_count', '>', 0)->orderByDesc('rating_avg')->orderByDesc('rating_count');
    }

    /** Full-text search on MySQL, LIKE fallback elsewhere (SQLite in tests). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        if (DB::getDriverName() === 'mysql') {
            return $query->whereFullText(['title', 'short_description', 'publisher'], $term);
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%")
                ->orWhere('publisher', 'like', "%{$term}%");
        });
    }

    public function isPublished(): bool
    {
        return in_array($this->status, [self::STATUS_PUBLISHED, self::STATUS_SCHEDULED], true)
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    /** Recalculate the star rating from approved reviews. */
    public function refreshRating(): void
    {
        $stats = $this->reviews()->where('status', Review::STATUS_APPROVED)
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')->first();

        $this->forceFill([
            'rating_count' => (int) $stats->c,
            'rating_avg' => round((float) $stats->a, 2),
        ])->saveQuietly();
    }

    public function metaTitle(): string
    {
        return $this->seo_title ?: $this->title;
    }

    public function metaDescription(): ?string
    {
        return $this->seo_description ?: $this->short_description;
    }
}
