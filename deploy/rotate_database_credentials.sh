#!/usr/bin/env bash
set -euo pipefail

DEPLOY_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${DEPLOY_DIR}/.." && pwd)"
ENV_FILE="${PROJECT_ROOT}/peoplecine_data/config/peoplecine.env"
COMPOSE_FILE="${DEPLOY_DIR}/docker-compose.production.yml"

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "Environment file not found: ${ENV_FILE}" >&2
    exit 1
fi

generate_secret() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -hex 24
        return
    fi

    od -An -N24 -tx1 /dev/urandom | tr -d ' \n'
}

read_env_value() {
    local key="$1"
    grep -m1 "^${key}=" "${ENV_FILE}" | cut -d'=' -f2-
}

upsert_env_value() {
    local file="$1"
    local key="$2"
    local value="$3"

    if grep -q "^${key}=" "${file}"; then
        sed -i "s#^${key}=.*#${key}=${value}#" "${file}"
    else
        printf '%s=%s\n' "${key}" "${value}" >> "${file}"
    fi
}

DB_NAME="$(read_env_value MARIADB_DATABASE)"
DB_USER="$(read_env_value MARIADB_USER)"

if [[ ! "${DB_NAME}" =~ ^[A-Za-z0-9_]+$ || ! "${DB_USER}" =~ ^[A-Za-z0-9_]+$ ]]; then
    echo "Database name or username contains unsupported characters." >&2
    exit 1
fi

NEW_APPLICATION_PASSWORD="$(generate_secret)"
NEW_ROOT_PASSWORD="$(generate_secret)"
TEMP_ENV="$(mktemp "${ENV_FILE}.XXXXXX")"
BACKUP_ENV="${ENV_FILE}.before-security-rotation-$(date +%Y%m%d-%H%M%S)"

trap 'rm -f "${TEMP_ENV}"' EXIT

cp "${ENV_FILE}" "${TEMP_ENV}"
upsert_env_value "${TEMP_ENV}" DB_PASSWORD "${NEW_APPLICATION_PASSWORD}"
upsert_env_value "${TEMP_ENV}" MARIADB_PASSWORD "${NEW_APPLICATION_PASSWORD}"
upsert_env_value "${TEMP_ENV}" MARIADB_ROOT_PASSWORD "${NEW_ROOT_PASSWORD}"
upsert_env_value "${TEMP_ENV}" MARIADB_BIND_ADDRESS 127.0.0.1
upsert_env_value "${TEMP_ENV}" SESSION_ENCRYPT true
upsert_env_value "${TEMP_ENV}" SESSION_SECURE_COOKIE true
upsert_env_value "${TEMP_ENV}" TRUSTED_PROXIES '127.0.0.1,::1,172.16.0.0/12'

printf '%s\n' \
    "SET PASSWORD = PASSWORD('${NEW_ROOT_PASSWORD}');" \
    "DROP USER IF EXISTS 'root'@'%';" \
    "ALTER USER '${DB_USER}'@'%' IDENTIFIED BY '${NEW_APPLICATION_PASSWORD}';" \
    "REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${DB_USER}'@'%';" \
    "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';" \
    "FLUSH PRIVILEGES;" |
    docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T db \
        sh -lc 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD"'

cp "${ENV_FILE}" "${BACKUP_ENV}"
mv "${TEMP_ENV}" "${ENV_FILE}"
trap - EXIT

docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d --force-recreate db app

echo "Database credentials rotated and remote root access removed."
echo "Updated environment: ${ENV_FILE}"
echo "Previous environment backup: ${BACKUP_ENV}"
