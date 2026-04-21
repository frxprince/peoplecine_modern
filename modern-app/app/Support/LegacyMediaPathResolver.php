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

        $configuredRoots = config('peoplecine.legacy_wboard_roots');
        $roots = array_values(array_filter(array_unique([
            config('peoplecine.legacy_wboard_root'),
            ...(is_array($configuredRoots) ? $configuredRoots : []),
        ])));

        return $this->resolveWithinRoots($path, $roots);
    }

    /**
     * @param  array<int, string|null>  $roots
     */
    public function resolveWithinRoots(?string $legacyPath, array $roots): ?string
    {
        $path = trim((string) $legacyPath);

        if ($path === '') {
            return null;
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
            $resolved = $this->resolveCaseInsensitivePath($root, $normalized);

            if ($resolved === null) {
                return null;
            }
        }

        if (! Str::startsWith(Str::lower($resolved), Str::lower($root.DIRECTORY_SEPARATOR))
            && Str::lower($resolved) !== Str::lower($root)) {
            return null;
        }

        return is_file($resolved) ? $resolved : null;
    }

    private function resolveCaseInsensitivePath(string $root, string $normalized): ?string
    {
        $segments = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $normalized), 'strlen'));
        $current = $root;

        foreach ($segments as $segment) {
            if (! is_dir($current)) {
                return null;
            }

            $entries = scandir($current);

            if ($entries === false) {
                return null;
            }

            $matchedEntry = null;

            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                if (strcasecmp($entry, $segment) !== 0) {
                    continue;
                }

                $matchedEntry = $entry;

                if ($entry === $segment) {
                    break;
                }
            }

            if ($matchedEntry === null) {
                return null;
            }

            $current .= DIRECTORY_SEPARATOR.$matchedEntry;
        }

        $resolved = realpath($current);

        return $resolved === false ? null : $resolved;
    }
}
