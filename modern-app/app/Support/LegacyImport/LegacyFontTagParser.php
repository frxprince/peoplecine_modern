<?php

namespace App\Support\LegacyImport;

use Illuminate\Support\Str;

class LegacyFontTagParser
{
    /**
     * @return array{text: string, color: ?string}
     */
    public static function parse(?string $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return ['text' => '', 'color' => null];
        }

        $color = null;

        if (preg_match('/<font\b[^>]*\bcolor\s*=\s*([\'"]?)([^\'"\s>]+)\1[^>]*>/i', $raw, $matches) === 1) {
            $color = self::sanitizeColor($matches[2]);
        }

        $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $raw) ?? '';
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text);

        return [
            'text' => $text,
            'color' => $color,
        ];
    }

    public static function sanitizeColor(?string $color): ?string
    {
        $clean = trim(Str::lower((string) $color), " \t\n\r\0\x0B\"'");

        if ($clean === '') {
            return null;
        }

        if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/', $clean) === 1) {
            return $clean;
        }

        if (preg_match('/^[a-z]{3,20}$/', $clean) === 1) {
            return $clean;
        }

        return null;
    }
}
