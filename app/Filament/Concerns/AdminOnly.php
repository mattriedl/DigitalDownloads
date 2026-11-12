<?php

namespace App\Filament\Concerns;

/**
 * Editors manage the catalog and content; only admins see users, ads money,
 * payments, templates, settings and logs.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
