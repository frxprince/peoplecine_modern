<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Support\LegacyMediaPathResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class ImageInspectorController extends Controller
{
    public function __invoke(Attachment $attachment, LegacyMediaPathResolver $resolver): View
    {
        abort_unless($attachment->isImage(), 404);

        $path = $resolver->resolve($attachment->legacy_path);
        abort_if($path === null, 404);

        return view('attachments.inspect', [
            'attachment' => $attachment,
            'imageUrl' => route('legacy-media.show', $attachment),
            'imagePath' => $path,
            'imageSizeBytes' => File::size($path) ?: $attachment->size_bytes,
            'mimeType' => File::mimeType($path) ?: ($attachment->mime_type ?: 'application/octet-stream'),
            'exifRows' => $this->readExifRows($path),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function readExifRows(string $absolutePath): array
    {
        if (! function_exists('exif_read_data')) {
            return [];
        }

        $exif = @exif_read_data($absolutePath, null, true, false);

        if (! is_array($exif)) {
            return [];
        }

        $rows = [];
        $seen = [];
        $fields = [
            'IFD0.Make' => 'Camera Make',
            'IFD0.Model' => 'Camera Model',
            'IFD0.Software' => 'Software',
            'EXIF.LensModel' => 'Lens',
            'EXIF.DateTimeOriginal' => 'Date Taken',
            'EXIF.ExposureTime' => 'Exposure',
            'EXIF.FNumber' => 'Aperture',
            'EXIF.ISOSpeedRatings' => 'ISO',
            'EXIF.FocalLength' => 'Focal Length',
            'GPS.GPSLatitude' => 'GPS Latitude',
            'GPS.GPSLongitude' => 'GPS Longitude',
        ];

        foreach ($fields as $key => $label) {
            [$section, $field] = explode('.', $key, 2);
            $value = $exif[$section][$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $this->normalizeExifValue($value),
            ];
            $seen[$key] = true;
        }

        foreach ($exif as $section => $values) {
            if (! is_array($values)) {
                continue;
            }

            foreach ($values as $field => $value) {
                $key = sprintf('%s.%s', (string) $section, (string) $field);

                if (isset($seen[$key])) {
                    continue;
                }

                if (is_array($value) && $value === []) {
                    continue;
                }

                if (! is_scalar($value) && ! is_array($value)) {
                    continue;
                }

                $normalizedValue = $this->normalizeExifValue($value);

                if ($normalizedValue === '') {
                    continue;
                }

                $rows[] = [
                    'label' => $key,
                    'value' => $normalizedValue,
                ];
            }
        }

        return $rows;
    }

    private function normalizeExifValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map(
                fn (mixed $item): string => $this->normalizeExifValue($item),
                $value
            ));
        }

        $stringValue = trim((string) $value);

        if (preg_match('/^(\d+)\/(\d+)$/', $stringValue, $matches) === 1) {
            $numerator = (float) $matches[1];
            $denominator = (float) $matches[2];

            if ($denominator > 0) {
                $ratio = $numerator / $denominator;

                return rtrim(rtrim(number_format($ratio, 4, '.', ''), '0'), '.');
            }
        }

        return $stringValue;
    }
}
