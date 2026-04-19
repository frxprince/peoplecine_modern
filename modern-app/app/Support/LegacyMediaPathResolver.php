<?php

namespace App\Support;

use Illuminate\Support\Str;

class LegacyMediaPathResolver
{
    public function resolve(?string $legacyPath): ?string
    {
        $path = trim((string) $legacyPath);

        if ($path === '') {
            return null;
        }

        $root = realpath((string) config('peoplecine.legacy_wboard_root'));

        if ($root === false) {
            return null;
        }

        $normalized = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
        $candidate = $root.DIRECTORY_SEPARATOR.$normalized;
        $resolved = realpath($candidate);

        if ($resolved === false) {
            return null;
        }

        if (! Str::startsWith(Str::lower($resolved), Str::lower($root.DIRECTORY_SEPARATOR))
            && Str::lower($resolved) !== Str::lower($root)) {
            return null;
        }

        return is_file($resolved) ? $resolved : null;
    }
}
