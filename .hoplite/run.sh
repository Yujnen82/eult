#!/usr/bin/env bash
# Preview E-ULT: MariaDB lokal + server dev CodeIgniter di :3000.
set -euo pipefail
cd "$(dirname "$0")/.."
bash .hoplite/db.sh start
# Server built-in PHP multi-worker agar request paralel (aset, AJAX) tidak saling memblokir.
export PHP_CLI_SERVER_WORKERS=4
exec php spark serve --host 0.0.0.0 --port 3000
