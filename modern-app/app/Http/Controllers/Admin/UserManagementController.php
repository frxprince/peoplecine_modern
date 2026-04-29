<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $sort = (string) $request->query('sort', 'id');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $search = trim((string) $request->query('search', ''));
        $hasLastVisitedAtColumn = Schema::hasColumn('users', 'last_visited_at');
        $hasRecentVisitorsTable = Schema::hasTable('recent_visitors');

        $sortableColumns = [
            'id' => static function (Builder $query, string $direction): void {
                $query->orderBy('id', $direction);
            },
            'user' => static function (Builder $query, string $direction): void {
                $query
                    ->leftJoin('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                    ->orderByRaw("coalesce(nullif(user_profiles.display_name, ''), users.username) {$direction}")
                    ->select('users.*');
            },
            'email' => static function (Builder $query, string $direction): void {
                $query->orderBy('email', $direction);
            },
            'legacy_level' => static function (Builder $query, string $direction): void {
                $query->orderBy('legacy_level', $direction);
            },
            'account_status' => static function (Builder $query, string $direction): void {
                $query->orderBy('account_status', $direction);
            },
            'role' => static function (Builder $query, string $direction): void {
                $query->orderBy('role', $direction);
            },
            'visit_count' => static function (Builder $query, string $direction): void {
                $query->orderBy('visit_count', $direction);
            },
        ];

        $sortableColumns['last_visited_at'] = static function (Builder $query, string $direction) use ($hasLastVisitedAtColumn, $hasRecentVisitorsTable): void {
            if ($hasLastVisitedAtColumn && $hasRecentVisitorsTable) {
                $query
                    ->orderByRaw('COALESCE(users.last_visited_at, recent_visitors_last_seen.recent_last_visited_at) IS NULL')
                    ->orderByRaw("COALESCE(users.last_visited_at, recent_visitors_last_seen.recent_last_visited_at) {$direction}");

                return;
            }

            if ($hasLastVisitedAtColumn) {
                $query
                    ->orderByRaw('users.last_visited_at IS NULL')
                    ->orderBy('users.last_visited_at', $direction);

                return;
            }

            if ($hasRecentVisitorsTable) {
                $query
                    ->orderByRaw('recent_visitors_last_seen.recent_last_visited_at IS NULL')
                    ->orderBy('recent_visitors_last_seen.recent_last_visited_at', $direction);
            }
        };

        if (! array_key_exists($sort, $sortableColumns)) {
            $sort = 'id';
        }

        $usersQuery = User::query()->with('profile');

        if ($hasRecentVisitorsTable) {
            $recentVisitors = DB::table('recent_visitors')
                ->select('user_id', DB::raw('MAX(last_visited_at) as recent_last_visited_at'))
                ->whereNotNull('user_id')
                ->groupBy('user_id');

            $usersQuery
                ->leftJoinSub($recentVisitors, 'recent_visitors_last_seen', static function ($join): void {
                    $join->on('recent_visitors_last_seen.user_id', '=', 'users.id');
                })
                ->select('users.*')
                ->selectRaw(
                    $hasLastVisitedAtColumn
                        ? 'COALESCE(users.last_visited_at, recent_visitors_last_seen.recent_last_visited_at) as effective_last_visited_at'
                        : 'recent_visitors_last_seen.recent_last_visited_at as effective_last_visited_at'
                );
        }

        if ($search !== '') {
            $usersQuery->where(function (Builder $query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where('users.username', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhere('users.id', $search)
                    ->orWhereHas('profile', function (Builder $profileQuery) use ($like): void {
                        $profileQuery->where('display_name', 'like', $like)
                            ->orWhere('first_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like);
                    });
            });
        }

        $sortableColumns[$sort]($usersQuery, $direction);

        match ($sort) {
            'id' => $usersQuery
                ->orderBy('username'),
            'role' => $usersQuery
                ->orderByDesc('legacy_level')
                ->orderBy('username'),
            'visit_count' => $usersQuery
                ->orderBy('username'),
            'last_visited_at' => $usersQuery
                ->orderBy('username'),
            'legacy_level' => $usersQuery
                ->orderBy('username'),
            'account_status' => $usersQuery
                ->orderByDesc('legacy_level')
                ->orderBy('username'),
            'email' => $usersQuery
                ->orderBy('username'),
            'user' => $usersQuery
                ->orderBy('users.username'),
            default => $usersQuery->orderBy('username'),
        };

        return view('admin.users.index', [
            'title' => 'User Management',
            'users' => $usersQuery
                ->paginate(25)
                ->withQueryString(),
            'currentSort' => $sort,
            'currentDirection' => $direction,
            'currentSearch' => $search,
            'hasLastVisitedAtColumn' => $hasLastVisitedAtColumn,
            'hasRecentVisitorsTable' => $hasRecentVisitorsTable,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'legacy_level' => ['required', 'integer', Rule::in([0, 1, 2, 3, 4, 9, 10])],
            'account_status' => ['required', Rule::in(['active', 'banned', 'disabled'])],
            'role' => ['required', Rule::in(['user', 'admin'])],
        ]);

        $requestedLevel = (int) $validated['legacy_level'];
        $wantsProgrammer = $requestedLevel === 10;
        $wantsAdmin = $validated['role'] === 'admin' || in_array($requestedLevel, [9, 10], true);

        $user->forceFill([
            'legacy_level' => $wantsProgrammer ? 10 : ($wantsAdmin ? 9 : $requestedLevel),
            'account_status' => $validated['account_status'],
            'role' => $wantsAdmin ? 'admin' : 'user',
            'legacy_authorize' => $wantsProgrammer ? 'Programmer' : ($wantsAdmin ? 'Admin' : null),
        ])->save();

        return redirect()
            ->route('admin.users.index', $this->redirectQuery($request))
            ->with('status', "Updated {$user->username}.");
    }

    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'new_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['new_password']),
            'password_reset_required' => false,
            'remember_token' => Str::random(60),
        ])->save();

        return redirect()
            ->route('admin.users.index', $this->redirectQuery($request))
            ->with('status', "Password updated for {$user->username}.");
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $selectedIds = collect($validated['user_ids'])
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $currentAdminId = (int) $request->user()->id;

        $usersToDelete = User::query()
            ->withCount([
                'posts as authored_posts_count',
                'topics as authored_topics_count',
            ])
            ->whereIn('id', $selectedIds->all())
            ->get();

        $skippedOwnAccount = $usersToDelete->contains('id', $currentAdminId);

        /** @var Collection<int, User> $deletableUsers */
        $deletableUsers = $usersToDelete->reject(
            static fn (User $managedUser): bool => (int) $managedUser->id === $currentAdminId
                || ((int) ($managedUser->authored_posts_count ?? 0) > 0)
                || ((int) ($managedUser->authored_topics_count ?? 0) > 0)
        );

        /** @var Collection<int, User> $disableOnlyUsers */
        $disableOnlyUsers = $usersToDelete->reject(
            static fn (User $managedUser): bool => (int) $managedUser->id === $currentAdminId
        )->filter(
            static fn (User $managedUser): bool => ((int) ($managedUser->authored_posts_count ?? 0) > 0)
                || ((int) ($managedUser->authored_topics_count ?? 0) > 0)
        );

        DB::transaction(function () use ($deletableUsers, $disableOnlyUsers): void {
            foreach ($disableOnlyUsers as $managedUser) {
                $managedUser->forceFill([
                    'account_status' => 'disabled',
                    'remember_token' => Str::random(60),
                ])->save();
            }

            foreach ($deletableUsers as $managedUser) {
                $managedUser->forceDelete();
            }
        });

        $deletedCount = $deletableUsers->count();
        $disabledCount = $disableOnlyUsers->count();

        $status = match (true) {
            $deletedCount === 0 && $disabledCount === 0 && $skippedOwnAccount => 'Your own admin account cannot be deleted from this screen.',
            $deletedCount === 0 && $disabledCount === 1 && $skippedOwnAccount => 'Disabled 1 account with posted content. Your own admin account was skipped.',
            $deletedCount === 0 && $disabledCount > 1 && $skippedOwnAccount => "Disabled {$disabledCount} accounts with posted content. Your own admin account was skipped.",
            $deletedCount === 1 && $disabledCount === 0 && $skippedOwnAccount => 'Deleted 1 account. Your own admin account was skipped.',
            $deletedCount > 1 && $disabledCount === 0 && $skippedOwnAccount => "Deleted {$deletedCount} accounts. Your own admin account was skipped.",
            $deletedCount > 0 && $disabledCount > 0 && $skippedOwnAccount => "Deleted {$deletedCount} accounts and disabled {$disabledCount} posted accounts. Your own admin account was skipped.",
            $deletedCount === 0 && $disabledCount === 1 => 'Disabled 1 account with posted content.',
            $deletedCount === 0 && $disabledCount > 1 => "Disabled {$disabledCount} accounts with posted content.",
            $deletedCount === 1 && $disabledCount === 0 => 'Deleted 1 account.',
            $deletedCount > 1 && $disabledCount === 0 => "Deleted {$deletedCount} accounts.",
            $deletedCount > 0 && $disabledCount > 0 => "Deleted {$deletedCount} accounts and disabled {$disabledCount} posted accounts.",
            default => 'No accounts were changed.',
        };

        return redirect()
            ->route('admin.users.index', $this->redirectQuery($request))
            ->with('status', $status);
    }

    /**
     * @return array{page:int, sort:string, direction:string, search?:string}
     */
    protected function redirectQuery(Request $request): array
    {
        $query = [
            'page' => $request->integer('page', 1),
            'sort' => (string) $request->query('sort', 'id'),
            'direction' => (string) $request->query('direction', 'desc'),
        ];

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query['search'] = $search;
        }

        return $query;
    }
}
