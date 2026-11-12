<?php

namespace App\Models;

use App\Models\Concerns\FlushesSiteCache;
use App\Models\Concerns\HasPublicUrl;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use FlushesSiteCache, HasFactory, HasPublicUrl, HasSlug, RecordsAdminActivity;

    protected static string $publicPrefix = '/category/';

    protected $guarded = ['id', 'items_count'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_on_home' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** IDs of this category and every subcategory below it. */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        $level = [$this->id];
        // Walk down one level per query; the tree is shallow (category > subcategory).
        while ($level = static::whereIn('parent_id', $level)->pluck('id')->all()) {
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    /** Recount published items so listings never run COUNT(*) on every page view. */
    public function refreshItemsCount(): void
    {
        $this->forceFill(['items_count' => $this->items()->published()->count()])->saveQuietly();
    }
}
