#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${DEPLOY_DIR}/.." && pwd)"
DATA_ROOT="${PROJECT_ROOT}/peoplecine_data"
ENV_TEMPLATE="${DEPLOY_DIR}/env.production.example"
ENV_FILE="${DATA_ROOT}/config/peoplecine.env"
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.production.yml"

SQLITE_SOURCE_PATH="${SQLITE_SOURCE_PATH:-}"
LEGACY_UPLOADS_SOURCE="${LEGACY_UPLOADS_SOURCE:-}"
LEGACY_ICONS_SOURCE="${LEGACY_ICONS_SOURCE:-}"

if ! command -v docker >/dev/null 2>&1; then
    echo "docker is required but was not found in PATH." >&2
    exit 1
fi

mkdir -p \
    "${DATA_ROOT}/app/bootstrap-cache" \
    "${DATA_ROOT}/app/storage/app/private" \
    "${DATA_ROOT}/app/storage/framework/cache" \
    "${DATA_ROOT}/app/storage/framework/sessions" \
    "${DATA_ROOT}/app/storage/framework/views" \
    "${DATA_ROOT}/app/storage/logs" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/config" \
    "${DATA_ROOT}/legacy/wboard/icons" \
    "${DATA_ROOT}/legacy/wboard/uploads" \
    "${DATA_ROOT}/mariadb/data"

if [[ ! -f "${ENV_FILE}" ]]; then
    cp "${ENV_TEMPLATE}" "${ENV_FILE}"
fi

if grep -q '^APP_KEY=$' "${ENV_FILE}"; then
    if command -v openssl >/dev/null 2>&1; then
        APP_KEY_VALUE="base64:$(openssl rand -base64 32 | tr -d '\n')"
    else
        APP_KEY_VALUE="base64:$(head -c 32 /dev/urandom | base64 | tr -d '\n')"
    fi
    sed -i.bak "s#^APP_KEY=.*#APP_KEY=${APP_KEY_VALUE}#" "${ENV_FILE}"
    rm -f "${ENV_FILE}.bak"
fi

if [[ -z "${SQLITE_SOURCE_PATH}" ]]; then
    SQLITE_SOURCE_PATH="${PROJECT_ROOT}/peoplecine-modern.sqlite"
fi

if [[ ! -f "${SQLITE_SOURCE_PATH}" ]]; then
    echo "SQLite source file not found: ${SQLITE_SOURCE_PATH}" >&2
    echo "Set SQLITE_SOURCE_PATH to the exported PeopleCine SQLite file before running this installer." >&2
    exit 1
fi

ROOT_DB_PASSWORD="$(grep '^MARIADB_ROOT_PASSWORD=' "${ENV_FILE}" | cut -d'=' -f2-)"

cp "${SQLITE_SOURCE_PATH}" "${DATA_ROOT}/bootstrap/sqlite/peoplecine-modern.sqlite"

if [[ -n "${LEGACY_UPLOADS_SOURCE}" ]]; then
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete "${LEGACY_UPLOADS_SOURCE}/" "${DATA_ROOT}/legacy/wboard/uploads/"
    else
        rm -rf "${DATA_ROOT}/legacy/wboard/uploads"
        mkdir -p "${DATA_ROOT}/legacy/wboard/uploads"
        cp -a "${LEGACY_UPLOADS_SOURCE}/." "${DATA_ROOT}/legacy/wboard/uploads/"
    fi
fi

if [[ -n "${LEGACY_ICONS_SOURCE}" ]]; then
    if command -v rsync >/dev/null 2>&1; then
        rsync -a --delete "${LEGACY_ICONS_SOURCE}/" "${DATA_ROOT}/legacy/wboard/icons/"
    else
        rm -rf "${DATA_ROOT}/legacy/wboard/icons"
        mkdir -p "${DATA_ROOT}/legacy/wboard/icons"
        cp -a "${LEGACY_ICONS_SOURCE}/." "${DATA_ROOT}/legacy/wboard/icons/"
    fi
fi

chmod -R 775 \
    "${DATA_ROOT}/app/bootstrap-cache" \
    "${DATA_ROOT}/app/storage" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/legacy"

chmod -R 770 "${DATA_ROOT}/mariadb"

if [[ "$(id -u)" -eq 0 ]]; then
    chown -R 33:33 \
        "${DATA_ROOT}/app/bootstrap-cache" \
        "${DATA_ROOT}/app/storage" \
        "${DATA_ROOT}/bootstrap/sqlite" \
        "${DATA_ROOT}/legacy" || true
    chown -R 999:999 "${DATA_ROOT}/mariadb" || true
fi

docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d --build db

for _ in $(seq 1 60); do
    if docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T db mariadb-admin ping -h 127.0.0.1 -uroot "-p${ROOT_DB_PASSWORD}" --silent >/dev/null 2>&1; then
        break
    fi
    sleep 2
done

docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d --build app

docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T app php artisan peoplecine:migrate-sqlite-to-mariadb \
    --fresh \
    --source=/var/www/peoplecine_data/bootstrap/sqlite/peoplecine-modern.sqlite \
    --source-connection=sqlite_source \
    --target-connection=mariadb \
    --chunk=1000

docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T app php artisan optimize:clear

echo
echo "PeopleCine is ready."
echo "Website:  http://localhost:7000"
echo "MariaDB:  127.0.0.1:7010"
echo "DB user:  ohm"
echo "DB pass:  2001Serenity"
