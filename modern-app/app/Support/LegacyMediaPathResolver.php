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

        $roots = config('peoplecine.legacy_wboard_roots');

        if (! is_array($roots) || $roots === []) {
            $roots = [config('peoplecine.legacy_wboard_root')];
        }

        $normalized = ltrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);

        foreach ($roots as $rootPath) {
            $resolved = $this->resolveAgainstRoot((string) $rootPath, $normalized);

            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private function resolveAgainstRoot(string $rootPath, string $normalized): ?string
    {
        $root = realpath($rootPath);

        if ($root === false) {
            return null;
        }

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
