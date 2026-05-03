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
        return DB::table('recent_visitors')
            ->leftJoin('users', 'users.id', '=', 'recent_visitors.user_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->orderByDesc('recent_visitors.last_visited_at')
            ->limit(20)
            ->get([
                'recent_visitors.user_id',
                'recent_visitors.ip_address',
                'recent_visitors.last_visited_at',
                'users.username',
                'user_profiles.display_name',
                'user_profiles.first_name',
            ])
            ->filter(function (object $session): bool {
                return $session->user_id !== null || filled($session->ip_address);
            })
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
                    'last_seen' => Carbon::parse(
                        (string) $session->last_visited_at,
                        config('app.timezone', 'UTC')
                    )->format('Y-m-d H:i'),
                ];
            });
    }
}
