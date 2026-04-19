<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LegacyMediaPathResolver;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarController extends Controller
{
    public function __invoke(User $user, LegacyMediaPathResolver $resolver): BinaryFileResponse
    {
        $path = $resolver->resolve($user->profile?->normalizedAvatarPath());

        abort_if($path === null, 404);

        $mimeType = File::mimeType($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
