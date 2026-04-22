<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class BannerManager
{
    /**
     * @return array<int, array{id:string,path:string,url:string,alt:string,sort_order:int,section:string,version:?int}>
     */
    public function sidebarBanners(): array
    {
        return $this->itemsForSection('sidebar');
    }

    /**
     * @return array<int, array{id:string,path:string,url:string,alt:string,sort_order:int,section:string,version:?int}>
     */
    public function landingBanners(): array
    {
        return $this->itemsForSection('landing');
    }

    /**
     * @return array<string, array<int, array{id:string,path:string,url:string,alt:string,sort_order:int,section:string,version:?int}>>
     */
    public function groupedBanners(): array
    {
        return [
            'sidebar' => $this->sidebarBanners(),
            'landing' => $this->landingBanners(),
        ];
    }

    public function add(string $section, UploadedFile $image, ?string $alt = null): void
    {
        $section = $this->normalizeSection($section);
        $config = $this->loadConfig();
        $items = $config[$section] ?? [];

        $extension = Str::lower($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg');
        $fileName = $section.'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(8)).'.'.$extension;
        $relativePath = trim(config('peoplecine.banner_public_prefix', 'images/managed-banners'), '/').'/'.$section.'/'.$fileName;
        $targetDirectory = $this->sectionRoot($section);

        File::ensureDirectoryExists($targetDirectory);
        $image->move($targetDirectory, $fileName);

        $items[] = [
            'id' => (string) Str::uuid(),
            'path' => $relativePath,
            'alt' => $this->normalizeAlt($alt, pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)),
            'sort_order' => $this->nextSortOrder($items),
        ];

        $config[$section] = $items;
        $this->writeConfig($config);
    }

    public function update(string $section, string $bannerId, int $sortOrder, ?string $alt = null): void
    {
        $section = $this->normalizeSection($section);
        $config = $this->loadConfig();
        $items = $config[$section] ?? [];

        foreach ($items as &$item) {
            if (($item['id'] ?? '') !== $bannerId) {
                continue;
            }

            $item['sort_order'] = $sortOrder;
            $item['alt'] = $this->normalizeAlt($alt, $item['alt'] ?? 'Banner');
            $config[$section] = $items;
            $this->writeConfig($config);

            return;
        }

        throw new RuntimeException("Banner not found for section [{$section}] and id [{$bannerId}].");
    }

    public function delete(string $section, string $bannerId): void
    {
        $section = $this->normalizeSection($section);
        $config = $this->loadConfig();
        $items = $config[$section] ?? [];
        $remaining = [];

        foreach ($items as $item) {
            if (($item['id'] ?? '') === $bannerId) {
                $this->deleteManagedFileIfNeeded((string) ($item['path'] ?? ''));
                continue;
            }

            $remaining[] = $item;
        }

        $config[$section] = $remaining;
        $this->writeConfig($config);
    }

    /**
     * @return array<int, array{id:string,path:string,url:string,alt:string,sort_order:int,section:string,version:?int}>
     */
    private function itemsForSection(string $section): array
    {
        $section = $this->normalizeSection($section);
        $items = $this->loadConfig()[$section] ?? [];

        $normalized = array_map(function (array $item, int $index) use ($section): array {
            $path = trim((string) ($item['path'] ?? ''), '/');

            return [
                'id' => (string) ($item['id'] ?? $section.'-banner-'.$index),
                'path' => $path,
                'url' => $this->versionedAssetUrl($path),
                'alt' => $this->normalizeAlt($item['alt'] ?? null, ucfirst($section).' banner '.($index + 1)),
                'sort_order' => (int) ($item['sort_order'] ?? (($index + 1) * 10)),
                'section' => $section,
                'version' => $this->fileVersion($path),
            ];
        }, array_values($items), array_keys(array_values($items)));

        usort($normalized, static function (array $left, array $right): int {
            return [$left['sort_order'], $left['id']] <=> [$right['sort_order'], $right['id']];
        });

        return $normalized;
    }

    /**
     * @return array<string, array<int, array{id:string,path:string,alt:string,sort_order:int}>>
     */
    private function loadConfig(): array
    {
        $path = (string) config('peoplecine.banner_config_path');

        if (! is_file($path)) {
            return $this->defaultConfig();
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return $this->defaultConfig();
        }

        return [
            'sidebar' => array_values(is_array($decoded['sidebar'] ?? null) ? $decoded['sidebar'] : []),
            'landing' => array_values(is_array($decoded['landing'] ?? null) ? $decoded['landing'] : []),
        ];
    }

    /**
     * @param array<string, array<int, array{id:string,path:string,alt:string,sort_order:int}>> $config
     */
    private function writeConfig(array $config): void
    {
        $path = (string) config('peoplecine.banner_config_path');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents(
            $path,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    /**
     * @return array<string, array<int, array{id:string,path:string,alt:string,sort_order:int}>>
     */
    private function defaultConfig(): array
    {
        return [
            'sidebar' => [
                [
                    'id' => 'sidebar-default-1',
                    'path' => 'images/legacy/add_newbanner_small.png',
                    'alt' => 'PeopleCine banner',
                    'sort_order' => 10,
                ],
                [
                    'id' => 'sidebar-default-2',
                    'path' => 'images/legacy/add_side_banner_mid.jpg',
                    'alt' => 'PeopleCine side banner',
                    'sort_order' => 20,
                ],
                [
                    'id' => 'sidebar-default-3',
                    'path' => 'images/legacy/bigfilm_leftbanner.png',
                    'alt' => 'Big film banner',
                    'sort_order' => 30,
                ],
            ],
            'landing' => [
                ['id' => 'landing-default-1', 'path' => 'images/landingpagebanner/landingpagebanner-01.png', 'alt' => 'Landing banner 1', 'sort_order' => 10],
                ['id' => 'landing-default-2', 'path' => 'images/landingpagebanner/landingpagebanner-02.jpg', 'alt' => 'Landing banner 2', 'sort_order' => 20],
                ['id' => 'landing-default-3', 'path' => 'images/landingpagebanner/landingpagebanner-03.jpg', 'alt' => 'Landing banner 3', 'sort_order' => 30],
                ['id' => 'landing-default-4', 'path' => 'images/landingpagebanner/landingpagebanner-04.jpg', 'alt' => 'Landing banner 4', 'sort_order' => 40],
                ['id' => 'landing-default-5', 'path' => 'images/landingpagebanner/landingpagebanner-05.jpg', 'alt' => 'Landing banner 5', 'sort_order' => 50],
                ['id' => 'landing-default-6', 'path' => 'images/landingpagebanner/landingpagebanner-06.jpg', 'alt' => 'Landing banner 6', 'sort_order' => 60],
                ['id' => 'landing-default-7', 'path' => 'images/landingpagebanner/landingpagebanner-07.jpg', 'alt' => 'Landing banner 7', 'sort_order' => 70],
                ['id' => 'landing-default-8', 'path' => 'images/landingpagebanner/landingpagebanner-08.jpg', 'alt' => 'Landing banner 8', 'sort_order' => 80],
                ['id' => 'landing-default-9', 'path' => 'images/landingpagebanner/landingpagebanner-09.jpg', 'alt' => 'Landing banner 9', 'sort_order' => 90],
            ],
        ];
    }

    private function normalizeSection(string $section): string
    {
        $section = trim(Str::lower($section));

        if (! in_array($section, ['sidebar', 'landing'], true)) {
            throw new RuntimeException("Unsupported banner section [{$section}].");
        }

        return $section;
    }

    private function normalizeAlt(?string $alt, string $fallback): string
    {
        $value = trim((string) $alt);

        return $value !== '' ? $value : trim($fallback);
    }

    /**
     * @param array<int, array{id:string,path:string,alt:string,sort_order:int}> $items
     */
    private function nextSortOrder(array $items): int
    {
        $max = 0;

        foreach ($items as $item) {
            $max = max($max, (int) ($item['sort_order'] ?? 0));
        }

        return $max + 10;
    }

    private function sectionRoot(string $section): string
    {
        return rtrim((string) config('peoplecine.banner_public_root'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$section;
    }

    private function absolutePath(string $path): string
    {
        $relativePath = trim($path, '/');
        $prefix = trim((string) config('peoplecine.banner_public_prefix', 'images/managed-banners'), '/');

        if ($prefix !== '' && Str::startsWith($relativePath, $prefix.'/')) {
            $suffix = substr($relativePath, strlen($prefix) + 1);

            return rtrim((string) config('peoplecine.banner_public_root'), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $suffix);
        }

        return public_path($relativePath);
    }

    private function deleteManagedFileIfNeeded(string $path): void
    {
        $relativePath = trim($path, '/');
        $prefix = trim((string) config('peoplecine.banner_public_prefix', 'images/managed-banners'), '/');

        if ($prefix === '' || ! Str::startsWith($relativePath, $prefix.'/')) {
            return;
        }

        $absolutePath = $this->absolutePath($relativePath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function fileVersion(string $path): ?int
    {
        $absolutePath = $this->absolutePath($path);

        return is_file($absolutePath) ? filemtime($absolutePath) ?: null : null;
    }

    private function versionedAssetUrl(string $path): string
    {
        $url = $this->bannerUrl($path);
        $version = $this->fileVersion($path);

        return $version !== null ? $url.'?v='.$version : $url;
    }

    private function bannerUrl(string $path): string
    {
        $relativePath = trim($path, '/');
        $prefix = trim((string) config('peoplecine.banner_public_prefix', 'images/managed-banners'), '/');

        if ($prefix !== '' && Str::startsWith($relativePath, $prefix.'/')) {
            $suffix = substr($relativePath, strlen($prefix) + 1);
            $normalizedSuffix = str_replace('\\', '/', (string) $suffix);
            [$section, $fileName] = array_pad(explode('/', $normalizedSuffix, 2), 2, null);

            if (
                is_string($section)
                && is_string($fileName)
                && $section !== ''
                && $fileName !== ''
            ) {
                return route('managed-banners.show', [
                    'section' => $section,
                    'filename' => basename($fileName),
                ]);
            }
        }

        return asset($relativePath);
    }
}
