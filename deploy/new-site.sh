#!/usr/bin/env bash
# Add a new domain to this server, running the shared DigitalDownloads code.
#
#   sudo bash deploy/new-site.sh example.com
#
# Creates: the code checkout, its own MySQL database + user, .env, Apache vhost,
# scheduler cron and queue worker. Then run the installer it prints at the end.
# Each domain is fully separate: own database, own admin, own content.
set -euo pipefail
cd "$(dirname "$0")"
. ./common.sh

DOMAIN="${1:-}"
[ -n "$DOMAIN" ] || die "Usage: sudo bash deploy/new-site.sh example.com"
DOMAIN="$(echo "$DOMAIN" | tr 'A-Z' 'a-z')"
valid_domain "$DOMAIN" || die "\"$DOMAIN\" doesn't look like a domain name."

require_root
ensure_dirs

SITE_DIR="$WEB_ROOT/$DOMAIN"
APP="$SITE_DIR/app"
[ -e "$APP" ] && die "$APP already exists. Pick another domain or remove it first."

# ---- Prerequisites ---------------------------------------------------------
for ext in mbstring xml curl pdo_mysql zip intl gd bcmath fileinfo; do
    "$PHP_BIN" -m | grep -qi "^$ext$" || die "PHP extension '$ext' is missing (apt install php-${ext/pdo_mysql/mysql})."
done
command -v node >/dev/null || die "Node.js is not installed."
command -v mysql >/dev/null || die "MySQL client is not installed."
command -v clamdscan >/dev/null || command -v clamscan >/dev/null \
    || log "WARNING: ClamAV is not installed, so files uploaded to the site will not be virus-scanned. Install with: apt install clamav clamav-daemon"

if ! command -v composer >/dev/null; then
    log "Installing Composer (checksum verified)"
    EXPECTED="$(curl -fsSL https://composer.github.io/installer.sig)"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    ACTUAL="$("$PHP_BIN" -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
    [ "$EXPECTED" = "$ACTUAL" ] || { rm -f /tmp/composer-setup.php; die "Composer installer checksum mismatch."; }
    "$PHP_BIN" /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

# ---- Code ------------------------------------------------------------------
log "[$DOMAIN] Cloning $REPO_URL ($BRANCH)"
mkdir -p "$SITE_DIR"
chown "$RUN_USER":"$RUN_USER" "$SITE_DIR"
as_user git clone --branch "$BRANCH" "$REPO_URL" "$APP"

# ---- Database --------------------------------------------------------------
DB_NAME="dd_$(echo "$DOMAIN" | tr '.-' '__' | cut -c1-60)"
DB_USER="$(echo "dd_${DOMAIN%%.*}" | tr '.-' '__' | cut -c1-28)_$(openssl rand -hex 1)"
DB_PASS="$(openssl rand -base64 30 | tr -d '/+=' | cut -c1-32)"
log "[$DOMAIN] Creating database $DB_NAME"
mysql <<SQL
CREATE DATABASE \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
SQL

# ---- Environment -----------------------------------------------------------
# .env is outside public/ (Apache only serves app/public) and readable only by the site user.
cat > "$APP/.env" <<ENV
APP_NAME="$DOMAIN"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASS

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
CACHE_PREFIX=${DB_NAME}_
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@$DOMAIN"
MAIL_FROM_NAME="\${APP_NAME}"
ENV
chown "$RUN_USER":"$RUN_USER" "$APP/.env"
chmod 600 "$APP/.env"

log "[$DOMAIN] Installing dependencies and building assets"
build_site "$APP"
as_user "$PHP_BIN" artisan key:generate --force
chmod -R ug+rwX "$APP/storage" "$APP/bootstrap/cache"

# ---- Apache ----------------------------------------------------------------
VHOST="/etc/apache2/sites-available/dd-$DOMAIN.conf"
cat > "$VHOST" <<APACHE
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $APP/public

    <Directory $APP/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/dd-$DOMAIN-error.log
    CustomLog \${APACHE_LOG_DIR}/dd-$DOMAIN-access.log combined
</VirtualHost>
APACHE
a2enmod rewrite headers >/dev/null
a2ensite "dd-$DOMAIN" >/dev/null
apachectl configtest
systemctl reload apache2

# ---- Scheduler and queue worker -------------------------------------------
echo "* * * * * $RUN_USER cd $APP && $PHP_BIN artisan schedule:run >> /dev/null 2>&1" > "/etc/cron.d/dd-$(echo "$DOMAIN" | tr '.' '-')"

UNIT_SRC="$(pwd)/digitaldownloads-queue@.service"
if [ ! -f /etc/systemd/system/digitaldownloads-queue@.service ] || ! cmp -s "$UNIT_SRC" /etc/systemd/system/digitaldownloads-queue@.service; then
    cp "$UNIT_SRC" /etc/systemd/system/digitaldownloads-queue@.service
    systemctl daemon-reload
fi
systemctl enable --now "digitaldownloads-queue@$DOMAIN" >/dev/null

grep -qxF "$DOMAIN" "$SITES_FILE" || echo "$DOMAIN" >> "$SITES_FILE"
install_scripts "$APP/deploy"
if [ ! -f /etc/cron.d/digitaldownloads-autodeploy ]; then
    echo "*/15 * * * * root /usr/local/bin/dd-deploy >> $LOG_FILE 2>&1" > /etc/cron.d/digitaldownloads-autodeploy
fi

log "[$DOMAIN] Ready for install"
cat <<NEXT

Done. Next steps for $DOMAIN:

  1. DNS: point $DOMAIN (and www) at this server in Cloudflare.
  2. SSL: issue the certificate the same way as your other sites, e.g.
       certbot --apache -d $DOMAIN -d www.$DOMAIN
     (or certbot dns-cloudflare after adding the zone to the API token).
  3. Install the site and create its admin:
       cd $APP && sudo -u $RUN_USER php artisan site:install

Database: $DB_NAME (credentials are in $APP/.env only).
NEXT
