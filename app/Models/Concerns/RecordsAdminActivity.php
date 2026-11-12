<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Support\Arr;

/**
 * Writes an activity log entry when an admin or editor creates, changes or deletes this record.
 * Visitor actions (a review being posted, a download counter going up) are not logged.
 */
trait RecordsAdminActivity
{
    /** Never written to the log. */
    protected static array $activityHidden = [
        'password', 'remember_token', 'credentials', 'two_factor_secret', 'two_factor_recovery_codes', 'code',
        'updated_at', 'created_at', 'last_login_at', 'last_login_ip', 'email_verified_at',
    ];

    public static function bootRecordsAdminActivity(): void
    {
        static::created(fn ($model) => $model->logAdminActivity('created'));
        static::updated(fn ($model) => $model->logAdminActivity('updated'));
        static::deleted(fn ($model) => $model->logAdminActivity('deleted'));
    }

    protected function logAdminActivity(string $action): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'isStaff') || ! $user->isStaff()) {
            return;
        }

        $changes = $action === 'updated' ? $this->getChanges() : [];
        $changes = Arr::except($changes, static::$activityHidden);
        if ($action === 'updated' && $changes === []) {
            return;
        }

        ActivityLog::record($action, $this, array_filter([
            'label' => $this->title ?? $this->name ?? $this->label ?? $this->subject ?? $this->from_path ?? null,
            'changes' => array_map(fn ($v) => is_scalar($v) || $v === null ? (string) $v : json_encode($v), $changes) ?: null,
        ]));
    }
}
