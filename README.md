# DigitalDownloads

A download portal script (FileHippo / UpLab style): categories, item pages with external
download links, a countdown-and-ad download page, reviews, comments, a blog, support
tickets, ads everywhere, and optional paid memberships.

**One codebase, many sites.** Each domain is a separate install of this repo with its own
database, admin login, content, branding, ads and SEO settings. A push to `main` updates the
code on every site; content never mixes between sites.

Stack: Laravel 13, PHP 8.3+ (server runs 8.5), MySQL 8, Blade + Tailwind, Vite.

## Status

| Phase | What | State |
| --- | --- | --- |
| 1 | Project setup, installer, multi-site deploy scripts | Done |
| 2 | Database schema, models, defaults, demo content | Done |
| 3 | Admin panel (Filament, at /admin) | Done |
| 4 | Public front end (dark, ad-heavy theme, countdown download page) | Done |
| 5 | Accounts, Google sign-in, reviews/comments, dead-link checker, virus scanning | Done |
| 6 | Advertising: BannerPromote in every zone, ad stats | Done |
| 7 | Support tickets for members, captcha, SMS | Next |
| 8 | SEO and performance | |
| 9 | Testing, hardening, launch | |
| 10 | Memberships (gateways already built in, switched off) | Later |

## Adding a site on the server

```bash
# First time only: get the scripts onto the server
git clone https://github.com/mattriedl/DigitalDownloads.git /tmp/dd && sudo bash /tmp/dd/deploy/new-site.sh example.com
# After the first site, the scripts are installed as commands:
sudo dd-new-site another-site.com
```

`new-site.sh` creates the checkout in `/var/www/<domain>/app`, a dedicated MySQL database
and user, a locked-down `.env`, the Apache vhost, the scheduler cron and a queue worker,
and registers the domain in `/etc/digitaldownloads/sites`. Then:

1. Point DNS at the server (Cloudflare) and issue SSL with certbot.
2. Run the installer: `cd /var/www/<domain>/app && sudo -u www-data php artisan site:install`

## Server requirements per site

- PHP 8.3+ with mbstring, xml, curl, mysql, zip, intl, gd, bcmath; MySQL 8; Node 22; Apache with mod_rewrite.
- ClamAV for virus-scanning files uploaded to the site: `apt install clamav clamav-daemon`
  (without it, hosted files are marked "unscanned" and still served).
- Outgoing email: set `MAIL_*` in each site's `.env` (e.g. Amazon SES or Postmark SMTP).
- Google sign-in: Admin → Site settings → Integrations, with redirect URL `https://<domain>/auth/google/callback`.
- Scheduled jobs run from the per-site cron (`schedule:run`): nightly dead-link check at 02:15.

## Deploying updates

Push to `main`. Every 15 minutes `dd-deploy` (cron) updates each site that is behind:
maintenance mode → pull → composer/npm build → migrate → add any new defaults → cache → back up.
A site that fails is rolled back to its previous commit and the error is logged in
`/var/log/digitaldownloads-deploy.log`.

```bash
sudo dd-deploy               # sites with new commits
sudo dd-deploy --force       # rebuild all
sudo dd-deploy example.com   # one site
```

## Local development

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan site:install --demo
composer run dev
php artisan test
```

## Where things live

- `app/Models` — catalog (`Category`, `Item`, `ItemLink`), engagement, ads, content, support, payments
- `app/Support/Settings.php` + `setting('key')` helper — every per-site option (name, logo,
  colors, SEO, ad and countdown options), cached
- `database/seeders/DefaultsSeeder.php` — ad zones, default settings, gateways (off), email
  templates, starter policy pages; safe to re-run, never overwrites admin changes
- `database/seeders/DemoSeeder.php` — sample content for staging only
- `app/Console/Commands/InstallSite.php` — `php artisan site:install`
- `deploy/` — server scripts
