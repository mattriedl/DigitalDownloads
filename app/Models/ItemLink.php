<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One download source for an item: a link to another site (most items) or a file we host. */
class ItemLink extends Model
{
    use HasFactory;

    public const TYPE_EXTERNAL = 'external';

    public const TYPE_HOSTED = 'hosted';

    /** Consecutive failed checks before a link is flagged broken. */
    public const BROKEN_AFTER_FAILURES = 3;

    protected $guarded = ['id', 'click_count'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_broken' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function isExternal(): bool
    {
        return $this->type === self::TYPE_EXTERNAL;
    }

    /** Record the result of a dead-link check. */
    public function recordCheck(?int $statusCode): void
    {
        $ok = $statusCode !== null && $statusCode < 400;
        $failures = $ok ? 0 : $this->failed_checks + 1;

        $this->forceFill([
            'last_checked_at' => now(),
            'last_status_code' => $statusCode,
            'failed_checks' => min($failures, 255),
            'is_broken' => $failures >= self::BROKEN_AFTER_FAILURES,
        ])->save();
    }
}
