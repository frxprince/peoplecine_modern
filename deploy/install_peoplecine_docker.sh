#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${DEPLOY_DIR}/.." && pwd)"
DATA_ROOT="${PROJECT_ROOT}/peoplecine_data"
ENV_TEMPLATE="${DEPLOY_DIR}/env.production.example"
ENV_FILE="${DATA_ROOT}/config/peoplecine.env"
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.production.yml"

SQLITE_SOURCE_PATH="${SQLITE_SOURCE_PATH:-}"
LEGACY_WBOARD_SOURCE="${LEGACY_WBOARD_SOURCE:-}"
LEGACY_UPLOADS_SOURCE="${LEGACY_UPLOADS_SOURCE:-}"
LEGACY_ICONS_SOURCE="${LEGACY_ICONS_SOURCE:-}"

abort_path_conflict() {
    local path="$1"
    local expected="$2"

    echo "Path conflict: ${path} exists but is not a ${expected}." >&2
    exit 1
}

ensure_directory() {
    local path="$1"

    if [[ -e "${path}" && ! -d "${path}" ]]; then
        abort_path_conflict "${path}" "directory"
    fi

    mkdir -p "${path}"
}

ensure_parent_directory_for_file() {
    local path="$1"
    local parent

    parent="$(dirname "${path}")"
    ensure_directory "${parent}"

    if [[ -d "${path}" ]]; then
        abort_path_conflict "${path}" "file"
    fi
}

copy_file_if_missing() {
    local source="$1"
    local target="$2"

    ensure_parent_directory_for_file "${target}"

    if [[ -e "${target}" ]]; then
        echo "Skipping existing file: ${target}"
        return 0
    fi

    cp "${source}" "${target}"
}

copy_tree_preserving_existing() {
    local source="$1"
    local target="$2"

    ensure_directory "${target}"

    if command -v rsync >/dev/null 2>&1; then
        rsync -a --ignore-existing "${source}/" "${target}/"
    else
        (
            cd "${source}"
            find . -mindepth 1 \( -type d -o -type f \) -print0
        ) | while IFS= read -r -d '' entry; do
            local relative
            local sourcePath
            local targetPath

            relative="${entry#./}"
            sourcePath="${source}/${relative}"
            targetPath="${target}/${relative}"

            if [[ -d "${sourcePath}" ]]; then
                ensure_directory "${targetPath}"
                continue
            fi

            ensure_parent_directory_for_file "${targetPath}"

            if [[ -e "${targetPath}" ]]; then
                echo "Skipping existing file: ${targetPath}"
                continue
            fi

            cp -p "${sourcePath}" "${targetPath}"
        done
    fi
}

if ! command -v docker >/dev/null 2>&1; then
    echo "docker is required but was not found in PATH." >&2
    exit 1
fi

for directory in \
    "${DATA_ROOT}/app/code" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/config" \
    "${DATA_ROOT}/legacy/wboard" \
    "${DATA_ROOT}/mariadb/data"; do
    ensure_directory "${directory}"
done

if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete \
        --exclude '.env' \
        --exclude 'node_modules' \
        --exclude 'storage/logs/*' \
        --exclude 'storage/framework/cache/*' \
        --exclude 'storage/framework/sessions/*' \
        --exclude 'storage/framework/views/*' \
        --exclude 'storage/app/private/*' \
        --exclude 'database/*.sqlite' \
        --exclude 'database/*.sqlite-journal' \
        "${PROJECT_ROOT}/modern-app/" "${DATA_ROOT}/app/code/"
else
    if [[ -e "${DATA_ROOT}/app/code" && ! -d "${DATA_ROOT}/app/code" ]]; then
        abort_path_conflict "${DATA_ROOT}/app/code" "directory"
    fi

    rm -rf "${DATA_ROOT}/app/code"
    ensure_directory "${DATA_ROOT}/app/code"
    cp -a "${PROJECT_ROOT}/modern-app/." "${DATA_ROOT}/app/code/"
    rm -f "${DATA_ROOT}/app/code/.env"
    rm -f "${DATA_ROOT}/app/code/database/"*.sqlite "${DATA_ROOT}/app/code/database/"*.sqlite-journal 2>/dev/null || true
fi

for directory in \
    "${DATA_ROOT}/app/code/bootstrap/cache" \
    "${DATA_ROOT}/app/code/storage/app/private" \
    "${DATA_ROOT}/app/code/storage/framework/cache" \
    "${DATA_ROOT}/app/code/storage/framework/sessions" \
    "${DATA_ROOT}/app/code/storage/framework/views" \
    "${DATA_ROOT}/app/code/storage/logs"; do
    ensure_directory "${directory}"
done

if [[ ! -f "${ENV_FILE}" ]]; then
    ensure_parent_directory_for_file "${ENV_FILE}"
    cp "${ENV_TEMPLATE}" "${ENV_FILE}"
elif [[ -d "${ENV_FILE}" ]]; then
    abort_path_conflict "${ENV_FILE}" "file"
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

copy_file_if_missing "${SQLITE_SOURCE_PATH}" "${DATA_ROOT}/bootstrap/sqlite/peoplecine-modern.sqlite"

if [[ -n "${LEGACY_WBOARD_SOURCE}" ]]; then
    if [[ ! -d "${LEGACY_WBOARD_SOURCE}" ]]; then
        echo "Legacy wboard source directory not found: ${LEGACY_WBOARD_SOURCE}" >&2
        exit 1
    fi

    copy_tree_preserving_existing "${LEGACY_WBOARD_SOURCE}" "${DATA_ROOT}/legacy/wboard"
else
    if [[ -n "${LEGACY_UPLOADS_SOURCE}" ]]; then
        copy_tree_preserving_existing "${LEGACY_UPLOADS_SOURCE}" "${DATA_ROOT}/legacy/wboard/uploads"
    fi

    if [[ -n "${LEGACY_ICONS_SOURCE}" ]]; then
        copy_tree_preserving_existing "${LEGACY_ICONS_SOURCE}" "${DATA_ROOT}/legacy/wboard/icons"
    fi
fi

chmod -R 775 \
    "${DATA_ROOT}/app/code/bootstrap/cache" \
    "${DATA_ROOT}/app/code/storage" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/legacy"

chmod -R 770 "${DATA_ROOT}/mariadb"

if [[ "$(id -u)" -eq 0 ]]; then
    chown -R 33:33 \
        "${DATA_ROOT}/app/code/bootstrap/cache" \
        "${DATA_ROOT}/app/code/storage" \
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
