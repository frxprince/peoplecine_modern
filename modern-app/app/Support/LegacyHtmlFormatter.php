<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\Str;

class LegacyHtmlFormatter
{
    private const FORBIDDEN_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'applet',
        'meta',
        'link',
        'base',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
        'frame',
        'frameset',
    ];

    private const URI_ATTRIBUTES = [
        'href',
        'src',
        'action',
        'formaction',
        'poster',
        'background',
        'xlink:href',
    ];

    public static function linkify(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="legacy-html-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($loaded === false) {
            return e($html);
        }

        $root = self::findRoot($dom);

        if (! $root instanceof DOMElement) {
            return e($html);
        }

        self::sanitizeNode($root);
        self::linkifyNode($root, $dom);
        self::promoteEmbeddableAnchors($root, $dom);
        self::decorateAnchors($root);

        $rendered = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $rendered .= $dom->saveHTML($child);
        }

        return $rendered !== '' ? $rendered : $html;
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tagName = strtolower($child->tagName);

            if (in_array($tagName, self::FORBIDDEN_TAGS, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child);
            self::sanitizeNode($child);
        }
    }

    private static function findRoot(DOMDocument $dom): ?DOMElement
    {
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('id') === 'legacy-html-root') {
                return $div;
            }
        }

        return null;
    }

    private static function linkifyNode(DOMNode $node, DOMDocument $dom): void
    {
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                self::replaceTextNodeWithLinks($child, $dom);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            if (in_array(strtolower($child->tagName), ['a', 'script', 'style'], true)) {
                continue;
            }

            self::linkifyNode($child, $dom);
        }
    }

    private static function replaceTextNodeWithLinks(DOMText $textNode, DOMDocument $dom): void
    {
        $text = $textNode->nodeValue ?? '';

        if ($text === '' || ! preg_match_all('~(?:(?:https?://)|(?:www\.))[^\s<]+~iu', $text, $matches, PREG_OFFSET_CAPTURE)) {
            return;
        }

        $fragment = $dom->createDocumentFragment();
        $offset = 0;

        foreach ($matches[0] as [$rawUrl, $position]) {
            $position = (int) $position;

            if ($position > $offset) {
                $fragment->appendChild($dom->createTextNode(substr($text, $offset, $position - $offset)));
            }

            [$url, $suffix] = self::splitUrlSuffix($rawUrl);

            if ($url === '') {
                $fragment->appendChild($dom->createTextNode($rawUrl));
                $offset = $position + strlen($rawUrl);

                continue;
            }

            $href = self::normalizeHref($url);
            $embed = self::createYouTubeEmbed($dom, $href);

            if ($embed instanceof DOMElement) {
                $fragment->appendChild($embed);
            } else {
                $anchor = $dom->createElement('a', $url);
                $anchor->setAttribute('href', $href);
                self::decorateAnchor($anchor);
                $fragment->appendChild($anchor);
            }

            if ($suffix !== '') {
                $fragment->appendChild($dom->createTextNode($suffix));
            }

            $offset = $position + strlen($rawUrl);
        }

        if ($offset < strlen($text)) {
            $fragment->appendChild($dom->createTextNode(substr($text, $offset)));
        }

        $textNode->parentNode?->replaceChild($fragment, $textNode);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitUrlSuffix(string $rawUrl): array
    {
        $url = rtrim($rawUrl, '.,!?;:');
        $suffix = substr($rawUrl, strlen($url));

        return [$url, $suffix];
    }

    private static function normalizeHref(string $url): string
    {
        if (str_starts_with(strtolower($url), 'http://') || str_starts_with(strtolower($url), 'https://')) {
            return $url;
        }

        return 'https://'.$url;
    }

    private static function promoteEmbeddableAnchors(DOMElement $root, DOMDocument $dom): void
    {
        $anchors = iterator_to_array($root->getElementsByTagName('a'));

        foreach ($anchors as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $embed = self::createYouTubeEmbed($dom, $anchor->getAttribute('href'));

            if ($embed instanceof DOMElement) {
                $anchor->parentNode?->replaceChild($embed, $anchor);
            }
        }
    }

    private static function decorateAnchors(DOMElement $root): void
    {
        $anchors = iterator_to_array($root->getElementsByTagName('a'));

        foreach ($anchors as $anchor) {
            if ($anchor instanceof DOMElement) {
                self::decorateAnchor($anchor);
            }
        }
    }

    private static function decorateAnchor(DOMElement $anchor): void
    {
        $anchor->setAttribute('target', '_blank');

        $rels = preg_split('/\s+/', trim($anchor->getAttribute('rel'))) ?: [];
        $rels = array_filter($rels);
        $rels[] = 'noopener';
        $rels[] = 'noreferrer';
        $anchor->setAttribute('rel', implode(' ', array_values(array_unique($rels))));
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $attributes = [];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $attributes[] = $attribute->name;
        }

        foreach ($attributes as $attributeName) {
            $normalizedName = strtolower($attributeName);

            if (str_starts_with($normalizedName, 'on')) {
                $element->removeAttribute($attributeName);

                continue;
            }

            if (in_array($normalizedName, ['srcdoc', 'xmlns', 'form'], true)) {
                $element->removeAttribute($attributeName);

                continue;
            }

            if (in_array($normalizedName, self::URI_ATTRIBUTES, true)) {
                $value = trim($element->getAttribute($attributeName));
                $rewritten = self::rewriteLegacyArticleUrl($value);

                if ($rewritten !== null) {
                    $element->setAttribute($attributeName, $rewritten);
                    $value = $rewritten;
                }

                if (! self::isSafeUrl($value)) {
                    $element->removeAttribute($attributeName);
                }
            }
        }
    }

    private static function rewriteLegacyArticleUrl(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $pdfPath = self::extractLegacyArticlePdfPath($value);

        if ($pdfPath !== null) {
            return route('legacy-article-pdf.show', ['filename' => $pdfPath], false);
        }

        $path = self::extractLegacyArticlePath($value);

        if ($path === null) {
            return null;
        }

        return route('legacy-article-media.show', ['path' => $path], false);
    }

    private static function extractLegacyArticlePath(string $value): ?string
    {
        if (preg_match('/[\x00-\x1F\x7F]/u', $value) === 1) {
            return null;
        }

        $parts = parse_url($value);

        if ($parts === false) {
            return null;
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));
        $path = str_replace('\\', '/', (string) ($parts['path'] ?? ''));

        if ($path === '' && ! str_starts_with($value, '/')) {
            return null;
        }

        if ($host !== '' && ! in_array($host, ['thaicine.com', 'www.thaicine.com', 'peoplecine.com', 'www.peoplecine.com'], true)) {
            return null;
        }

        $segments = preg_split('~/+~', ltrim($path, '/')) ?: [];
        $wboardIndex = array_search('wboard', array_map(static fn ($segment) => Str::lower($segment), $segments), true);

        if ($wboardIndex !== false) {
            $segments = array_slice($segments, $wboardIndex + 1);
        }

        if ($segments === [] || Str::lower($segments[0]) !== 'articles') {
            return null;
        }

        $normalized = implode('/', array_filter($segments, static fn ($segment) => $segment !== ''));

        return $normalized !== '' ? $normalized : null;
    }

    private static function extractLegacyArticlePdfPath(string $value): ?string
    {
        if (preg_match('/[\x00-\x1F\x7F]/u', $value) === 1) {
            return null;
        }

        $normalizedValue = str_replace('\\', '/', trim($value));

        if (preg_match('~^pdf/([^/?#]+\.pdf)$~iu', ltrim($normalizedValue, '/'), $matches) === 1) {
            return basename($matches[1]);
        }

        $parts = parse_url($normalizedValue);

        if ($parts === false) {
            return null;
        }

        $host = Str::lower((string) ($parts['host'] ?? ''));

        if ($host !== '' && ! in_array($host, ['thaicine.com', 'www.thaicine.com', 'peoplecine.com', 'www.peoplecine.com'], true)) {
            return null;
        }

        $path = str_replace('\\', '/', (string) ($parts['path'] ?? ''));
        $segments = preg_split('~/+~', ltrim($path, '/')) ?: [];
        $lowerSegments = array_map(static fn ($segment) => Str::lower($segment), $segments);
        $wboardIndex = array_search('wboard', $lowerSegments, true);

        if ($wboardIndex !== false) {
            $segments = array_slice($segments, $wboardIndex + 1);
            $lowerSegments = array_slice($lowerSegments, $wboardIndex + 1);
        }

        if (($lowerSegments[0] ?? null) !== 'pdf' || ! isset($segments[1])) {
            return null;
        }

        return basename($segments[1]);
    }

    private static function isSafeUrl(string $value): bool
    {
        if ($value === '') {
            return true;
        }

        if (preg_match('/[\x00-\x1F\x7F]/u', $value) === 1) {
            return false;
        }

        if (str_starts_with($value, '#') || str_starts_with($value, '/') || str_starts_with($value, './') || str_starts_with($value, '../')) {
            return true;
        }

        if (preg_match('~^(?:https?|mailto|tel|ftp):~iu', $value) === 1) {
            return true;
        }

        return ! preg_match('~^[a-z][a-z0-9+.-]*:~iu', $value);
    }

    private static function createYouTubeEmbed(DOMDocument $dom, string $url): ?DOMElement
    {
        $embedUrl = self::youtubeEmbedUrl($url);

        if ($embedUrl === null) {
            return null;
        }

        $wrapper = $dom->createElement('div');
        $wrapper->setAttribute('class', 'youtube-embed');

        $iframe = $dom->createElement('iframe');
        $iframe->setAttribute('class', 'youtube-embed__frame');
        $iframe->setAttribute('src', $embedUrl);
        $iframe->setAttribute('title', 'YouTube video player');
        $iframe->setAttribute('loading', 'lazy');
        $iframe->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share');
        $iframe->setAttribute('allowfullscreen', 'allowfullscreen');

        $wrapper->appendChild($iframe);

        return $wrapper;
    }

    private static function youtubeEmbedUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = trim((string) ($parts['path'] ?? ''), '/');
        $videoId = null;

        if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (str_contains($host, 'youtube.com')) {
            parse_str((string) ($parts['query'] ?? ''), $query);

            if (! empty($query['v'])) {
                $videoId = (string) $query['v'];
            } elseif (str_starts_with($path, 'embed/')) {
                $videoId = explode('/', substr($path, 6))[0] ?? null;
            } elseif (str_starts_with($path, 'shorts/')) {
                $videoId = explode('/', substr($path, 7))[0] ?? null;
            } elseif (str_starts_with($path, 'live/')) {
                $videoId = explode('/', substr($path, 5))[0] ?? null;
            }
        }

        if (! is_string($videoId) || ! preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
            return null;
        }

        return 'https://www.youtube-nocookie.com/embed/'.$videoId;
    }
}
