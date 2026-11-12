#!/usr/bin/env bash
# Nightly backup of every DigitalDownloads site on this server (run by cron as root).
#
#   sudo dd-backup            # back up every site now
#   sudo dd-backup example.com
#
# Per site, in $BACKUP_DIR/<domain>/<date>/:
#   database.sql.gz  - full MySQL dump (consistent snapshot, no table locks)
#   uploads.tar.gz   - storage/app (public images, hosted files, ticket attachments)
#   env              - the site's .env. It holds APP_KEY, without which the encrypted
#                      settings (captcha, SMS, Google secrets) can't be read: keep it with the dump.
#
# Keeps $BACKUP_KEEP_DAYS days. If BACKUP_RCLONE_REMOTE is set in /etc/digitaldownloads/config
# (e.g. "b2:my-bucket/digitaldownloads"), each night's backups are also copied there with rclone.
set -euo pipefail
cd "$(dirname "$0")"
. ./common.sh

BACKUP_DIR="${BACKUP_DIR:-/var/backups/digitaldownloads}"
BACKUP_KEEP_DAYS="${BACKUP_KEEP_DAYS:-14}"
BACKUP_RCLONE_REMOTE="${BACKUP_RCLONE_REMOTE:-}"

require_root
ensure_dirs
umask 077
mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

# KEY's value from a .env file, or the given default when it's missing or empty.
env_value() {
    local v
    v="$(grep -E "^$2=" "$1" | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//')"
    echo "${v:-${3:-}}"
}

# Note: `set -e` does not apply inside a function called as `f || ...`, so every step is checked by hand.
backup_site() {
    local domain="$1" app="$WEB_ROOT/$1/app" dest cnf db
    [ -f "$app/.env" ] || { log "[$domain] no $app/.env"; return 1; }

    dest="$BACKUP_DIR/$domain/$(date +%F)"
    mkdir -p "$dest" || return 1
    db="$(env_value "$app/.env" DB_DATABASE)"
    [ -n "$db" ] || { log "[$domain] DB_DATABASE is not set"; return 1; }

    # Credentials go in a temporary option file so the password never shows in `ps`.
    cnf="$(mktemp)" || return 1
    printf '[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' \
        "$(env_value "$app/.env" DB_USERNAME)" "$(env_value "$app/.env" DB_PASSWORD)" \
        "$(env_value "$app/.env" DB_HOST 127.0.0.1)" "$(env_value "$app/.env" DB_PORT 3306)" > "$cnf"

    if ! mysqldump --defaults-extra-file="$cnf" --single-transaction --quick --routines --no-tablespaces "$db" \
            | gzip -6 > "$dest/database.sql.gz.part"; then
        rm -f "$cnf" "$dest/database.sql.gz.part"
        log "[$domain] mysqldump failed"
        return 1
    fi
    rm -f "$cnf"
    mv "$dest/database.sql.gz.part" "$dest/database.sql.gz" || return 1

    # Resized image copies are rebuilt on demand, so they're left out.
    tar -czf "$dest/uploads.tar.gz" -C "$app/storage" --exclude='app/public/resized' app \
        || { log "[$domain] archiving uploads failed"; return 1; }
    cp "$app/.env" "$dest/env" || return 1

    log "[$domain] backup saved to $dest ($(du -sh "$dest" | cut -f1))"
}

DOMAINS=("$@")
[ ${#DOMAINS[@]} -gt 0 ] || mapfile -t DOMAINS < <(grep -vE '^\s*(#|$)' "$SITES_FILE")

failed=0
for domain in "${DOMAINS[@]}"; do
    backup_site "$domain" || { failed=1; log "[$domain] BACKUP FAILED"; }
done

# Old backups out, then the off-server copy.
find "$BACKUP_DIR" -mindepth 2 -maxdepth 2 -type d -mtime +"$BACKUP_KEEP_DAYS" -exec rm -rf {} +

if [ -n "$BACKUP_RCLONE_REMOTE" ]; then
    if command -v rclone >/dev/null; then
        rclone copy "$BACKUP_DIR" "$BACKUP_RCLONE_REMOTE" --max-age 26h \
            && log "Backups copied to $BACKUP_RCLONE_REMOTE" \
            || { failed=1; log "ERROR: rclone copy to $BACKUP_RCLONE_REMOTE failed"; }
    else
        failed=1; log "ERROR: BACKUP_RCLONE_REMOTE is set but rclone is not installed"
    fi
fi

exit $failed
