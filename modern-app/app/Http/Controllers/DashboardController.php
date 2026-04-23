<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Conversation;
use App\Models\Room;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->load('profile');

        $recentTopics = Topic::query()
            ->visibleTo($user)
            ->with(['room'])
            ->where('author_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentPosts = $user->posts()
            ->whereHas('topic', fn ($query) => $query->visibleTo($user))
            ->with(['topic.room'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $bookmarkedTopics = $user->bookmarks()
            ->visibleTo($user)
            ->with(['room', 'author.profile'])
            ->latest('bookmarks.created_at')
            ->limit(5)
            ->get();

        $recentConversations = Conversation::query()
            ->forUser($user)
            ->with(['participants.profile', 'latestMessage.sender.profile'])
            ->withMax('messages as latest_message_at', 'created_at')
            ->orderByDesc('latest_message_at')
            ->limit(5)
            ->get()
            ->each(function (Conversation $conversation) use ($user): void {
                $conversation->setAttribute('is_unread_for_viewer', $conversation->hasUnreadMessagesFor($user));
            });

        return view('dashboard', [
            'title' => $this->label('แดชบอร์ดสมาชิก', 'Member Dashboard'),
            'user' => $user,
            'stats' => [
                'profile_status' => $user->requiresPasswordReset()
                    ? $this->label('ต้องเปลี่ยนรหัสผ่าน', 'Needs password update')
                    : $this->label('ใช้งานอยู่', 'Active'),
                'member_level' => $user->memberLevelLabel(),
                'can_reply' => $user->canReply(),
                'can_create_topic' => $user->canCreateTopic(),
                'can_upload_images' => $user->canUploadImages(),
                'can_access_vip' => $user->canAccessVipRoom(),
                'topics_started' => $user->topics()->count(),
                'posts_written' => $user->posts()->count(),
                'bookmarks' => $user->bookmarks()->count(),
                'articles' => Article::query()->where('author_id', $user->id)->count(),
                'private_messages' => $user->conversations()->count(),
                'moderated_rooms' => Room::query()
                    ->whereExists(function ($query) use ($user) {
                        $query->select(DB::raw(1))
                            ->from('room_moderators')
                            ->whereColumn('room_moderators.room_id', 'rooms.id')
                            ->where('room_moderators.user_id', $user->id);
                    })
                    ->count(),
            ],
            'recentTopics' => $recentTopics,
            'recentPosts' => $recentPosts,
            'bookmarkedTopics' => $bookmarkedTopics,
            'recentConversations' => $recentConversations,
        ]);
    }

    private function label(string $thai, string $english): string
    {
        return app()->getLocale() === 'th' ? $thai : $english;
    }
}
