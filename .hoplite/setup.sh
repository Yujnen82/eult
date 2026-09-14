#!/usr/bin/env bash
# Setup sandbox Hoplite untuk E-ULT (CodeIgniter 4 + MariaDB lokal).
# Idempotent: aman dijalankan ulang.
set -euo pipefail
cd "$(dirname "$0")/.."

export DEBIAN_FRONTEND=noninteractive

if ! command -v php >/dev/null 2>&1; then
  apt-get update -qq
  apt-get install -y -qq --no-install-recommends \
    php8.3-cli php8.3-mbstring php8.3-intl php8.3-xml php8.3-curl php8.3-gd \
    php8.3-sqlite3 php8.3-mysql php8.3-zip unzip
fi

if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
  php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

composer install --no-interaction --prefer-dist --no-progress

if ! command -v mysqld_safe >/dev/null 2>&1; then
  apt-get update -qq
  apt-get install -y -qq --no-install-recommends mariadb-server mariadb-client
fi

# MariaDB lokal (data uji saja) untuk Preview dan suite `Database`.
bash .hoplite/db.sh start
mysql < tests/_support/Database/skema_uji.sql
bash .hoplite/db.sh seed

# .env sandbox: kredensial DB lokal dan HTTP polos (tanpa TLS di sandbox).
if [ ! -f .env ]; then
  cat > .env <<'ENV'
CI_ENVIRONMENT = development
app.baseURL = http://127.0.0.1:3000/
app.forceGlobalSecureRequests = false
cookie.secure = false
session.cookieSecure = false
EULT_DB_HOST = 127.0.0.1
EULT_DB_NAME = db_newtiket
EULT_DB_USER = eult
EULT_DB_PASS = eult-uji
EULT_DBULT_HOST = 127.0.0.1
EULT_DBULT_NAME = db_ult
EULT_DBULT_USER = eult
EULT_DBULT_PASS = eult-uji
EULT_OSM_URL = http://127.0.0.1:9/
ENV
fi

mkdir -p writable/uploads/chat writable/uploads/ticketing writable/uploads/qrcode writable/session writable/cache writable/logs
echo "setup selesai"
