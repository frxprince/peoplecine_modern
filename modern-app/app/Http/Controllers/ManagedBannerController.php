<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Routing\ResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ManagedBannerController extends Controller
{
    public function __invoke(string $section, string $filename): BinaryFileResponse
    {
        abort_unless(in_array($section, ['sidebar', 'landing'], true), 404);

        $baseRoot = rtrim((string) config('peoplecine.banner_public_root'), DIRECTORY_SEPARATOR);
        $path = $baseRoot.DIRECTORY_SEPARATOR.$section.DIRECTORY_SEPARATOR.basename($filename);

        abort_unless(is_file($path), 404);

        /** @var ResponseFactory $response */
        $response = response();

        return $response->file($path);
    }
}
