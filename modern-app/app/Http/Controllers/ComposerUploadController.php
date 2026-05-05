<?php

namespace App\Http\Controllers;

use App\Models\StagedUpload;
use App\Support\PostImageUploadManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
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
        $uploadedFile = $request->file('image');

        if ($uploadedFile instanceof UploadedFile && ! $uploadedFile->isValid()) {
            $uploadErrorCode = $uploadedFile->getError();
            $message = match ($uploadErrorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => sprintf(
                    'The uploaded image is too large for the server limit (%s).',
                    ini_get('upload_max_filesize')
                ),
                UPLOAD_ERR_PARTIAL => 'The uploaded image was only partially uploaded. Please try again.',
                default => sprintf('The uploaded image failed to upload (error code: %d).', $uploadErrorCode),
            };

            throw ValidationException::withMessages([
                'image' => $message,
            ]);
        }

        try {
            $validated = $request->validate([
                'image' => [
                    'required',
                    'file',
                    'image',
                    'mimes:jpg,jpeg,png,gif,bmp,webp',
                    'max:'.$maxAttachmentKilobytes,
                ],
            ]);
        } catch (ValidationException $exception) {
            $file = $request->file('image');

            Log::warning('Composer image upload validation failed.', [
                'user_id' => $user?->id,
                'original_name' => $file instanceof UploadedFile ? $file->getClientOriginalName() : null,
                'reported_mime' => $file instanceof UploadedFile ? $file->getClientMimeType() : null,
                'server_mime' => ($file instanceof UploadedFile && is_string($file->getRealPath()) && $file->getRealPath() !== '')
                    ? (@mime_content_type($file->getRealPath()) ?: null)
                    : null,
                'size_bytes' => $file instanceof UploadedFile ? $file->getSize() : null,
                'errors' => $exception->errors(),
            ]);

            throw $exception;
        }

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
