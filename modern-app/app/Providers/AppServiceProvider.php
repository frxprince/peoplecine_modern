<?php

namespace App\Providers;

use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.legacy-bootstrap');

        View::composer('layouts.app', function ($view): void {
            $user = request()->user();

            $unreadMessageCount = 0;

            if ($user !== null) {
                $unreadMessageCount = DB::table('conversation_participants')
                    ->where('user_id', $user->id)
                    ->whereNull('conversation_participants.deleted_at')
                    ->whereExists(function ($query) use ($user): void {
                        $query->select(DB::raw(1))
                            ->from('messages')
                            ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                            ->whereNull('messages.deleted_at')
                            ->whereRaw('messages.id > COALESCE(conversation_participants.last_read_message_id, 0)')
                            ->where(function ($senderQuery) use ($user): void {
                                $senderQuery->whereNull('messages.sender_id')
                                    ->orWhere('messages.sender_id', '!=', $user->id);
                            })
                            ->whereNotExists(function ($muteQuery) use ($user): void {
                                $muteQuery->select(DB::raw(1))
                                    ->from('direct_message_preferences')
                                    ->whereColumn('direct_message_preferences.target_user_id', 'messages.sender_id')
                                    ->where('direct_message_preferences.user_id', $user->id)
                                    ->where('direct_message_preferences.is_muted', true);
                            });
                    })
                    ->count();
            }

            $view->with('sidebarRooms', Room::query()
                ->visibleTo($user)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(12)
                ->get());
            $view->with('unreadMessageCount', $unreadMessageCount);
        });
    }
}
