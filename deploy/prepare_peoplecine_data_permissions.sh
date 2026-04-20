#!/usr/bin/env bash
set -euo pipefail

DATA_ROOT="${1:-/home/ohm/peoplecine_modern/peoplecine_data}"
APP_OWNER="${2:-ohm:ohm}"
WEB_OWNER="${3:-33:33}"
DB_OWNER="${4:-999:999}"

if [[ ! -d "${DATA_ROOT}" ]]; then
    echo "peoplecine_data directory not found: ${DATA_ROOT}" >&2
    echo "Usage: $0 [/absolute/path/to/peoplecine_data] [app_owner:group] [web_uid:gid] [db_uid:gid]" >&2
    exit 1
fi

if command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
else
    SUDO=""
fi

echo "Preparing PeopleCine data directory permissions"
echo "Data root : ${DATA_ROOT}"
echo "App owner : ${APP_OWNER}"
echo "Web owner : ${WEB_OWNER}"
echo "DB owner  : ${DB_OWNER}"

# Expected mounted folders used by the Docker deployment.
${SUDO} mkdir -p \
    "${DATA_ROOT}/app/code" \
    "${DATA_ROOT}/app/code/bootstrap/cache" \
    "${DATA_ROOT}/app/code/storage/app/private" \
    "${DATA_ROOT}/app/code/storage/framework/cache" \
    "${DATA_ROOT}/app/code/storage/framework/sessions" \
    "${DATA_ROOT}/app/code/storage/framework/views" \
    "${DATA_ROOT}/app/code/storage/logs" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/config" \
    "${DATA_ROOT}/legacy/wboard" \
    "${DATA_ROOT}/mariadb/data"

# Default everything to the deployment user first so backups and maintenance
# remain easy from the host OS.
${SUDO} chown -R "${APP_OWNER}" "${DATA_ROOT}"

# General read/traverse permissions for the mounted tree.
${SUDO} find "${DATA_ROOT}" -type d -exec chmod 755 {} +
${SUDO} find "${DATA_ROOT}" -type f -exec chmod 644 {} +

# Writable areas for the Laravel web container (www-data in the PHP image).
for path in \
    "${DATA_ROOT}/app/code/bootstrap/cache" \
    "${DATA_ROOT}/app/code/storage" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/legacy"; do
    if [[ -e "${path}" ]]; then
        ${SUDO} chown -R "${WEB_OWNER}" "${path}"
        ${SUDO} find "${path}" -type d -exec chmod 775 {} +
        ${SUDO} find "${path}" -type f -exec chmod 664 {} +
    fi
done

# MariaDB data must be writable by the MariaDB container user.
if [[ -e "${DATA_ROOT}/mariadb" ]]; then
    ${SUDO} chown -R "${DB_OWNER}" "${DATA_ROOT}/mariadb"
    ${SUDO} find "${DATA_ROOT}/mariadb" -type d -exec chmod 770 {} +
    ${SUDO} find "${DATA_ROOT}/mariadb" -type f -exec chmod 660 {} +
fi

echo
echo "Done. Review the key folders below:"
for path in \
    "${DATA_ROOT}/app/code" \
    "${DATA_ROOT}/app/code/storage" \
    "${DATA_ROOT}/bootstrap/sqlite" \
    "${DATA_ROOT}/legacy/wboard" \
    "${DATA_ROOT}/mariadb/data"; do
    if [[ -e "${path}" ]]; then
        ls -ld "${path}"
    else
        echo "WARN: ${path} not found"
    fi
done

echo
echo "Example:"
echo "  ./deploy/prepare_peoplecine_data_permissions.sh ${DATA_ROOT}"
