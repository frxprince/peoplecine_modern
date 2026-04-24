<?php

namespace App\Http\Controllers;

use App\Support\LegacyMediaPathResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyInlineMediaController extends Controller
{
    public function __invoke(Request $request, LegacyMediaPathResolver $resolver): BinaryFileResponse
    {
        $path = trim((string) $request->query('path', ''));
        abort_if($path === '', 404);

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $lowerNormalized = strtolower($normalized);

        abort_unless(
            str_starts_with($lowerNormalized, 'uploads/')
            || str_starts_with($lowerNormalized, 'picpost/')
            || str_starts_with($lowerNormalized, 'icons/'),
            404
        );

        $resolvedPath = $resolver->resolve($normalized);
        abort_if($resolvedPath === null, 404);

        $mimeType = File::mimeType($resolvedPath) ?: 'application/octet-stream';

        return response()->file($resolvedPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
