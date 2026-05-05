<?php

namespace App\Support;

use App\Models\User;
use DateTimeInterface;

class PostedImageWatermarker
{
    public function watermark(string $absolutePath, User $user, DateTimeInterface $timestamp): bool
    {
        if (! extension_loaded('gd') || ! is_file($absolutePath)) {
            return false;
        }

        $imageDetails = @getimagesize($absolutePath);
        $imageType = is_array($imageDetails) && isset($imageDetails[2])
            ? (int) $imageDetails[2]
            : false;
        $originalExifSegment = $imageType === IMAGETYPE_JPEG
            ? $this->extractJpegExifSegment($absolutePath)
            : null;
        $image = $this->createImageResource($absolutePath, $imageType);

        if (! is_object($image)) {
            return false;
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);
            [$image, $width, $height] = $this->resizeImageIfNeeded($image, $imageType, $width, $height);

            if ($width < 80 || $height < 80) {
                return $this->saveImageResource($image, $absolutePath, $imageType, $originalExifSegment);
            }

            $label = sprintf(
                '%s | %s | %s',
                (string) config('peoplecine.watermark_site_name', 'PeopleCine'),
                $user->displayName(),
                $timestamp->format((string) config('peoplecine.watermark_timestamp_format', 'Y-m-d H:i:s'))
            );
            $fontPath = $this->resolveFontPath($label);

            $fontSize = max(9, min(18, (int) round($width / 62)));
            $margin = max(8, (int) config('peoplecine.watermark_margin', 14));
            $textAlpha = max(0, min(127, (int) config('peoplecine.watermark_text_alpha', 72)));
            $shadowAlpha = max(0, min(127, (int) config('peoplecine.watermark_shadow_alpha', 92)));
            $backgroundAlpha = max(0, min(127, (int) config('peoplecine.watermark_background_alpha', 48)));

            imagealphablending($image, true);
            imagesavealpha($image, true);

            if ($fontPath !== null && function_exists('imagettftext')) {
                $textBox = imagettfbbox($fontSize, 0, $fontPath, $label);

                if (is_array($textBox)) {
                    $textWidth = (int) abs($textBox[4] - $textBox[0]);
                    $textHeight = (int) abs($textBox[5] - $textBox[1]);
                    $x = max($margin, $width - $textWidth - $margin);
                    $y = max($fontSize + $margin, $height - $margin);

                    $panelTop = max(0, $y - $textHeight - (int) round($margin * 0.6));
                    $panelBottom = min($height, $y + (int) round($margin * 0.35));
                    $panelLeft = max(0, $x - (int) round($margin * 0.6));
                    $panelRight = min($width, $x + $textWidth + (int) round($margin * 0.6));
                    $panelColor = imagecolorallocatealpha($image, 0, 0, 0, $backgroundAlpha);
                    $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, $shadowAlpha);
                    $textColor = imagecolorallocatealpha($image, 255, 255, 255, $textAlpha);

                    imagefilledrectangle($image, $panelLeft, $panelTop, $panelRight, $panelBottom, $panelColor);
                    imagettftext($image, $fontSize, 0, $x + 1, $y + 1, $shadowColor, $fontPath, $label);
                    imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontPath, $label);

                    return $this->saveImageResource($image, $absolutePath, $imageType, $originalExifSegment);
                }
            }

            $font = 2;
            $textWidth = imagefontwidth($font) * strlen($label);
            $textHeight = imagefontheight($font);
            $x = max($margin, $width - $textWidth - $margin);
            $y = max($margin, $height - $textHeight - $margin);
            $panelColor = imagecolorallocatealpha(
                $image,
                0,
                0,
                0,
                $backgroundAlpha
            );
            $shadowColor = imagecolorallocatealpha($image, 0, 0, 0, $shadowAlpha);
            $textColor = imagecolorallocatealpha($image, 255, 255, 255, $textAlpha);

            imagefilledrectangle(
                $image,
                max(0, $x - (int) round($margin * 0.5)),
                max(0, $y - (int) round($margin * 0.4)),
                min($width, $x + $textWidth + (int) round($margin * 0.5)),
                min($height, $y + $textHeight + (int) round($margin * 0.4)),
                $panelColor
            );
            imagestring($image, $font, $x + 1, $y + 1, $label, $shadowColor);
            imagestring($image, $font, $x, $y, $label, $textColor);

            return $this->saveImageResource($image, $absolutePath, $imageType, $originalExifSegment);
        } finally {
            imagedestroy($image);
        }
    }

    private function createImageResource(string $absolutePath, int|false $imageType): mixed
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolutePath),
            IMAGETYPE_PNG => @imagecreatefrompng($absolutePath),
            IMAGETYPE_GIF => @imagecreatefromgif($absolutePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($absolutePath) : false,
            IMAGETYPE_BMP => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($absolutePath) : false,
            default => false,
        };
    }

    /**
     * @return array{0: mixed, 1: int, 2: int}
     */
    private function resizeImageIfNeeded(mixed $image, int|false $imageType, int $width, int $height): array
    {
        $maxWidth = max(1, (int) config('peoplecine.post_image_max_width', 1920));
        $maxHeight = max(1, (int) config('peoplecine.post_image_max_height', 1080));

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return [$image, $width, $height];
        }

        $scale = min($maxWidth / max(1, $width), $maxHeight / max(1, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! is_object($resized)) {
            return [$image, $width, $height];
        }

        if (in_array($imageType, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        $copied = imagecopyresampled(
            $resized,
            $image,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height
        );

        if (! $copied) {
            imagedestroy($resized);

            return [$image, $width, $height];
        }

        imagedestroy($image);

        return [$resized, $targetWidth, $targetHeight];
    }

    private function saveImageResource(
        mixed $image,
        string $absolutePath,
        int|false $imageType,
        ?string $exifSegment = null
    ): bool
    {
        $saved = match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($image, $absolutePath, (int) config('peoplecine.watermark_jpeg_quality', 90)),
            IMAGETYPE_PNG => imagepng($image, $absolutePath, (int) config('peoplecine.watermark_png_compression', 6)),
            IMAGETYPE_GIF => imagegif($image, $absolutePath),
            IMAGETYPE_WEBP => function_exists('imagewebp')
                ? imagewebp($image, $absolutePath, (int) config('peoplecine.watermark_webp_quality', 90))
                : false,
            IMAGETYPE_BMP => function_exists('imagebmp') ? imagebmp($image, $absolutePath) : false,
            default => false,
        };

        if (! $saved || $imageType !== IMAGETYPE_JPEG || $exifSegment === null) {
            return $saved;
        }

        return $this->injectJpegExifSegment($absolutePath, $exifSegment);
    }

    private function extractJpegExifSegment(string $absolutePath): ?string
    {
        $bytes = @file_get_contents($absolutePath);

        if (! is_string($bytes) || strlen($bytes) < 6 || ! str_starts_with($bytes, "\xFF\xD8")) {
            return null;
        }

        $offset = 2;
        $length = strlen($bytes);

        while (($offset + 4) <= $length) {
            if ($bytes[$offset] !== "\xFF") {
                break;
            }

            $marker = ord($bytes[$offset + 1]);

            if ($marker === 0xDA || $marker === 0xD9) {
                break;
            }

            $segmentLength = unpack('n', substr($bytes, $offset + 2, 2))[1] ?? 0;

            if ($segmentLength < 2) {
                break;
            }

            $segmentEnd = $offset + 2 + $segmentLength;

            if ($segmentEnd > $length) {
                break;
            }

            if ($marker === 0xE1) {
                $payload = substr($bytes, $offset + 4, $segmentLength - 2);

                if (str_starts_with($payload, "Exif\x00\x00")) {
                    return substr($bytes, $offset, 2 + $segmentLength);
                }
            }

            $offset = $segmentEnd;
        }

        return null;
    }

    private function injectJpegExifSegment(string $absolutePath, string $exifSegment): bool
    {
        $bytes = @file_get_contents($absolutePath);

        if (! is_string($bytes) || strlen($bytes) < 4 || ! str_starts_with($bytes, "\xFF\xD8")) {
            return false;
        }

        $insertAt = 2;
        $length = strlen($bytes);

        if (($insertAt + 4) <= $length && $bytes[$insertAt] === "\xFF" && ord($bytes[$insertAt + 1]) === 0xE0) {
            $app0Length = unpack('n', substr($bytes, $insertAt + 2, 2))[1] ?? 0;

            if ($app0Length >= 2) {
                $candidate = $insertAt + 2 + $app0Length;

                if ($candidate <= $length) {
                    $insertAt = $candidate;
                }
            }
        }

        $merged = substr($bytes, 0, $insertAt).$exifSegment.substr($bytes, $insertAt);

        return @file_put_contents($absolutePath, $merged) !== false;
    }

    private function resolveFontPath(string $label): ?string
    {
        $configuredCandidates = config('peoplecine.watermark_font_paths', []);

        if (! is_array($configuredCandidates)) {
            $configuredCandidates = [];
        }

        $singleConfiguredPath = trim((string) config('peoplecine.watermark_font_path', ''));
        $preferThai = $this->containsThaiCharacters($label);

        $candidates = array_values(array_filter(array_unique(array_merge(
            array_map(static fn (mixed $path): string => trim((string) $path), $configuredCandidates),
            $preferThai ? $this->defaultThaiFontCandidates() : [],
            $singleConfiguredPath !== '' ? [$singleConfiguredPath] : [],
            $this->defaultGeneralFontCandidates(),
        ))));

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function containsThaiCharacters(string $text): bool
    {
        return preg_match('/\p{Thai}/u', $text) === 1;
    }

    /**
     * @return array<int, string>
     */
    private function defaultThaiFontCandidates(): array
    {
        return [
            'C:/Windows/Fonts/THSarabunNew.ttf',
            'C:/Windows/Fonts/THSarabun.ttf',
            'C:/Windows/Fonts/LeelawUI.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
            '/usr/share/fonts/truetype/tlwg/Garuda.ttf',
            '/usr/share/fonts/truetype/tlwg/Waree.ttf',
            '/usr/share/fonts/truetype/noto/NotoSansThai-Regular.ttf',
            '/usr/share/fonts/truetype/noto/NotoSerifThai-Regular.ttf',
            '/usr/share/fonts/truetype/tlwg/Loma.ttf',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function defaultGeneralFontCandidates(): array
    {
        return [
            'C:/Windows/Fonts/tahoma.ttf',
            'C:/Windows/Fonts/arial.ttf',
            '/usr/share/fonts/truetype/noto/NotoSans-Regular.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        ];
    }
}
