@extends('layouts.app', ['title' => __('User Management')])

@section('content')
    @php
        $clicksColumnLabel = __('Clicks');
        $lastVisitColumnLabel = __('Last visit');
        $defaultDirections = [
            'id' => 'asc',
            'user' => 'asc',
            'email' => 'asc',
            'visit_count' => 'desc',
            'last_visited_at' => 'desc',
            'legacy_level' => 'desc',
            'account_status' => 'asc',
            'role' => 'desc',
        ];

        $sortLink = function (string $column) use ($currentSort, $currentDirection, $defaultDirections) {
            $nextDirection = $currentSort === $column
                ? ($currentDirection === 'asc' ? 'desc' : 'asc')
                : ($defaultDirections[$column] ?? 'asc');

            return route('admin.users.index', array_merge(request()->query(), [
                'sort' => $column,
                'direction' => $nextDirection,
                'page' => 1,
            ]));
        };

        $queryState = [
            'page' => $users->currentPage(),
            'sort' => $currentSort,
            'direction' => $currentDirection,
            'search' => $currentSearch,
        ];
    @endphp

    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Admin') }}</p>
        <h1>{{ __('User Management') }}</h1>
        <p class="lede">
            {{ __('Restore the old PeopleCine access rules here. Admins can change member levels, promote another admin, ban, disable, or reactivate accounts.') }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('admin.rooms.index') }}">{{ __('Manage Rooms') }}</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Members') }}</h2>
            <p>{{ __(':count imported accounts.', ['count' => number_format($users->total())]) }}</p>
        </div>

        <form class="admin-search-form" method="GET" action="{{ route('admin.users.index') }}" data-live-search-form>
            <input name="sort" type="hidden" value="{{ $currentSort }}">
            <input name="direction" type="hidden" value="{{ $currentDirection }}">

            <label class="admin-search-form__label" for="admin-user-search">{{ __('Search users') }}</label>
            <input
                id="admin-user-search"
                class="admin-search-form__input"
                name="search"
                type="text"
                value="{{ $currentSearch }}"
                placeholder="{{ __('Search by ID, username, display name, or email') }}"
                autocomplete="off"
                data-live-search-input
            >
            <button class="button button--small" type="submit">{{ __('Search') }}</button>

            @if ($currentSearch !== '')
                <a class="button button--ghost button--small" href="{{ route('admin.users.index', ['sort' => $currentSort, 'direction' => $currentDirection]) }}">{{ __('Clear') }}</a>
            @endif
        </form>

        <form
            id="bulk-delete-form"
            class="admin-bulk-toolbar"
            method="POST"
            action="{{ route('admin.users.destroy-many', $queryState) }}"
        >
            @csrf
            @method('DELETE')
            <input name="page" type="hidden" value="{{ $users->currentPage() }}">
            <button
                class="button button--small button--danger"
                type="submit"
                onclick="return confirm(@js(__('Delete selected users with no posts, and disable selected users who already have posts or replies?')));"
            >
                {{ __('Delete / Disable Selected') }}
            </button>
            <span class="forum-last-meta">{{ __('Accounts with no posts or replies are deleted. Accounts with posted content are disabled instead. Your own admin account is always skipped.') }}</span>
            @error('user_ids')
                <p class="form-error">{{ $message }}</p>
            @enderror
            @error('new_password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </form>

        <div class="admin-user-table-wrap">
            <table class="admin-user-table">
                <thead>
                    <tr>
                        <th class="admin-user-table__select">
                            <input
                                id="select-all-users"
                                class="admin-user-table__checkbox"
                                type="checkbox"
                                aria-label="{{ __('Select all users on this page') }}"
                            >
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('id') }}">
                                {{ __('ID') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'id')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('user') }}">
                                {{ __('User') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'user')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('email') }}">
                                {{ __('Email') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'email')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('visit_count') }}">
                                {{ $clicksColumnLabel }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'visit_count')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('last_visited_at') }}">
                                {{ $lastVisitColumnLabel }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'last_visited_at')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('legacy_level') }}">
                                {{ __('Level') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'legacy_level')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('account_status') }}">
                                {{ __('Status') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'account_status')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>
                            <a class="admin-user-table__sort" href="{{ $sortLink('role') }}">
                                {{ __('Role') }}
                                <span class="admin-user-table__sort-indicator">
                                    @if ($currentSort === 'role')
                                        {{ $currentDirection === 'asc' ? '^' : 'v' }}
                                    @else
                                        +/-
                                    @endif
                                </span>
                            </a>
                        </th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $managedUser)
                        @php($formId = 'manage-user-'.$managedUser->id)
                        @php($passwordFormId = 'manage-user-password-'.$managedUser->id)
                        <tr>
                            <td class="admin-user-table__select">
                                <input
                                    class="admin-user-table__checkbox admin-user-table__checkbox-item"
                                    name="user_ids[]"
                                    type="checkbox"
                                    value="{{ $managedUser->id }}"
                                    form="bulk-delete-form"
                                    aria-label="{{ __('Select :username', ['username' => $managedUser->username]) }}"
                                >
                            </td>
                            <td class="admin-user-table__id">{{ $managedUser->id }}</td>
                            <td>
                                <strong>
                                    <a
                                        class="admin-user-table__profile-link"
                                        href="{{ route('admin.users.profile', $managedUser) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {{ $managedUser->displayName() }}
                                    </a>
                                </strong>
                                <div class="forum-last-meta">{{ $managedUser->username }}</div>
                            </td>
                            <td>{{ $managedUser->email ?: __('No email') }}</td>
                            <td class="admin-user-table__address">
                                @php($addressParts = array_filter([
                                    $managedUser->profile?->address,
                                    $managedUser->profile?->postal_code,
                                ]))
                                {{ $addressParts !== [] ? implode(' ', $addressParts) : __('Not available') }}
                            </td>
                            <td class="admin-user-table__phone">
                                {{ $managedUser->profile?->phone ?: __('Not available') }}
                            </td>
                            <td class="admin-user-table__id">{{ number_format((int) ($managedUser->visit_count ?? 0)) }}</td>
                            <td class="admin-user-table__id">
                                @php($effectiveLastVisit = $managedUser->effective_last_visited_at ?? $managedUser->last_visited_at)
                                {{ $effectiveLastVisit
                                    ? \Illuminate\Support\Carbon::parse((string) $effectiveLastVisit)->format('Y-m-d H:i')
                                    : '-' }}
                            </td>
                            <td class="admin-user-table__cell-control">
                                <select name="legacy_level" form="{{ $formId }}">
                                @foreach ([0, 1, 2, 3, 4, 9, 10] as $level)
                                    <option value="{{ $level }}" @selected($managedUser->memberLevel() === $level)>
                                        {{ match($level) {
                                            9 => __('Level 9 Admin'),
                                            10 => __('Level 10 Programmer'),
                                            default => __('Level :level', ['level' => $level]),
                                        } }}
                                    </option>
                                @endforeach
                                </select>
                            </td>
                            <td class="admin-user-table__cell-control">
                                <select name="account_status" form="{{ $formId }}">
                                    <option value="active" @selected($managedUser->account_status === 'active')>{{ __('Active') }}</option>
                                    <option value="banned" @selected($managedUser->account_status === 'banned')>{{ __('Banned') }}</option>
                                    <option value="disabled" @selected($managedUser->account_status === 'disabled')>{{ __('Disabled') }}</option>
                                </select>
                            </td>
                            <td class="admin-user-table__cell-control">
                                <select name="role" form="{{ $formId }}">
                                    <option value="user" @selected($managedUser->role === 'user')>{{ __('User') }}</option>
                                    <option value="admin" @selected($managedUser->isAdmin())>{{ __('Admin') }}</option>
                                </select>
                            </td>
                            <td class="admin-user-table__action">
                                <form
                                    id="{{ $formId }}"
                                    class="admin-user-form"
                                    method="POST"
                                    action="{{ route('admin.users.update', [
                                        'user' => $managedUser,
                                        'page' => $users->currentPage(),
                                        'sort' => $currentSort,
                                        'direction' => $currentDirection,
                                    ]) }}"
                                >
                                    @csrf
                                    @method('PUT')
                                    <button class="button button--small" type="submit">{{ __('Save') }}</button>
                                </form>

                                <details class="admin-user-password-box">
                                    <summary class="admin-user-password-box__summary">{{ __('Reset password') }}</summary>
                                    <form
                                        id="{{ $passwordFormId }}"
                                        class="admin-user-password-form"
                                        method="POST"
                                        action="{{ route('admin.users.password.update', array_merge(['user' => $managedUser], $queryState)) }}"
                                    >
                                        @csrf
                                        @method('PUT')
                                        <input name="page" type="hidden" value="{{ $users->currentPage() }}">

                                        <label class="admin-user-password-form__label" for="new_password_{{ $managedUser->id }}">{{ __('New password') }}</label>
                                        <input
                                            id="new_password_{{ $managedUser->id }}"
                                            name="new_password"
                                            type="password"
                                            required
                                        >

                                        <label class="admin-user-password-form__label" for="new_password_confirmation_{{ $managedUser->id }}">{{ __('Confirm password') }}</label>
                                        <input
                                            id="new_password_confirmation_{{ $managedUser->id }}"
                                            name="new_password_confirmation"
                                            type="password"
                                            required
                                        >

                                        <button class="button button--small" type="submit">{{ __('Set password') }}</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $users->links() }}
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all-users');
            const rowCheckboxes = Array.from(document.querySelectorAll('.admin-user-table__checkbox-item'));

            if (selectAll && rowCheckboxes.length > 0) {
                const syncHeaderState = function () {
                    const checkedCount = rowCheckboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    }).length;

                    selectAll.checked = checkedCount === rowCheckboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
                };

                selectAll.addEventListener('change', function () {
                    rowCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = selectAll.checked;
                    });

                    syncHeaderState();
                });

                rowCheckboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', syncHeaderState);
                });

                syncHeaderState();
            }

            const liveSearchForm = document.querySelector('[data-live-search-form]');
            const liveSearchInput = document.querySelector('[data-live-search-input]');

            if (!liveSearchForm || !liveSearchInput) {
                return;
            }

            let searchTimer = null;

            liveSearchInput.addEventListener('input', function () {
                if (searchTimer !== null) {
                    window.clearTimeout(searchTimer);
                }

                searchTimer = window.setTimeout(function () {
                    liveSearchForm.requestSubmit();
                }, 300);
            });
        });
    </script>
@endsection
