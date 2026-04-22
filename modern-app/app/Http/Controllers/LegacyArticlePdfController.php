<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyArticlePdfController extends Controller
{
    public function __invoke(string $filename): BinaryFileResponse
    {
        abort_if($filename === '', 404);
        abort_if(str_contains($filename, '/') || str_contains($filename, '\\'), 404);
        abort_if(basename($filename) !== $filename, 404);

        $root = config('peoplecine.article_pdf_root', public_path('legacy-article-pdf'));
        $path = $root.DIRECTORY_SEPARATOR.$filename;

        abort_unless(is_file($path), 404);

        $mimeType = File::mimeType($path) ?: 'application/pdf';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
