<?php

namespace App\Http\Controllers;

use App\Support\LegacyMediaPathResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyArticleMediaController extends Controller
{
    public function __invoke(Request $request, LegacyMediaPathResolver $resolver): BinaryFileResponse
    {
        $path = trim((string) $request->query('path'));

        abort_if($path === '', 404);

        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        abort_unless(str_starts_with(strtolower($normalized), 'articles/'), 404);

        $resolved = $resolver->resolve($normalized);

        abort_if($resolved === null, 404);

        $mimeType = File::mimeType($resolved) ?: 'application/octet-stream';

        return response()->file($resolved, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
