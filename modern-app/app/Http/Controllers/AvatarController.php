<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AvatarController extends Controller
{
    public function __invoke(User $user): BinaryFileResponse
    {
        $relativePath = $user->profile?->normalizedAvatarPath();
        $root = rtrim((string) config('peoplecine.legacy_wboard_root'), '\\/');
        $path = $relativePath === null || $root === ''
            ? null
            : $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        abort_if($path === null || ! is_file($path), 404);

        $mimeType = File::mimeType($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
