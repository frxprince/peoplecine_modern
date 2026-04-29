<?php

namespace App\Providers;

use App\Models\Room;
use App\Models\Topic;
use App\Models\User;
use App\Support\BannerManager;
use Carbon\CarbonImmutable;
use FilesystemIterator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

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
            $buildTimestamp = $this->resolveBuildTimestamp();

            $unreadMessageCount = 0;

            if ($user !== null) {
                if (Schema::hasColumn('users', 'visit_count') && Schema::hasColumn('users', 'last_visited_at')) {
                    $now = now();

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'visit_count' => DB::raw('visit_count + 1'),
                            'last_visited_at' => $now,
                        ]);

                    $user->visit_count = (int) ($user->visit_count ?? 0) + 1;
                    $user->last_visited_at = $now;
                } else {
                    User::withoutTimestamps(static function () use ($user): void {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->increment('visit_count');
                    });
                    $user->visit_count = (int) ($user->visit_count ?? 0) + 1;
                }

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

            if (Schema::hasTable('recent_visitors')) {
                $visitorIp = request()->ip();
                $visitorKey = $user !== null
                    ? 'user:'.$user->id
                    : ($visitorIp !== null && $visitorIp !== '' ? 'guest:'.strtolower($visitorIp) : null);

                if ($visitorKey !== null) {
                    DB::table('recent_visitors')->updateOrInsert(
                        ['visitor_key' => $visitorKey],
                        [
                            'user_id' => $user?->id,
                            'ip_address' => $visitorIp,
                            'last_visited_at' => now(),
                        ]
                    );
                }
            }

            $clickCounter = $this->incrementSiteClickCounter();

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

    private function incrementSiteClickCounter(): int
    {
        static $hasSiteCounterTable;
        $hasSiteCounterTable ??= Schema::hasTable('site_counters');

        if ($hasSiteCounterTable) {
            DB::table('site_counters')->insertOrIgnore([
                'key' => 'site_click_counter',
                'value' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('site_counters')
                ->where('key', 'site_click_counter')
                ->update([
                    'value' => DB::raw('value + 1'),
                    'updated_at' => now(),
                ]);

            $value = DB::table('site_counters')
                ->where('key', 'site_click_counter')
                ->value('value');

            return max(0, (int) $value);
        }

        Cache::add('peoplecine.site_click_counter', 0, now()->addYears(10));

        return max(0, (int) Cache::increment('peoplecine.site_click_counter'));
    }

    private function resolveBuildTimestamp(): int
    {
        $cacheKey = 'peoplecine.build_datetime_unix';
        $fallback = time();

        /** @var mixed $cachedValue */
        $cachedValue = Cache::get($cacheKey);
        if (is_numeric($cachedValue) && (int) $cachedValue > 0) {
            return (int) $cachedValue;
        }

        $timestamp = collect([
            base_path('composer.lock'),
            base_path('app'),
            base_path('config'),
            base_path('resources'),
            base_path('routes'),
            public_path('js'),
            public_path('css'),
        ])
            ->map(fn (string $path): int => $this->latestMtime($path))
            ->max() ?: $fallback;

        Cache::put($cacheKey, $timestamp, now()->addSeconds(60));

        return $timestamp;
    }

    private function latestMtime(string $path): int
    {
        if (! file_exists($path)) {
            return 0;
        }

        if (is_file($path)) {
            return (int) (filemtime($path) ?: 0);
        }

        if (! is_dir($path)) {
            return 0;
        }

        $latest = 0;

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $entry) {
                if (! $entry->isFile()) {
                    continue;
                }

                $mtime = (int) ($entry->getMTime() ?: 0);
                if ($mtime > $latest) {
                    $latest = $mtime;
                }
            }
        } catch (Throwable) {
            return 0;
        }

        return $latest;
    }
}
