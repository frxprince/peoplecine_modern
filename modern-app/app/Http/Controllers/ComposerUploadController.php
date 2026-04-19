<?php

namespace App\Http\Controllers;

use App\Models\StagedUpload;
use App\Support\PostImageUploadManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ComposerUploadController extends Controller
{
    public function __construct(
        private readonly PostImageUploadManager $uploadManager,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user?->canUploadImages(), 403);

        $maxAttachmentKilobytes = max(1, (int) config('peoplecine.post_image_max_kb', 4096));

        $validated = $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,gif,bmp,webp', 'max:'.$maxAttachmentKilobytes],
        ]);

        $upload = $this->uploadManager->stageUpload($user, $validated['image']);

        return response()->json([
            'token' => $upload->token,
            'name' => $upload->original_filename,
            'size' => $upload->size_bytes,
            'width' => $upload->width,
            'height' => $upload->height,
        ], Response::HTTP_CREATED);
    }

    public function destroy(Request $request, StagedUpload $stagedUpload): Response
    {
        $this->uploadManager->deleteStagedUpload($request->user(), $stagedUpload);

        return response()->noContent();
    }
}
