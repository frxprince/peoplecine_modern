<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function landing(): View
    {
        return view('landing', [
            'title' => __('PeopleCine Modern'),
            'recentVisitors' => $this->recentVisitors(),
        ]);
    }

    public function forum(): View
    {
        $user = request()->user();

        return view('home', [
            'rooms' => Room::query()
                ->visibleTo($user)
                ->withCount('topics')
                ->withSum('topics as total_views', 'view_count')
                ->withSum('topics as total_replies', 'reply_count')
                ->with([
                    'latestTopic' => fn ($query) => $query
                        ->visibleTo($user)
                        ->withExists(['postsWithImages as has_posted_image'])
                        ->with(['author.profile']),
                ])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'latestTopics' => Topic::query()
                ->visibleTo($user)
                ->with(['room', 'author.profile'])
                ->withExists(['postsWithImages as has_posted_image'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('last_posted_at')
                ->limit(10)
                ->get(),
            'latestArticles' => Article::query()
                ->with(['category', 'author.profile'])
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(),
            'stats' => [
                'users' => User::count(),
                'topics' => Topic::count(),
                'posts' => Post::count(),
                'articles' => Article::count(),
            ],
        ]);
    }

    private function recentVisitors(): Collection
    {
        return DB::table('sessions')
            ->leftJoin('users', 'users.id', '=', 'sessions.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->orderByDesc('sessions.last_activity')
            ->limit(200)
            ->get([
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.last_activity',
                'users.username',
                'user_profiles.display_name',
                'user_profiles.first_name',
            ])
            ->filter(function (object $session): bool {
                return $session->user_id !== null || filled($session->ip_address);
            })
            ->unique(function (object $session): string {
                if ($session->user_id !== null) {
                    return 'user:'.$session->user_id;
                }

                return 'guest:'.strtolower((string) $session->ip_address);
            })
            ->take(20)
            ->values()
            ->map(function (object $session): array {
                $name = trim((string) ($session->display_name ?? ''));

                if ($name === '') {
                    $name = trim((string) ($session->first_name ?? ''));
                }

                if ($name === '') {
                    $name = trim((string) ($session->username ?? ''));
                }

                $isGuest = $session->user_id === null;

                return [
                    'label' => $isGuest ? (string) $session->ip_address : $name,
                    'is_guest' => $isGuest,
                    'last_seen' => Carbon::createFromTimestamp(
                        (int) $session->last_activity,
                        config('app.timezone', 'UTC')
                    )->format('Y-m-d H:i'),
                ];
            });
    }
}
