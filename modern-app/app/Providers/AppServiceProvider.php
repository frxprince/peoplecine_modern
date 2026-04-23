<?php

namespace App\Providers;

use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Support\BannerManager;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
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
            $bannerManager = app(BannerManager::class);
            $user = request()->user();
            $buildTimestamp = collect([
                base_path('composer.lock'),
                base_path('routes/web.php'),
                resource_path('views/layouts/app.blade.php'),
                public_path('css/peoplecine.css'),
            ])
                ->filter(static fn (string $path): bool => is_file($path))
                ->map(static fn (string $path): int => (int) filemtime($path))
                ->max() ?: time();

            $unreadMessageCount = 0;

            if ($user !== null) {
                User::withoutTimestamps(static function () use ($user): void {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->increment('visit_count');
                });
                $user->visit_count = (int) ($user->visit_count ?? 0) + 1;

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

            Cache::add('peoplecine.site_click_counter', 0, now()->addYears(10));
            $clickCounter = (int) Cache::increment('peoplecine.site_click_counter');

            $view->with('sidebarRooms', Room::query()
                ->visibleTo($user)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit(12)
                ->get());
            $view->with('unreadMessageCount', $unreadMessageCount);
            $view->with('headerStats', [
                'users' => User::query()->count(),
                'topics' => Topic::query()->count(),
            ]);
            $view->with('siteFooterStats', [
                'clicks' => $clickCounter,
                'build_datetime' => CarbonImmutable::createFromTimestamp(
                    $buildTimestamp,
                    config('app.timezone', 'UTC')
                )->format('Y-m-d H:i:s'),
            ]);
            $view->with('sidebarBanners', $bannerManager->sidebarBanners());
        });

        View::composer('landing', function ($view): void {
            $view->with('landingBanners', app(BannerManager::class)->landingBanners());
        });
    }
}
