<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminTestMail;
use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

class DebugController extends Controller
{
    public function index(): View
    {
        return view('admin.debug.index', [
            'title' => 'Debug',
        ]);
    }

    public function statistics(): View
    {
        $recentVisitors = DB::table('recent_visitors')
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
            ])
            ->map(function (object $visitor): array {
                $name = trim((string) ($visitor->display_name ?? ''));

                if ($name === '') {
                    $name = trim((string) ($visitor->username ?? ''));
                }

                $isGuest = $visitor->user_id === null;

                return [
                    'label' => $isGuest ? ((string) $visitor->ip_address ?: 'Unknown guest') : $name,
                    'is_guest' => $isGuest,
                    'ip_address' => $visitor->ip_address ?: '-',
                    'last_seen' => Carbon::parse((string) $visitor->last_visited_at)->format('Y-m-d H:i'),
                ];
            });

        $topVisitors = User::query()
            ->with('profile')
            ->where('visit_count', '>', 0)
            ->orderByDesc('visit_count')
            ->orderBy('username')
            ->limit(20)
            ->get();

        $topViewedTopics = Topic::query()
            ->with(['room', 'author.profile'])
            ->orderByDesc('view_count')
            ->orderByDesc('last_posted_at')
            ->limit(15)
            ->get();

        $topRepliedTopics = Topic::query()
            ->with(['room', 'author.profile'])
            ->orderByDesc('reply_count')
            ->orderByDesc('last_posted_at')
            ->limit(15)
            ->get();

        $roomStats = Room::query()
            ->withCount('topics')
            ->withSum('topics as total_views', 'view_count')
            ->withSum('topics as total_replies', 'reply_count')
            ->with(['latestTopic'])
            ->orderByDesc('total_views')
            ->orderByDesc('total_replies')
            ->orderBy('name')
            ->limit(15)
            ->get();

        $recentPosts = DB::table('posts')
            ->leftJoin('users', 'users.id', '=', 'posts.author_id')
            ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
            ->leftJoin('topics', 'topics.id', '=', 'posts.topic_id')
            ->leftJoin('rooms', 'rooms.id', '=', 'topics.room_id')
            ->whereNull('posts.deleted_at')
            ->orderByDesc('posts.created_at')
            ->limit(20)
            ->get([
                'posts.id',
                'posts.position_in_topic',
                'posts.ip_address',
                'posts.created_at',
                'topics.title as topic_title',
                'rooms.name as room_name',
                'users.username',
                'user_profiles.display_name',
            ])
            ->map(function (object $post): array {
                $name = trim((string) ($post->display_name ?? ''));

                if ($name === '') {
                    $name = trim((string) ($post->username ?? ''));
                }

                return [
                    'author' => $name !== '' ? $name : 'Archived member',
                    'topic_title' => (string) ($post->topic_title ?? 'Archived topic'),
                    'room_name' => (string) ($post->room_name ?? 'Archived room'),
                    'position_in_topic' => (int) $post->position_in_topic,
                    'ip_address' => $post->ip_address ?: '-',
                    'created_at' => Carbon::parse((string) $post->created_at)->format('Y-m-d H:i'),
                ];
            });

        return view('admin.debug.statistics', [
            'title' => 'Statistics',
            'overview' => [
                'members' => User::query()->count(),
                'member_clicks' => (int) User::query()->sum('visit_count'),
                'stored_visitors' => (int) DB::table('recent_visitors')->count(),
                'guest_visitors' => (int) DB::table('recent_visitors')->whereNull('user_id')->count(),
                'topics' => Topic::query()->count(),
                'posts' => (int) DB::table('posts')->whereNull('deleted_at')->count(),
                'topic_views' => (int) Topic::query()->sum('view_count'),
                'topic_replies' => (int) Topic::query()->sum('reply_count'),
            ],
            'recentVisitors' => $recentVisitors,
            'topVisitors' => $topVisitors,
            'topViewedTopics' => $topViewedTopics,
            'topRepliedTopics' => $topRepliedTopics,
            'roomStats' => $roomStats,
            'recentPosts' => $recentPosts,
        ]);
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email'],
            'subject_line' => ['nullable', 'string', 'max:160'],
            'body_text' => ['nullable', 'string', 'max:4000'],
        ]);

        $subject = trim((string) ($validated['subject_line'] ?? ''));
        $body = trim((string) ($validated['body_text'] ?? ''));

        $subject = $subject !== '' ? $subject : 'PeopleCine mail test';
        $body = $body !== ''
            ? $body
            : "This is a test email from the PeopleCine debug panel.\n\nSent at: ".now()->format('Y-m-d H:i:s');

        try {
            Mail::to($validated['recipient_email'])->send(
                new AdminTestMail($subject, $body)
            );
        } catch (Throwable $exception) {
            return redirect()
                ->route('debug.index')
                ->withInput()
                ->withErrors([
                    'mail_test' => 'Unable to send test email: '.$exception->getMessage(),
                ]);
        }

        return redirect()
            ->route('debug.index')
            ->with('status', "Test email sent to {$validated['recipient_email']}.");
    }
}
