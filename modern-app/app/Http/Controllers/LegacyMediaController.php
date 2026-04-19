<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Support\LegacyMediaPathResolver;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacyMediaController extends Controller
{
    public function __invoke(Attachment $attachment, LegacyMediaPathResolver $resolver): BinaryFileResponse
    {
        $path = $resolver->resolve($attachment->legacy_path);

        abort_if($path === null, 404);

        $mimeType = File::mimeType($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
