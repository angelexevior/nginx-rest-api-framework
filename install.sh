#!/usr/bin/env bash
#
# Interactive installer for nginx-rest-api-framework.
# Configures the database, imports the sample data, and wires up
# nginx or Apache. Nothing destructive happens without confirmation.

set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="$ROOT_DIR/config.php"
SQL_FILE="$ROOT_DIR/countries.sql"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

c_reset='\033[0m'; c_bold='\033[1m'; c_green='\033[32m'; c_yellow='\033[33m'; c_red='\033[31m'; c_blue='\033[34m'

info()  { printf "${c_blue}==>${c_reset} %s\n" "$1"; }
ok()    { printf "${c_green}✔${c_reset} %s\n" "$1"; }
warn()  { printf "${c_yellow}!${c_reset} %s\n" "$1"; }
fail()  { printf "${c_red}✘ %s${c_reset}\n" "$1"; }
title() { printf "\n${c_bold}%s${c_reset}\n" "$1"; }

# ask "Question" "default" -> echoes answer
ask() {
    local prompt="$1" default="${2:-}" answer
    if [ -n "$default" ]; then
        read -r -p "$prompt [$default]: " answer || true
        echo "${answer:-$default}"
    else
        read -r -p "$prompt: " answer || true
        echo "$answer"
    fi
}

ask_secret() {
    local prompt="$1" answer
    read -r -s -p "$prompt: " answer || true
    echo >&2
    echo "$answer"
}

# confirm "Question" [default y|n] -> return 0 for yes, 1 for no
confirm() {
    local prompt="$1" default="${2:-y}" answer
    local hint="y/N"; [ "$default" = "y" ] && hint="Y/n"
    read -r -p "$prompt [$hint]: " answer || true
    answer="${answer:-$default}"
    case "$answer" in
        [Yy]*) return 0 ;;
        *) return 1 ;;
    esac
}

require_cmd() {
    command -v "$1" >/dev/null 2>&1
}

# ---------------------------------------------------------------------------
title "nginx-rest-api-framework installer"
# ---------------------------------------------------------------------------

echo "This will walk you through:"
echo "  1) checking prerequisites"
echo "  2) configuring the database connection"
echo "  3) importing the sample dataset"
echo "  4) wiring up nginx or Apache for pretty URLs"
echo
confirm "Continue?" y || { echo "Aborted."; exit 0; }

# ---------------------------------------------------------------------------
title "1. Checking prerequisites"
# ---------------------------------------------------------------------------

missing=0

if require_cmd php; then
    ok "PHP found: $(php -r 'echo PHP_VERSION;')"
    if php -m | grep -qi '^mysqli$'; then
        ok "PHP mysqli extension is enabled"
    else
        fail "PHP mysqli extension is NOT enabled. This framework requires it."
        missing=1
    fi
else
    fail "PHP CLI not found. Install PHP 7.4+ before continuing."
    missing=1
fi

MYSQL_BIN=""
for candidate in mysql mariadb; do
    if require_cmd "$candidate"; then
        MYSQL_BIN="$candidate"
        break
    fi
done

if [ -n "$MYSQL_BIN" ]; then
    ok "MySQL client found ($MYSQL_BIN)"
else
    warn "No 'mysql' or 'mariadb' client found on PATH."
    warn "You can still configure credentials now, but you'll need to import countries.sql manually later."
fi

if [ "$missing" -eq 1 ]; then
    if ! confirm "Prerequisites are missing. Continue anyway?" n; then
        exit 1
    fi
fi

# ---------------------------------------------------------------------------
title "2. Database configuration"
# ---------------------------------------------------------------------------

DB_HOST=$(ask "Database host" "127.0.0.1")
DB_PORT=$(ask "Database port" "3306")
DB_NAME=$(ask "Database name" "api_framework")
DB_USER=$(ask "Database username" "root")
DB_PASS=$(ask_secret "Database password (leave empty if none)")

test_db_connection() {
    php -r '
        $host=$argv[1]; $port=(int)$argv[2]; $user=$argv[3]; $pass=$argv[4]; $db=$argv[5];
        mysqli_report(MYSQLI_REPORT_OFF);
        $c = @mysqli_connect($host, $user, $pass, "", $port);
        if (!$c) { fwrite(STDERR, mysqli_connect_error() . "\n"); exit(1); }
        $exists = mysqli_query($c, "SHOW DATABASES LIKE \"" . mysqli_real_escape_string($c, $db) . "\"");
        exit(mysqli_num_rows($exists) > 0 ? 0 : 2);
    ' "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS" "$DB_NAME"
}

while true; do
    info "Testing connection to $DB_HOST:$DB_PORT as $DB_USER..."
    test_db_connection
    status=$?
    if [ "$status" -eq 0 ]; then
        ok "Connected, and database '$DB_NAME' already exists."
        break
    elif [ "$status" -eq 2 ]; then
        ok "Connected. Database '$DB_NAME' does not exist yet."
        if confirm "Create database '$DB_NAME' now?" y; then
            if php -r '
                $host=$argv[1]; $port=(int)$argv[2]; $user=$argv[3]; $pass=$argv[4]; $db=$argv[5];
                $c = @mysqli_connect($host, $user, $pass, "", $port);
                if (!$c) exit(1);
                exit(mysqli_query($c, "CREATE DATABASE `" . str_replace("`","``",$db) . "` CHARACTER SET utf8mb4") ? 0 : 1);
            ' "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS" "$DB_NAME"; then
                ok "Database created."
            else
                fail "Could not create database. Check that the user has CREATE privileges."
                confirm "Try different credentials?" y && continue || exit 1
            fi
        else
            warn "Continuing without creating the database. Import will fail until it exists."
        fi
        break
    else
        fail "Could not connect with those credentials."
        confirm "Try again?" y && continue || exit 1
    fi
done

# Write config.php
if [ -f "$CONFIG_FILE" ] && grep -q "db_hostname = '[^']" "$CONFIG_FILE" 2>/dev/null; then
    warn "config.php already has non-empty credentials."
    if ! confirm "Overwrite it?" n; then
        info "Keeping existing config.php."
    else
        cp "$CONFIG_FILE" "$CONFIG_FILE.bak.$(date +%s)"
        ok "Backed up existing config."
        WRITE_CONFIG=1
    fi
else
    WRITE_CONFIG=1
fi

if [ "${WRITE_CONFIG:-0}" -eq 1 ]; then
    cat > "$CONFIG_FILE" <<PHP
<?php

class Config {

    //Database configuration
    public \$db_hostname = '${DB_HOST}';
    public \$db_port = '${DB_PORT}';
    public \$db_username = '${DB_USER}';
    public \$db_password = '${DB_PASS}';
    public \$db_name = '${DB_NAME}';

    //The time a session should be left alive (In seconds)
    //This is for security reasons. Users will be automatically logged out after the specified seconds of inactivity
    public \$session_timeout = '10800';

    //Time settings
    public \$timezone = 'UTC';
}
PHP
    ok "Wrote config.php"
fi

# ---------------------------------------------------------------------------
title "3. Import sample data"
# ---------------------------------------------------------------------------

table_has_data() {
    php -r '
        $host=$argv[1]; $port=(int)$argv[2]; $user=$argv[3]; $pass=$argv[4]; $db=$argv[5];
        mysqli_report(MYSQLI_REPORT_OFF);
        $c = @mysqli_connect($host, $user, $pass, $db, $port);
        if (!$c) exit(2);
        $res = @mysqli_query($c, "SELECT COUNT(*) c FROM countries");
        if (!$res) exit(1);
        $row = mysqli_fetch_assoc($res);
        exit(((int)$row["c"]) > 0 ? 0 : 1);
    ' "$DB_HOST" "$DB_PORT" "$DB_USER" "$DB_PASS" "$DB_NAME"
}

if table_has_data; then
    warn "The 'countries' table already has data."
    IMPORT=0
    confirm "Re-import countries.sql anyway?" n && IMPORT=1
else
    IMPORT=1
fi

if [ "$IMPORT" -eq 1 ]; then
    if [ -n "$MYSQL_BIN" ]; then
        info "Importing $SQL_FILE..."
        if MYSQL_PWD="$DB_PASS" "$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$SQL_FILE"; then
            ok "Sample data imported."
        else
            fail "Import failed. You can retry manually with:"
            echo "  $MYSQL_BIN -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME < countries.sql"
        fi
    else
        warn "No mysql client available. Import manually with:"
        echo "  mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p $DB_NAME < countries.sql"
    fi
fi

# ---------------------------------------------------------------------------
title "4. Web server setup"
# ---------------------------------------------------------------------------

echo "Which web server are you using?"
echo "  1) nginx"
echo "  2) Apache"
echo "  3) Skip - I'll configure it myself"
WEBSERVER=$(ask "Enter 1, 2 or 3" "1")

DOMAIN=""
case "$WEBSERVER" in
1)
    DOMAIN=$(ask "Domain / server_name for this vhost" "api.local")
    WEBROOT=$(ask "Web root (where this project lives on the server)" "$ROOT_DIR")

    candidates=()
    for dir in /etc/nginx/sites-available /etc/nginx/conf.d /usr/local/etc/nginx/servers /opt/homebrew/etc/nginx/servers; do
        [ -d "$dir" ] && candidates+=("$dir")
    done

    if [ "${#candidates[@]}" -gt 0 ]; then
        info "Found these nginx config directories on this machine:"
        i=1
        for dir in "${candidates[@]}"; do
            echo "  $i) $dir"
            i=$((i + 1))
        done
        echo "  0) None of these / enter a custom path"
        choice=$(ask "Choose one" "1")
        if [ "$choice" = "0" ]; then
            NGINX_DIR=$(ask "Path to your nginx site config directory")
        else
            NGINX_DIR="${candidates[$((choice - 1))]}"
        fi
    else
        warn "Couldn't find a standard nginx config directory on this machine."
        NGINX_DIR=$(ask "Where should the site config file be written? (full path to the directory)")
    fi

    NGINX_CONF_PATH="$NGINX_DIR/${DOMAIN}.conf"
    NGINX_CONF_PATH=$(ask "Config file to write" "$NGINX_CONF_PATH")

    NGINX_CONF_CONTENT=$(cat <<CONF
server {
    listen 80;
    server_name ${DOMAIN};
    root ${WEBROOT};
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }
}
CONF
)
    echo
    info "Generated nginx config:"
    echo "----------------------------------------"
    echo "$NGINX_CONF_CONTENT"
    echo "----------------------------------------"

    FASTCGI_SOCK=$(ask "Path to your PHP-FPM socket (or host:port, e.g. 127.0.0.1:9000)" "/var/run/php/php-fpm.sock")
    if [[ "$FASTCGI_SOCK" == *:* && "$FASTCGI_SOCK" != /* ]]; then
        NGINX_CONF_CONTENT="${NGINX_CONF_CONTENT/fastcgi_pass unix:\/var\/run\/php\/php-fpm.sock;/fastcgi_pass ${FASTCGI_SOCK};}"
    else
        NGINX_CONF_CONTENT="${NGINX_CONF_CONTENT/\/var\/run\/php\/php-fpm.sock/${FASTCGI_SOCK}}"
    fi

    if confirm "Write this config to $NGINX_CONF_PATH?" y; then
        WRITER="tee"
        SUDO=""
        if [ ! -w "$(dirname "$NGINX_CONF_PATH")" ]; then
            SUDO="sudo"
            warn "You don't have write access there; will use sudo."
        fi
        if $SUDO sh -c "cat > '$NGINX_CONF_PATH'" <<<"$NGINX_CONF_CONTENT"; then
            ok "Wrote $NGINX_CONF_PATH"
        else
            fail "Could not write config. You may need to run this script with more privileges."
        fi

        ENABLED_DIR="$(dirname "$NGINX_DIR")/sites-enabled"
        if [ -d "$ENABLED_DIR" ] && [ ! -e "$ENABLED_DIR/$(basename "$NGINX_CONF_PATH")" ]; then
            if confirm "Symlink it into $ENABLED_DIR?" y; then
                $SUDO ln -s "$NGINX_CONF_PATH" "$ENABLED_DIR/$(basename "$NGINX_CONF_PATH")" \
                    && ok "Symlinked into sites-enabled." \
                    || fail "Could not create symlink."
            fi
        fi

        if require_cmd nginx; then
            if confirm "Run 'nginx -t' to validate the config?" y; then
                $SUDO nginx -t && ok "Config is valid." || fail "nginx reported a config error - fix before reloading."
            fi
            if confirm "Reload nginx now?" n; then
                $SUDO nginx -s reload && ok "nginx reloaded." || fail "Could not reload nginx."
            fi
        else
            warn "nginx binary not found on PATH here - reload it on your server manually."
        fi
    else
        info "Skipped writing the config. Reference: $ROOT_DIR/nginx-config.txt"
    fi
    ;;
2)
    DOMAIN=$(ask "Domain / ServerName for this vhost" "api.local")
    WEBROOT=$(ask "Web root (where this project lives on the server)" "$ROOT_DIR")

    candidates=()
    for dir in /etc/apache2/sites-available /etc/httpd/conf.d; do
        [ -d "$dir" ] && candidates+=("$dir")
    done

    if [ "${#candidates[@]}" -gt 0 ]; then
        info "Found these Apache config directories:"
        i=1
        for dir in "${candidates[@]}"; do
            echo "  $i) $dir"
            i=$((i + 1))
        done
        echo "  0) None of these / enter a custom path"
        choice=$(ask "Choose one" "1")
        if [ "$choice" = "0" ]; then
            APACHE_DIR=$(ask "Path to your Apache site config directory")
        else
            APACHE_DIR="${candidates[$((choice - 1))]}"
        fi
    else
        warn "Couldn't find a standard Apache config directory on this machine."
        APACHE_DIR=$(ask "Where should the vhost config file be written? (full path to the directory)")
    fi

    APACHE_CONF_PATH="$APACHE_DIR/${DOMAIN}.conf"
    APACHE_CONF_PATH=$(ask "Config file to write" "$APACHE_CONF_PATH")

    APACHE_CONF_CONTENT=$(cat <<CONF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    DocumentRoot ${WEBROOT}

    <Directory ${WEBROOT}>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
CONF
)
    echo
    info "Generated Apache vhost (uses the included .htaccess for rewrites):"
    echo "----------------------------------------"
    echo "$APACHE_CONF_CONTENT"
    echo "----------------------------------------"
    warn "Make sure mod_rewrite is enabled (a2enmod rewrite)."

    if confirm "Write this config to $APACHE_CONF_PATH?" y; then
        SUDO=""
        if [ ! -w "$(dirname "$APACHE_CONF_PATH")" ]; then
            SUDO="sudo"
            warn "You don't have write access there; will use sudo."
        fi
        if $SUDO sh -c "cat > '$APACHE_CONF_PATH'" <<<"$APACHE_CONF_CONTENT"; then
            ok "Wrote $APACHE_CONF_PATH"
        else
            fail "Could not write config."
        fi

        if require_cmd a2ensite; then
            if confirm "Enable this site with a2ensite?" y; then
                $SUDO a2ensite "$(basename "$APACHE_CONF_PATH")" && ok "Site enabled." || fail "a2ensite failed."
            fi
        fi

        if require_cmd apachectl || require_cmd apache2ctl; then
            APACTL=$(require_cmd apachectl && echo apachectl || echo apache2ctl)
            if confirm "Test and reload Apache now?" n; then
                $SUDO "$APACTL" configtest && $SUDO "$APACTL" graceful && ok "Apache reloaded." || fail "Reload failed."
            fi
        fi
    else
        info "Skipped writing the config. Reference: $ROOT_DIR/.htaccess"
    fi
    ;;
*)
    info "Skipping web server setup."
    info "nginx: use $ROOT_DIR/nginx-config.txt inside your server block."
    info "Apache: the included .htaccess already has the rewrite rule; enable mod_rewrite and AllowOverride All."
    ;;
esac

# ---------------------------------------------------------------------------
title "Done"
# ---------------------------------------------------------------------------

echo "Setup summary:"
echo "  Database  : $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
echo "  Config    : $CONFIG_FILE"
[ -n "$DOMAIN" ] && echo "  Vhost     : $DOMAIN"
echo
echo "Try it out:"
if [ -n "$DOMAIN" ]; then
    echo "  curl http://$DOMAIN/platform/countries"
else
    echo "  curl http://YOURADDRESS/platform/countries"
fi
echo
ok "Installation steps complete."
