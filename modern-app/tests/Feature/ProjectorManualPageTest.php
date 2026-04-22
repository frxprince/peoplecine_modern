<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectorManualPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_projector_manual_page_lists_pdf_files(): void
    {
        $root = storage_path('app/private/test-projector-manuals');
        File::ensureDirectoryExists($root);
        File::put($root.DIRECTORY_SEPARATOR.'Manual-A.pdf', "%PDF-1.4\nmanual a");
        File::put($root.DIRECTORY_SEPARATOR.'Manual-B.pdf', "%PDF-1.4\nmanual b");
        File::put($root.DIRECTORY_SEPARATOR.'ignore.txt', 'ignore');

        config()->set('peoplecine.article_pdf_root', $root);

        $response = $this->get(route('projector-manual.index'));

        $response->assertOk();
        $response->assertSee('Manual-A.pdf');
        $response->assertSee('Manual-B.pdf');
        $response->assertDontSee('ignore.txt');
        $response->assertSee('/articles/pdf/Manual-A.pdf', false);
    }
}
