<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Room;
use App\Models\Topic;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $now = now();

        $urls = collect([
            ['loc' => route('landing'), 'lastmod' => $now, 'priority' => '1.0'],
            ['loc' => route('home'), 'lastmod' => $now, 'priority' => '0.9'],
            ['loc' => route('projector-manual.index'), 'lastmod' => $now, 'priority' => '0.7'],
            ['loc' => route('articles.index'), 'lastmod' => $now, 'priority' => '0.7'],
            ['loc' => route('eula'), 'lastmod' => $now, 'priority' => '0.3'],
        ]);

        $roomUrls = Room::query()
            ->select(['slug', 'updated_at', 'created_at'])
            ->get()
            ->map(fn (Room $room): array => [
                'loc' => route('rooms.show', $room),
                'lastmod' => $room->updated_at ?? $room->created_at ?? $now,
                'priority' => '0.8',
            ]);

        $topicUrls = Topic::query()
            ->select(['id', 'updated_at', 'last_posted_at', 'created_at'])
            ->orderByDesc('last_posted_at')
            ->limit(50000)
            ->get()
            ->map(fn (Topic $topic): array => [
                'loc' => route('topics.show', $topic),
                'lastmod' => $topic->last_posted_at ?? $topic->updated_at ?? $topic->created_at ?? $now,
                'priority' => '0.7',
            ]);

        $articleUrls = Article::query()
            ->select(['slug', 'updated_at', 'published_at', 'created_at'])
            ->get()
            ->map(fn (Article $article): array => [
                'loc' => route('articles.show', $article),
                'lastmod' => $article->updated_at ?? $article->published_at ?? $article->created_at ?? $now,
                'priority' => '0.6',
            ]);

        $allUrls = $urls
            ->concat($roomUrls)
            ->concat($topicUrls)
            ->concat($articleUrls)
            ->values();

        $xml = $this->renderSitemap($allUrls->all());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * @param  array<int, array{loc:string,lastmod:DateTimeInterface|string|null,priority:string}>  $urls
     */
    private function renderSitemap(array $urls): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($urls as $url) {
            $loc = $this->escapeXml($url['loc']);
            $lastmodRaw = $url['lastmod'] ?? null;
            $lastmod = $lastmodRaw instanceof DateTimeInterface
                ? Carbon::instance(Carbon::parse($lastmodRaw))->toAtomString()
                : ($lastmodRaw !== null ? Carbon::parse((string) $lastmodRaw)->toAtomString() : null);
            $priority = $this->escapeXml($url['priority'] ?? '0.5');

            $lines[] = '  <url>';
            $lines[] = "    <loc>{$loc}</loc>";

            if ($lastmod !== null) {
                $lines[] = '    <lastmod>'.$this->escapeXml($lastmod).'</lastmod>';
            }

            $lines[] = "    <priority>{$priority}</priority>";
            $lines[] = '  </url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines)."\n";
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}

