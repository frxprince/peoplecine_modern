<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Post;
use App\Models\StagedUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use DateTimeInterface;

class PostImageUploadManager
{
    public function __construct(
        private readonly PostedImageWatermarker $watermarker,
    ) {
    }

    public function stageUpload(User $user, UploadedFile $file): StagedUpload
    {
        $token = (string) Str::uuid();
        $root = $this->stagingRoot();
        $directory = $root.DIRECTORY_SEPARATOR.$user->getKey();
        $originalFilename = $file->getClientOriginalName();
        $mimeType = $file->getMimeType();

        File::ensureDirectoryExists($directory);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $fileName = $token.'.'.$extension;
        $absolutePath = $directory.DIRECTORY_SEPARATOR.$fileName;

        $file->move($directory, $fileName);

        [$width, $height] = $this->detectImageDimensions($absolutePath);

        return StagedUpload::query()->create([
            'token' => $token,
            'user_id' => $user->getKey(),
            'purpose' => 'post-image',
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => File::size($absolutePath),
            'width' => $width,
            'height' => $height,
            'staged_path' => $this->relativeStagedPath($user, $fileName),
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function persistUploadedFilesForPost(
        User $user,
        Post $post,
        array $files,
        string $prefix,
        DateTimeInterface $timestamp,
        int $startingSlot = 1
    ): int
    {
        if ($files === []) {
            return $startingSlot;
        }

        [$uploadsDirectory, $legacyDirectory] = $this->legacyUploadsDirectoryInfo($timestamp);

        File::ensureDirectoryExists($uploadsDirectory);

        $slot = $startingSlot;

        foreach (array_values($files) as $file) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $originalFilename = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $sizeBytes = $file->getSize();
            $fileName = sprintf(
                'modern-%s-%d-%d-%s.%s',
                $prefix,
                $post->id,
                $slot,
                bin2hex(random_bytes(6)),
                $extension
            );

            $targetPath = $uploadsDirectory.DIRECTORY_SEPARATOR.$fileName;
            $file->move($uploadsDirectory, $fileName);
            $this->watermarker->watermark($targetPath, $user, $timestamp);

            [$width, $height] = $this->detectImageDimensions($targetPath);

            $this->createAttachmentRecord(
                $post,
                $slot,
                $legacyDirectory.'/'.$fileName,
                $originalFilename,
                $mimeType,
                $sizeBytes,
                $width,
                $height
            );

            $slot++;
        }

        return $slot;
    }

    /**
     * @param  array<int, string>  $tokens
     */
    public function claimStagedUploadsForPost(
        User $user,
        Post $post,
        array $tokens,
        string $prefix,
        DateTimeInterface $timestamp,
        int $startingSlot = 1
    ): int
    {
        $tokens = array_values(array_filter(array_map(
            static fn (mixed $token): string => trim((string) $token),
            $tokens
        )));

        if ($tokens === []) {
            return $startingSlot;
        }

        $uploads = StagedUpload::query()
            ->where('user_id', $user->getKey())
            ->whereNull('claimed_at')
            ->whereIn('token', $tokens)
            ->get()
            ->keyBy('token');

        if (count($tokens) !== $uploads->count()) {
            throw ValidationException::withMessages([
                'staged_uploads' => 'One or more uploaded images are missing. Please upload them again.',
            ]);
        }

        [$uploadsDirectory, $legacyDirectory] = $this->legacyUploadsDirectoryInfo($timestamp);
        File::ensureDirectoryExists($uploadsDirectory);

        $slot = $startingSlot;

        foreach ($tokens as $token) {
            /** @var StagedUpload $upload */
            $upload = $uploads->get($token);
            $sourcePath = $this->absoluteStagedPath($upload);

            if (! File::exists($sourcePath)) {
                throw ValidationException::withMessages([
                    'staged_uploads' => 'One or more uploaded images are no longer available. Please upload them again.',
                ]);
            }

            $extension = strtolower(pathinfo($upload->original_filename, PATHINFO_EXTENSION) ?: pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg');
            $fileName = sprintf(
                'modern-%s-%d-%d-%s.%s',
                $prefix,
                $post->id,
                $slot,
                bin2hex(random_bytes(6)),
                $extension
            );

            $targetPath = $uploadsDirectory.DIRECTORY_SEPARATOR.$fileName;
            File::copy($sourcePath, $targetPath);
            File::delete($sourcePath);
            $this->watermarker->watermark($targetPath, $user, $timestamp);

            [$width, $height] = $this->detectImageDimensions($targetPath);

            $this->createAttachmentRecord(
                $post,
                $slot,
                $legacyDirectory.'/'.$fileName,
                $upload->original_filename,
                $upload->mime_type,
                $upload->size_bytes,
                $width,
                $height
            );

            $upload->forceFill([
                'claimed_at' => now(),
                'staged_path' => '',
            ])->save();

            $slot++;
        }

        return $slot;
    }

    public function deleteStagedUpload(User $user, StagedUpload $upload): void
    {
        if ((int) $upload->user_id !== (int) $user->getKey()) {
            abort(403);
        }

        if ($upload->isClaimed()) {
            abort(409);
        }

        $absolutePath = $this->absoluteStagedPath($upload);

        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }

        $upload->delete();
    }

    /**
     * @param  array<int, int|string>  $attachmentIds
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, string>  $stagedTokens
     */
    public function syncPostAttachments(
        User $user,
        Post $post,
        array $attachmentIds,
        array $files,
        array $stagedTokens,
        string $prefix,
        DateTimeInterface $timestamp
    ): void {
        $attachmentIds = array_values(array_unique(array_map(
            static fn (mixed $attachmentId): int => (int) $attachmentId,
            $attachmentIds
        )));

        if ($attachmentIds !== []) {
            $attachments = $post->attachments()
                ->whereIn('id', $attachmentIds)
                ->get();

            foreach ($attachments as $attachment) {
                $this->deleteLegacyAttachmentFile($attachment);
                $attachment->delete();
            }
        }

        $this->reseatAttachmentSlots($post);

        $startingSlot = ((int) $post->attachments()->max('slot_no')) + 1;
        $nextSlot = $this->persistUploadedFilesForPost($user, $post, $files, $prefix, $timestamp, $startingSlot);
        $this->claimStagedUploadsForPost($user, $post, $stagedTokens, $prefix, $timestamp, $nextSlot);
    }

    private function createAttachmentRecord(
        Post $post,
        int $slot,
        string $legacyPath,
        ?string $originalFilename,
        ?string $mimeType,
        ?int $sizeBytes,
        ?int $width,
        ?int $height
    ): Attachment {
        return Attachment::query()->create([
            'attachable_type' => 'post',
            'attachable_id' => $post->id,
            'slot_no' => $slot,
            'legacy_path' => $legacyPath,
            'storage_disk' => 'legacy',
            'stored_path' => null,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'width' => $width,
            'height' => $height,
        ]);
    }

    private function deleteLegacyAttachmentFile(Attachment $attachment): void
    {
        $absolutePath = $this->absoluteLegacyPath($attachment->legacy_path);

        if ($absolutePath === null) {
            return;
        }

        clearstatcache(true, $absolutePath);

        if (! is_file($absolutePath)) {
            return;
        }

        @chmod($absolutePath, 0666);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            if (@unlink($absolutePath)) {
                clearstatcache(true, $absolutePath);

                return;
            }

            File::delete($absolutePath);
            clearstatcache(true, $absolutePath);

            if (! file_exists($absolutePath)) {
                return;
            }

            usleep(50_000);
        }
    }

    private function stagingRoot(): string
    {
        return storage_path('app/private/staged-post-images');
    }

    private function legacyUploadsRoot(): string
    {
        $root = rtrim((string) config('peoplecine.legacy_wboard_root'), '\\/');
        $baseDirectory = trim((string) config('peoplecine.post_image_base_directory', 'picpost'), '\\/');

        return $root.DIRECTORY_SEPARATOR.$baseDirectory;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function legacyUploadsDirectoryInfo(DateTimeInterface $timestamp): array
    {
        $uploadsRoot = $this->legacyUploadsRoot();
        $baseDirectory = trim((string) config('peoplecine.post_image_base_directory', 'picpost'), '\\/');
        $pattern = trim((string) config('peoplecine.post_image_directory_pattern', 'Y/m'));
        $subdirectory = trim($pattern !== '' ? $timestamp->format($pattern) : '', '\\/');

        if ($subdirectory === '') {
            return [$uploadsRoot, $baseDirectory];
        }

        $normalizedSubdirectory = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $subdirectory);
        $legacySubdirectory = str_replace(DIRECTORY_SEPARATOR, '/', $normalizedSubdirectory);

        return [
            $uploadsRoot.DIRECTORY_SEPARATOR.$normalizedSubdirectory,
            $baseDirectory.'/'.$legacySubdirectory,
        ];
    }

    private function relativeStagedPath(User $user, string $fileName): string
    {
        return $user->getKey().'/'.$fileName;
    }

    private function absoluteStagedPath(StagedUpload $upload): string
    {
        return $this->stagingRoot().DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $upload->staged_path);
    }

    private function absoluteLegacyPath(?string $legacyPath): ?string
    {
        if ($legacyPath === null || trim($legacyPath) === '') {
            return null;
        }

        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $legacyPath);

        return rtrim((string) config('peoplecine.legacy_wboard_root'), '\\/').DIRECTORY_SEPARATOR.$relativePath;
    }

    private function reseatAttachmentSlots(Post $post): void
    {
        $slot = 1;

        foreach ($post->attachments()->get() as $attachment) {
            if ((int) $attachment->slot_no !== $slot) {
                $attachment->forceFill(['slot_no' => $slot])->save();
            }

            $slot++;
        }
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function detectImageDimensions(string $absolutePath): array
    {
        $imageSize = @getimagesize($absolutePath);

        if ($imageSize === false) {
            return [null, null];
        }

        return [
            isset($imageSize[0]) ? (int) $imageSize[0] : null,
            isset($imageSize[1]) ? (int) $imageSize[1] : null,
        ];
    }
}
