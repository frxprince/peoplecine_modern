#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_ROOT="${PROJECT_ROOT}/peoplecine_data"
APP_SOURCE="${PROJECT_ROOT}/modern-app"
APP_RUNTIME="${DATA_ROOT}/app/code"
ENV_FILE="${DATA_ROOT}/config/peoplecine.env"
COMPOSE_FILE="${PROJECT_ROOT}/deploy/docker-compose.production.yml"

abort() {
    echo "Error: $*" >&2
    exit 1
}

require_file() {
    local path="$1"
    [[ -f "${path}" ]] || abort "Required file not found: ${path}"
}

ensure_directory() {
    local path="$1"

    if [[ -e "${path}" && ! -d "${path}" ]]; then
        abort "Path exists but is not a directory: ${path}"
    fi

    mkdir -p "${path}"
}

sync_app_code() {
    echo "Syncing application code into peoplecine_data/app/code ..."

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
            "${APP_SOURCE}/" "${APP_RUNTIME}/"
    else
        abort "rsync is required for rebuild.sh"
    fi
}

ensure_runtime_directories() {
    for directory in \
        "${APP_RUNTIME}" \
        "${APP_RUNTIME}/bootstrap/cache" \
        "${APP_RUNTIME}/storage/app/private" \
        "${APP_RUNTIME}/storage/framework/cache" \
        "${APP_RUNTIME}/storage/framework/sessions" \
        "${APP_RUNTIME}/storage/framework/views" \
        "${APP_RUNTIME}/storage/logs"; do
        ensure_directory "${directory}"
    done
}

install_php_dependencies_if_needed() {
    local runtimeComposerLock="${APP_RUNTIME}/composer.lock"
    local runtimeAutoload="${APP_RUNTIME}/vendor/autoload.php"

    if [[ ! -f "${runtimeAutoload}" || "${runtimeComposerLock}" -nt "${runtimeAutoload}" ]]; then
        echo "Installing PHP dependencies into mounted runtime ..."
        docker run --rm \
            -v "${APP_RUNTIME}:/app" \
            -w /app \
            composer:2 install \
            --no-dev \
            --prefer-dist \
            --no-interaction \
            --optimize-autoloader
    else
        echo "Composer dependencies already up to date."
    fi
}

fix_permissions() {
    chmod -R 775 \
        "${APP_RUNTIME}/bootstrap/cache" \
        "${APP_RUNTIME}/storage" || true

    if [[ "$(id -u)" -eq 0 ]]; then
        chown -R 33:33 \
            "${APP_RUNTIME}/bootstrap/cache" \
            "${APP_RUNTIME}/storage" || true
    fi
}

bring_up_stack() {
    echo "Starting containers on the latest code ..."
    docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" up -d --build db app
}

run_laravel_maintenance() {
    echo "Running Laravel migrations ..."
    docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T app php artisan migrate --force

    echo "Clearing Laravel caches ..."
    docker compose -f "${COMPOSE_FILE}" --env-file "${ENV_FILE}" exec -T app php artisan optimize:clear
}

main() {
    command -v docker >/dev/null 2>&1 || abort "docker is required but was not found in PATH"
    require_file "${COMPOSE_FILE}"
    require_file "${ENV_FILE}"
    require_file "${APP_SOURCE}/composer.json"
    require_file "${APP_SOURCE}/composer.lock"

    ensure_runtime_directories
    sync_app_code
    install_php_dependencies_if_needed
    fix_permissions
    bring_up_stack
    run_laravel_maintenance

    echo
    echo "PeopleCine rebuild complete."
    echo "Website: http://localhost:7000"
    echo "Next step: hard refresh the browser if CSS or JS changed."
}

main "$@"
