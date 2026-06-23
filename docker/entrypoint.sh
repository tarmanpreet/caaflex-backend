#!/bin/sh
set -e

if [ -f /var/www/html/.env ]; then
    set -a
    . /var/www/html/.env
    set +a
fi

echo "==> [entrypoint] Pulizia bootstrap cache (pacchetti dev rimossi)..."
rm -f /var/www/html/bootstrap/cache/packages.php \
       /var/www/html/bootstrap/cache/services.php \
       /var/www/html/bootstrap/cache/config.php

if [ "${APP_ENV}" = "local" ]; then
    echo "==> [entrypoint] Installazione dipendenze Composer (dev)..."
    composer install --no-interaction --no-scripts --prefer-dist \
        --working-dir=/var/www/html
elif [ ! -d /var/www/html/vendor ]; then
    echo "==> [entrypoint] vendor/ non trovato, installazione dipendenze Composer (no-dev)..."
    composer install --no-dev --no-interaction --no-scripts --prefer-dist \
        --working-dir=/var/www/html
fi

APP_KEY_TRIMMED=$(echo "${APP_KEY}" | tr -d '[:space:]')
if [ -z "${APP_KEY_TRIMMED}" ]; then
    if [ "${APP_ENV}" = "local" ]; then
        echo "==> [entrypoint] APP_KEY mancante, generazione automatica..."
        php /var/www/html/artisan key:generate --force --ansi
    else
        echo "ERROR: APP_KEY non impostata. Impostare APP_KEY nelle variabili d'ambiente prima di avviare." >&2
        exit 1
    fi
fi

echo "==> [entrypoint] Rigenero package discovery dal vendor installato..."
php /var/www/html/artisan package:discover --ansi

# ── Passport encryption keys ─────────────────────────────────────────────────
# Generate RSA key files on first boot if not provided via env vars.
# Must run BEFORE config:cache so the keys exist when Passport loads them.
# --force is safe here: we only invoke it when the key files are missing.
if [ -z "${PASSPORT_PRIVATE_KEY}" ] && [ -z "${PASSPORT_PUBLIC_KEY}" ]; then
    if [ ! -f /var/www/html/storage/oauth-private.key ] || [ ! -f /var/www/html/storage/oauth-public.key ]; then
        echo "==> [entrypoint] Generazione chiavi Passport..."
        php /var/www/html/artisan passport:keys --force --no-interaction
    fi
fi

if [ "${APP_ENV}" != "local" ]; then
    echo "==> [entrypoint] Ottimizzazione configurazione Laravel..."
    php /var/www/html/artisan config:cache

    echo "==> [entrypoint] Ottimizzazione route..."
    php /var/www/html/artisan route:cache

    echo "==> [entrypoint] Compilazione view Blade..."
    php /var/www/html/artisan view:cache
fi

echo "==> [entrypoint] Creazione symlink storage..."
php /var/www/html/artisan storage:link --force

echo "==> [entrypoint] Migrazione database..."
if [ "${RUN_MIGRATIONS}" = "1" ]; then
    php /var/www/html/artisan migrate --force --no-interaction
fi

echo "==> [entrypoint] Seed ruoli, permessi e procedure..."
if [ "${RUN_SEEDERS}" = "1" ]; then
    php /var/www/html/artisan db:seed --class=DatabaseSeeder --force --no-interaction
fi

echo "==> [entrypoint] Avvio supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisord.conf
