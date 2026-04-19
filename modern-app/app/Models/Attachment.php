<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Attachment extends Model
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'slot_no',
        'legacy_path',
        'storage_disk',
        'stored_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'checksum_sha256',
    ];

    public function legacyUrl(): ?string
    {
        return $this->legacy_path !== null
            ? route('legacy-media.show', $this)
            : null;
    }

    public function scopeImageFiles(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            foreach (self::IMAGE_EXTENSIONS as $extension) {
                $builder->orWhereRaw(
                    'lower(coalesce(original_filename, legacy_path, \'\')) like ?',
                    ["%.{$extension}"]
                );
            }
        });
    }

    public function isImage(): bool
    {
        $extension = Str::lower(pathinfo((string) $this->original_filename, PATHINFO_EXTENSION));

        return in_array($extension, self::IMAGE_EXTENSIONS, true);
    }
}
