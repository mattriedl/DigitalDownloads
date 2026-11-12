<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Per-site settings (site name, logo, colors, SEO defaults, ad options...).
 * Every domain is its own install with its own database, so these values are what make
 * each site different while the code stays identical.
 *
 * Values are JSON-encoded so booleans, numbers and arrays survive the round trip.
 * The whole table is cached as one array and flushed on every write.
 */
class Settings
{
    private const CACHE_KEY = 'site_settings';

    private ?array $loaded = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, string $group = 'general', bool $encrypted = false): void
    {
        $stored = json_encode($value);
        if ($encrypted && $value !== null) {
            $stored = Crypt::encryptString($stored);
        }

        Setting::updateOrCreate(['key' => $key], [
            'group' => $group,
            'value' => $stored,
            'is_encrypted' => $encrypted,
        ]);

        $this->flush();
    }

    /** Set many keys at once: ['site_name' => 'X', ...]. */
    public function setMany(array $values, string $group = 'general'): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $group);
        }
    }

    public function group(string $group): array
    {
        return Setting::where('group', $group)->pluck('key')
            ->mapWithKeys(fn ($key) => [$key => $this->get($key)])->all();
    }

    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        try {
            if (! Schema::hasTable('settings')) {
                return $this->loaded = [];
            }
            $this->loaded = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::all()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $this->decode($s)])
                ->all());
        } catch (Throwable) {
            // Before install (no database yet) the site must still boot.
            $this->loaded = [];
        }

        return $this->loaded;
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
    }

    private function decode(Setting $setting): mixed
    {
        $raw = $setting->value;
        if ($raw === null) {
            return null;
        }
        if ($setting->is_encrypted) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (Throwable) {
                return null;
            }
        }

        return json_decode($raw, true);
    }
}
