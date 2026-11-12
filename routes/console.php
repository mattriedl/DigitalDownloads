<?php

use Illuminate\Support\Facades\Schedule;

// Each site's cron runs `php artisan schedule:run` every minute (set up by deploy/new-site.sh).

// Nightly dead-link check; emails the admin when links newly break.
Schedule::command('links:check')->dailyAt('02:15')->withoutOverlapping(120)->runInBackground();

// Housekeeping
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('auth:clear-resets')->daily();
