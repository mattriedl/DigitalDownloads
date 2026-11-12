<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'variables' => 'array',
        ];
    }

    /** Replace {{variable}} placeholders with values. */
    public static function render(?string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', fn ($m) => (string) ($vars[$m[1]] ?? ''), (string) $text);
    }
}
