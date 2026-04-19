@extends('layouts.app', ['title' => __(':name Profile', ['name' => $profileUser->displayName()])])

@section('content')
    @php($profile = $profileUser->profile)
    @php($addressVisible = $profile?->addressVisibleTo(auth()->user()) ?? false)
    @php($viewer = auth()->user())
    @php($isSelf = $viewer && (int) $viewer->id === (int) $profileUser->id)
    @php($isBlocked = $viewer?->hasBlockedUser($profileUser) ?? false)
    @php($isMuted = $viewer?->hasMutedUser($profileUser) ?? false)
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Member Profile') }}</p>
        <h1>{{ $profileUser->displayName() }}</h1>
        <p class="lede">
            {{ __('Username: :username | :level', ['username' => $profileUser->username, 'level' => $profileUser->memberLevelLabel()]) }}
        </p>
        @auth
            @if (! $isSelf && auth()->user()?->canUsePrivateMessages())
                <div class="inline-actions">
                    @if ($profileUser->canUsePrivateMessages() && ! $isBlocked && ! ($profileUser->hasBlockedUser($viewer)))
                        <a class="button button--ghost button--small" href="{{ route('messages.create', ['to' => $profileUser->id]) }}">{{ __('Send private message') }}</a>
                    @endif

                    @if ($isBlocked)
                        <form method="POST" action="{{ route('members.unblock', $profileUser) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button button--ghost button--small" type="submit">{{ __('Unblock member') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('members.block', $profileUser) }}">
                            @csrf
                            <button class="button button--ghost button--small" type="submit">{{ __('Block member') }}</button>
                        </form>
                    @endif

                    @if ($isMuted)
                        <form method="POST" action="{{ route('members.unmute', $profileUser) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button button--ghost button--small" type="submit">{{ __('Unmute alerts') }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('members.mute', $profileUser) }}">
                            @csrf
                            <button class="button button--ghost button--small" type="submit">{{ __('Mute alerts') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        @endauth
    </section>

    <section class="section-grid">
        <section class="panel panel--tight">
            <div class="profile-avatar-panel">
                <div>
                    <strong>{{ __('Avatar') }}</strong>
                    <p class="empty-state">{{ __('Legacy profile image from the original forum archive.') }}</p>
                </div>

                <div class="profile-avatar-panel__preview">
                    @if ($profileUser->avatarUrl())
                        <img class="profile-avatar-panel__image" src="{{ $profileUser->avatarUrl() }}" alt="{{ $profileUser->displayName() }} avatar" loading="lazy">
                    @else
                        <div class="profile-avatar-panel__placeholder">{{ __('No avatar') }}</div>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel panel--tight">
            <div class="stack-list">
                <div class="stack-card">
                    <div>
                        <strong>{{ __('Forum Level') }}</strong>
                        <p>{{ $profileUser->memberLevelLabel() }}</p>
                    </div>
                    <span class="badge">{{ __('Level :level', ['level' => $profileUser->memberLevel()]) }}</span>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ __('Province') }}</strong>
                        <p>{{ $profile?->province ?: __('Not shared') }}</p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ __('Postcode') }}</strong>
                        <p>
                            @if ($addressVisible)
                                {{ $profile?->postal_code ?: __('Not shared') }}
                            @else
                                {{ __('Hidden by member') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="stack-card">
                    <div>
                        <strong>{{ __('Joined Archive') }}</strong>
                        <p>{{ optional($profileUser->created_at)->format('d M Y H:i') ?: __('Archive') }}</p>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <section class="stats-grid stats-grid--dashboard">
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Topics') }}</span>
            <span class="stat-card__value">{{ number_format($profileUser->topics_count) }}</span>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Posts') }}</span>
            <span class="stat-card__value">{{ number_format($profileUser->posts_count) }}</span>
        </article>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Address') }}</h2>
        </div>
        <p class="lede">
            @if ($addressVisible)
                {{ $profile?->address ?: __('No address available.') }}
            @else
                {{ __('This member has hidden their address.') }}
            @endif
        </p>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Biography') }}</h2>
        </div>
        <p class="lede">{{ $profileUser->profile?->biography ?: __('No biography available.') }}</p>
    </section>
@endsection
