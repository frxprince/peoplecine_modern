<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Post;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function landing(): View
    {
        return view('landing', [
            'title' => __('PeopleCine Modern'),
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
}
