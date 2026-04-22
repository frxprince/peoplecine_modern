<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArticleRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_rewrites_legacy_embedded_image_urls(): void
    {
        $article = Article::query()->create([
            'title' => 'Legacy Image Article',
            'slug' => 'legacy-image-article',
            'view_count' => 0,
        ]);

        ArticleBlock::query()->create([
            'article_id' => $article->id,
            'position_in_article' => 1,
            'body_html' => '<p><img src="http://thaicine.com/wboard/articles/misc/Movie02s.jpg" alt="legacy"></p>',
        ]);

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertSee('/legacy-article-media?path=articles%2Fmisc%2FMovie02s.jpg', false);
        $response->assertDontSee('http://thaicine.com/wboard/articles/misc/Movie02s.jpg', false);
    }

    public function test_article_page_rewrites_legacy_pdf_links_to_articles_pdf_route(): void
    {
        $article = Article::query()->create([
            'title' => 'Legacy Pdf Article',
            'slug' => 'legacy-pdf-article',
            'view_count' => 0,
        ]);

        ArticleBlock::query()->create([
            'article_id' => $article->id,
            'position_in_article' => 1,
            'body_html' => '<p><a href="pdf/CINEFORWARD.pdf">CINEFORWARD.pdf</a></p>',
        ]);

        $response = $this->get(route('articles.show', $article));

        $response->assertOk();
        $response->assertSee('/articles/pdf/CINEFORWARD.pdf', false);
        $response->assertDontSee('href="pdf/CINEFORWARD.pdf"', false);
    }

    public function test_articles_pdf_route_serves_pdf_file(): void
    {
        $root = storage_path('app/private/test-article-pdf');
        File::ensureDirectoryExists($root);
        File::put($root.DIRECTORY_SEPARATOR.'manual.pdf', "%PDF-1.4\nfake pdf content");

        config()->set('peoplecine.article_pdf_root', $root);

        $response = $this->get('/articles/pdf/manual.pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
