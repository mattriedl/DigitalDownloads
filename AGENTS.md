# DigitalDownloads — notes for Claude

Download portal script. One codebase deployed as **separate installs per domain** (own DB,
admin, content). Never add cross-site/multi-tenant logic; anything that differs per site is
a setting (`App\Support\Settings`, `setting('key')`) or database content.

- Laravel 13, PHP 8.3+ locally, 8.5 on the server, MySQL 8 in production, SQLite in tests.
- Full-text search: MySQL `FULLTEXT` index on items; `Item::scopeSearch` falls back to LIKE
  on SQLite. Guard any MySQL-only schema with `DB::getDriverName() === 'mysql'`.
- New per-site defaults go in `DefaultsSeeder` (idempotent; runs on every deploy; must
  never overwrite admin changes).
- Payment gateways exist but stay `is_enabled = false` until memberships launch.
- Content standards for SEO fields: `seo_title` ≤ 70 chars, `seo_description` 150–160 chars,
  no years in titles/H1s/descriptions.
- Most items are external links (`item_links.type = external`); downloads go through a
  countdown/ad page, and a nightly checker flags dead links.
- Deploy: push to `main`; server cron runs `deploy/deploy-all.sh` for every domain in
  `/etc/digitaldownloads/sites`.
- Run `php artisan test` before pushing.
- Never register Eloquent events (`Item::saved(...)`) from a service provider: it stops the
  model's own `booted()` hooks from registering. Use a model trait (`FlushesSiteCache`).
- Cached model data (homepage, nav) needs its class in `config/cache.php`
  `serializable_classes`; bump the cache key (`home_page_v2`) when its shape changes.
- `<x-icon>` belongs to blade-icons (Filament); site icons are `<x-ui-icon>`.
