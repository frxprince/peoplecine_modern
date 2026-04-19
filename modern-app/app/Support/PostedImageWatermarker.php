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
        $image = $this->createImageResource($absolutePath, $imageType);

        if (! is_object($image)) {
            return false;
        }

        try {
            $width = imagesx($image);
            $height = imagesy($image);

            if ($width < 80 || $height < 80) {
                return $this->saveImageResource($image, $absolutePath, $imageType);
            }

            $fontPath = (string) config('peoplecine.watermark_font_path', 'C:/Windows/Fonts/tahoma.ttf');
            $label = sprintf(
                '%s | %s | %s',
                (string) config('peoplecine.watermark_site_name', 'PeopleCine'),
                $user->displayName(),
                $timestamp->format((string) config('peoplecine.watermark_timestamp_format', 'Y-m-d H:i:s'))
            );

            $fontSize = max(9, min(18, (int) round($width / 62)));
            $margin = max(8, (int) config('peoplecine.watermark_margin', 14));
            $textAlpha = max(0, min(127, (int) config('peoplecine.watermark_text_alpha', 72)));
            $shadowAlpha = max(0, min(127, (int) config('peoplecine.watermark_shadow_alpha', 92)));
            $backgroundAlpha = max(0, min(127, (int) config('peoplecine.watermark_background_alpha', 48)));

            imagealphablending($image, true);
            imagesavealpha($image, true);

            if (is_file($fontPath) && function_exists('imagettftext')) {
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

                    return $this->saveImageResource($image, $absolutePath, $imageType);
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

            return $this->saveImageResource($image, $absolutePath, $imageType);
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

    private function saveImageResource(mixed $image, string $absolutePath, int|false $imageType): bool
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => imagejpeg($image, $absolutePath, (int) config('peoplecine.watermark_jpeg_quality', 90)),
            IMAGETYPE_PNG => imagepng($image, $absolutePath, (int) config('peoplecine.watermark_png_compression', 6)),
            IMAGETYPE_GIF => imagegif($image, $absolutePath),
            IMAGETYPE_WEBP => function_exists('imagewebp')
                ? imagewebp($image, $absolutePath, (int) config('peoplecine.watermark_webp_quality', 90))
                : false,
            IMAGETYPE_BMP => function_exists('imagebmp') ? imagebmp($image, $absolutePath) : false,
            default => false,
        };
    }
}
