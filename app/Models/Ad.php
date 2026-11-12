<?php

namespace App\Models;

use App\Models\Concerns\RecordsAdminActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Ad extends Model
{
    use RecordsAdminActivity;

    public const TYPE_IMAGE = 'image';

    public const TYPE_CODE = 'code';

    protected $guarded = ['id', 'impressions', 'clicks'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'hide_for_members' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(AdZone::class, 'ad_zone_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(AdStat::class);
    }

    /** Active and inside its start/end window. */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /**
     * Pick one ad for a zone: category-targeted ads win over general ones,
     * then a weighted random pick so several ads can share a slot.
     */
    public static function pickFor(string $zoneKey, ?int $categoryId = null): ?self
    {
        $candidates = static::running()
            ->with('zone')
            ->whereHas('zone', fn ($q) => $q->where('key', $zoneKey)->where('is_active', true))
            ->where(fn ($q) => $q->whereNull('category_id')->when($categoryId, fn ($q) => $q->orWhere('category_id', $categoryId)))
            ->get();

        if ($categoryId && $candidates->contains('category_id', $categoryId)) {
            $candidates = $candidates->where('category_id', $categoryId);
        }

        return static::weightedPick($candidates);
    }

    public static function weightedPick(Collection $ads): ?self
    {
        $total = $ads->sum(fn ($ad) => max(1, $ad->weight));
        if ($total === 0) {
            return null;
        }
        $roll = random_int(1, $total);
        foreach ($ads as $ad) {
            $roll -= max(1, $ad->weight);
            if ($roll <= 0) {
                return $ad;
            }
        }

        return $ads->last();
    }

    public function ctr(): float
    {
        return $this->impressions > 0 ? round($this->clicks / $this->impressions * 100, 2) : 0.0;
    }
}
