<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $viewer = $request->user();
        $query = trim((string) $request->query('q', ''));
        $topics = null;
        $replies = null;
        $searchError = null;
        $statusCode = 200;

        if ($query !== '') {
            $terms = $this->normalizedTerms($query);

            if ($terms->isEmpty()) {
                $searchError = 'Enter at least one search word with 2 or more characters.';
                $statusCode = 422;
            } elseif (($waitSeconds = $this->enforceFloodProtection($request)) !== null) {
                $searchError = "Search flood protection is active. Please wait {$waitSeconds} seconds before searching again.";
                $statusCode = 429;
            } else {
                $topics = Topic::query()
                    ->visibleTo($viewer)
                    ->with(['room', 'author.profile'])
                    ->where(function ($topicQuery) use ($terms): void {
                        foreach ($terms as $term) {
                            $topicQuery->where('title', 'like', '%'.$term.'%');
                        }
                    })
                    ->orderByDesc('is_pinned')
                    ->orderByDesc('last_posted_at')
                    ->paginate(20, ['*'], 'topics_page')
                    ->withQueryString()
                    ->through(function (Topic $topic): Topic {
                        $topic->setAttribute('result_url', route('topics.show', $topic));

                        return $topic;
                    });

                $replies = Post::query()
                    ->whereHas('topic', fn ($topicQuery) => $topicQuery->visibleTo($viewer))
                    ->with(['topic.room', 'author.profile'])
                    ->where(function ($postQuery) use ($terms): void {
                        foreach ($terms as $term) {
                            $postQuery->where('body_html', 'like', '%'.$term.'%');
                        }
                    })
                    ->orderByDesc('created_at')
                    ->paginate(20, ['*'], 'replies_page')
                    ->withQueryString()
                    ->through(function (Post $post) use ($terms): Post {
                        $post->setAttribute('result_url', route('topics.show', $post->topic).'#post-'.$post->id);
                        $post->setAttribute('search_excerpt', $this->buildExcerpt((string) $post->body_html, $terms));

                        return $post;
                    });
            }
        }

        return response()->view('search.index', [
            'title' => 'Forum Search',
            'searchQuery' => $query,
            'topicResults' => $topics,
            'replyResults' => $replies,
            'searchError' => $searchError,
        ], $statusCode);
    }

    private function enforceFloodProtection(Request $request): ?int
    {
        $identity = $request->user()?->id !== null
            ? 'user:'.$request->user()->id
            : 'ip:'.$request->ip();

        $cooldownKey = 'forum-search-cooldown:'.$identity;
        $burstKey = 'forum-search-burst:'.$identity;
        $cooldownSeconds = max(1, (int) config('peoplecine.forum_search_cooldown_seconds', 6));
        $burstLimit = max(1, (int) config('peoplecine.forum_search_burst_limit', 8));

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return RateLimiter::availableIn($cooldownKey);
        }

        if (RateLimiter::tooManyAttempts($burstKey, $burstLimit)) {
            return RateLimiter::availableIn($burstKey);
        }

        RateLimiter::hit($cooldownKey, $cooldownSeconds);
        RateLimiter::hit($burstKey, 60);

        return null;
    }

    /**
     * @return Collection<int, string>
     */
    private function normalizedTerms(string $query): Collection
    {
        return collect(preg_split('/\s+/u', $query) ?: [])
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 2)
            ->map(fn (string $term): string => mb_substr($term, 0, 40))
            ->take(6)
            ->values();
    }

    /**
     * @param Collection<int, string> $terms
     */
    private function buildExcerpt(string $html, Collection $terms): string
    {
        $plainText = preg_replace('/\s+/u', ' ', trim(strip_tags($html))) ?? '';

        if ($plainText === '') {
            return 'No visible reply text.';
        }

        $firstPosition = null;

        foreach ($terms as $term) {
            $position = mb_stripos($plainText, $term);

            if ($position !== false && ($firstPosition === null || $position < $firstPosition)) {
                $firstPosition = $position;
            }
        }

        if ($firstPosition === null) {
            return Str::limit($plainText, 180);
        }

        $start = max(0, $firstPosition - 70);
        $excerpt = mb_substr($plainText, $start, 180);

        if ($start > 0) {
            $excerpt = '...'.$excerpt;
        }

        if (($start + 180) < mb_strlen($plainText)) {
            $excerpt .= '...';
        }

        return $excerpt;
    }
}
