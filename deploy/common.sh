#!/usr/bin/env bash
# Shared settings for new-site.sh and deploy-all.sh. Override any of these in
# /etc/digitaldownloads/config (plain KEY=value lines) without editing the repo.

REPO_URL="${REPO_URL:-https://github.com/mattriedl/DigitalDownloads.git}"
BRANCH="${BRANCH:-main}"
WEB_ROOT="${WEB_ROOT:-/var/www}"          # each site lives in $WEB_ROOT/<domain>/app
CONF_DIR="${CONF_DIR:-/etc/digitaldownloads}"
SITES_FILE="${SITES_FILE:-$CONF_DIR/sites}"  # one domain per line
RUN_USER="${RUN_USER:-www-data}"          # owns the code, runs artisan/composer/npm
RUN_HOME="${RUN_HOME:-/var/cache/digitaldownloads}"  # HOME for composer/npm caches
LOG_FILE="${LOG_FILE:-/var/log/digitaldownloads-deploy.log}"
PHP_BIN="${PHP_BIN:-php}"

[ -f "$CONF_DIR/config" ] && . "$CONF_DIR/config"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG_FILE"; }
die() { log "ERROR: $*"; exit 1; }

# Run a command as the site user with writable caches.
as_user() {
    sudo -u "$RUN_USER" -H env HOME="$RUN_HOME" COMPOSER_HOME="$RUN_HOME/composer" \
        npm_config_cache="$RUN_HOME/npm" "$@"
}

require_root() { [ "$(id -u)" -eq 0 ] || die "Run this as root (sudo)."; }

ensure_dirs() {
    mkdir -p "$CONF_DIR" "$RUN_HOME"
    touch "$SITES_FILE" "$LOG_FILE"
    chown -R "$RUN_USER":"$RUN_USER" "$RUN_HOME"
}

valid_domain() { [[ "$1" =~ ^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$ ]]; }

# Keep a stable copy of these scripts outside any site checkout, so cron never
# runs a file that a deploy is rewriting. Exposes `dd-deploy`, `dd-new-site` and `dd-backup`.
SCRIPT_HOME="${SCRIPT_HOME:-/usr/local/lib/digitaldownloads}"
install_scripts() {
    local src="$1"
    mkdir -p "$SCRIPT_HOME"
    for f in common.sh deploy-all.sh new-site.sh backup-all.sh digitaldownloads-queue@.service; do
        [ -f "$src/$f" ] || continue
        cp "$src/$f" "$SCRIPT_HOME/.$f.tmp" && mv -f "$SCRIPT_HOME/.$f.tmp" "$SCRIPT_HOME/$f"
    done
    printf '#!/usr/bin/env bash\nexec bash %s/deploy-all.sh "$@"\n' "$SCRIPT_HOME" > /usr/local/bin/dd-deploy
    printf '#!/usr/bin/env bash\nexec bash %s/new-site.sh "$@"\n' "$SCRIPT_HOME" > /usr/local/bin/dd-new-site
    printf '#!/usr/bin/env bash\nexec bash %s/backup-all.sh "$@"\n' "$SCRIPT_HOME" > /usr/local/bin/dd-backup
    chmod 755 /usr/local/bin/dd-deploy /usr/local/bin/dd-new-site /usr/local/bin/dd-backup
    # Nightly backups of every site (added once; existing servers get it on their next deploy).
    [ -f /etc/cron.d/digitaldownloads-backup ] \
        || echo "40 3 * * * root /usr/local/bin/dd-backup >> $LOG_FILE 2>&1" > /etc/cron.d/digitaldownloads-backup
}

# Build steps shared by first install and every deploy.
build_site() {
    local app="$1"
    cd "$app" || die "Missing $app"
    as_user composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
    as_user npm ci --no-audit --no-fund --loglevel=error
    as_user npm run build
    as_user "$PHP_BIN" artisan storage:link >/dev/null 2>&1 || true
}
