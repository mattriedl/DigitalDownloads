#!/usr/bin/env bash
# Update every DigitalDownloads site on this server to the latest code on GitHub.
#
#   sudo bash deploy/deploy-all.sh            # only sites behind origin/main
#   sudo bash deploy/deploy-all.sh --force    # rebuild every site
#   sudo bash deploy/deploy-all.sh example.com  # one site
#
# Safe to run from cron every few minutes: a site with no new commits is skipped.
# If any step fails, that site is rolled back to its previous commit, brought back
# online, and the failure is logged; the other sites still deploy.
set -uo pipefail
cd "$(dirname "$0")"
. ./common.sh

FORCE=0
ONLY=""
for arg in "$@"; do
    case "$arg" in
        --force) FORCE=1 ;;
        *) ONLY="$arg" ;;
    esac
done

require_root
ensure_dirs

# One deploy at a time.
exec 9>/var/lock/digitaldownloads-deploy.lock
flock -n 9 || { echo "Another deploy is running."; exit 0; }

deploy_site() {
    local domain="$1"
    local app="$WEB_ROOT/$domain/app"
    [ -d "$app/.git" ] || { log "[$domain] SKIP: $app is not a checkout"; return 0; }
    cd "$app"

    as_user git fetch --quiet origin "$BRANCH" || { log "[$domain] FAIL: git fetch"; return 1; }
    local current target
    current="$(as_user git rev-parse HEAD)"
    target="$(as_user git rev-parse "origin/$BRANCH")"

    if [ "$current" = "$target" ] && [ "$FORCE" -eq 0 ]; then
        return 0
    fi

    log "[$domain] Deploying ${current:0:7} -> ${target:0:7}"
    as_user "$PHP_BIN" artisan down --retry=30 --render="errors::503" >/dev/null 2>&1 || true

    if as_user git reset --hard --quiet "$target" \
        && build_site "$app" \
        && as_user "$PHP_BIN" artisan migrate --force \
        && as_user "$PHP_BIN" artisan db:seed --class=DefaultsSeeder --force \
        && as_user "$PHP_BIN" artisan optimize; then
        as_user "$PHP_BIN" artisan queue:restart >/dev/null 2>&1 || true
        as_user "$PHP_BIN" artisan up >/dev/null
        log "[$domain] OK ${target:0:7}"
        return 0
    fi

    log "[$domain] FAIL at ${target:0:7}; rolling code back to ${current:0:7}"
    as_user git reset --hard --quiet "$current"
    build_site "$app" >/dev/null 2>&1
    as_user "$PHP_BIN" artisan optimize >/dev/null 2>&1
    as_user "$PHP_BIN" artisan up >/dev/null 2>&1
    return 1
}

failed=0
while IFS= read -r domain; do
    domain="${domain%%#*}"; domain="$(echo "$domain" | xargs)"
    [ -z "$domain" ] && continue
    [ -n "$ONLY" ] && [ "$domain" != "$ONLY" ] && continue
    deploy_site "$domain" || failed=$((failed + 1))
done < "$SITES_FILE"

echo "$(date -Is) failed=$failed" > /var/log/digitaldownloads-deploy.status

# Refresh the server's copy of these scripts from the first site's checkout
# (atomic rename, so a running copy is never edited in place).
first="$(grep -v '^\s*#' "$SITES_FILE" | grep -m1 .)"
if [ -n "$first" ] && [ -d "$WEB_ROOT/$first/app/deploy" ]; then
    install_scripts "$WEB_ROOT/$first/app/deploy"
fi

exit $(( failed > 0 ))
