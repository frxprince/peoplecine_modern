#!/usr/bin/env bash
set -euo pipefail

TARGET_ROOT="${1:-/home/ohm/peoplecine_modern/old_website/wboard}"
TARGET_OWNER="${2:-ohm:ohm}"

if [[ ! -d "${TARGET_ROOT}" ]]; then
    echo "Legacy wboard directory not found: ${TARGET_ROOT}" >&2
    echo "Usage: $0 [/absolute/path/to/old_website/wboard] [owner:group]" >&2
    exit 1
fi

echo "Preparing legacy PeopleCine media permissions"
echo "Target: ${TARGET_ROOT}"
echo "Owner : ${TARGET_OWNER}"

if command -v sudo >/dev/null 2>&1; then
    SUDO="sudo"
else
    SUDO=""
fi

# Keep ownership with the deployment user unless the caller overrides it.
${SUDO} chown -R "${TARGET_OWNER}" "${TARGET_ROOT}"

# Directories must be traversable, files only need read access for the
# read-only Docker bind mount used by the web container.
${SUDO} find "${TARGET_ROOT}" -type d -exec chmod 755 {} +
${SUDO} find "${TARGET_ROOT}" -type f -exec chmod 644 {} +

# A few common legacy media folders benefit from explicit checks so the
# operator sees whether the expected upload trees are present.
for subdir in uploads icons picpost; do
    if [[ -d "${TARGET_ROOT}/${subdir}" ]]; then
        echo "OK: ${TARGET_ROOT}/${subdir}"
    else
        echo "WARN: ${TARGET_ROOT}/${subdir} not found"
    fi
done

echo
echo "Done. The legacy wboard tree is ready for read-only Docker mounting."
echo "Example:"
echo "  export LEGACY_WBOARD_FALLBACK_SOURCE=\"${TARGET_ROOT}\""
