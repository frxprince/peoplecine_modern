<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class SafeImageUpload
{
    /** @var array<string, string> */
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/x-ms-bmp' => 'bmp',
    ];

    /**
     * @return array{extension:string,mime:string,width:int,height:int}
     */
    public static function inspectUpload(UploadedFile $file, string $attribute = 'image'): array
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '') {
            self::reject($attribute);
        }

        return self::inspectPath($path, $attribute);
    }

    /**
     * @return array{extension:string,mime:string,width:int,height:int}
     */
    public static function inspectPath(string $path, string $attribute = 'image'): array
    {
        if (! is_file($path)) {
            self::reject($attribute);
        }

        $imageInfo = @getimagesize($path);

        if ($imageInfo === false || ! isset($imageInfo[0], $imageInfo[1], $imageInfo['mime'])) {
            self::reject($attribute);
        }

        $detectedMime = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($path));
        $imageMime = strtolower((string) $imageInfo['mime']);
        $extension = self::MIME_TO_EXTENSION[$detectedMime] ?? null;
        $imageExtension = self::MIME_TO_EXTENSION[$imageMime] ?? null;

        if ($extension === null || $imageExtension === null || $extension !== $imageExtension) {
            self::reject($attribute);
        }

        return [
            'extension' => $extension,
            'mime' => self::mimeForExtension($extension),
            'width' => (int) $imageInfo[0],
            'height' => (int) $imageInfo[1],
        ];
    }

    public static function mimeForExtension(string $extension): ?string
    {
        $normalized = strtolower(ltrim($extension, '.'));

        return match ($normalized) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            default => null,
        };
    }

    private static function reject(string $attribute): never
    {
        throw ValidationException::withMessages([
            $attribute => 'The uploaded file is not a supported image.',
        ]);
    }
}
