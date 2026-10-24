<?php

namespace Tests\Unit;

use App\Support\SafeImageUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SafeImageUploadTest extends TestCase
{
    public function test_it_uses_the_detected_type_instead_of_a_dangerous_client_extension(): void
    {
        $path = $this->temporaryFile(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p2nK2wAAAAASUVORK5CYII='
        ));

        try {
            $metadata = SafeImageUpload::inspectUpload(
                new UploadedFile($path, 'photo.php', 'application/x-php', null, true)
            );

            $this->assertSame('png', $metadata['extension']);
            $this->assertSame('image/png', $metadata['mime']);
        } finally {
            @unlink($path);
        }
    }

    public function test_it_rejects_php_disguised_as_an_image(): void
    {
        $path = $this->temporaryFile('<?php echo "unsafe";');

        try {
            $this->expectException(ValidationException::class);

            SafeImageUpload::inspectUpload(
                new UploadedFile($path, 'photo.jpg', 'image/jpeg', null, true)
            );
        } finally {
            @unlink($path);
        }
    }

    private function temporaryFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'peoplecine-safe-image-');
        $this->assertNotFalse($path);
        file_put_contents($path, $contents);

        return $path;
    }
}
