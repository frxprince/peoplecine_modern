@extends('layouts.app', ['title' => __('Member Dashboard')])

@section('content')
    <section class="hero-panel">
        <div>
            <p class="eyebrow">{{ __('Member Dashboard') }}</p>
            <h1>{{ __('Welcome back, :name.', ['name' => $user->displayName()]) }}</h1>
            <p class="lede">
                {{ __('This dashboard is the first member-facing step in the rebuild. It confirms your account is active in the new Laravel platform and gives you a clean view of your imported community footprint.') }}
            </p>
        </div>

        <div class="callout">
            <strong>{{ __('Account Summary') }}</strong>
            <p>{{ $user->email ?? __('No verified email stored yet.') }}</p>
            <p>{{ __('Role') }}: {{ ucfirst($user->role) }}</p>
            <p>{{ __('Status') }}: {{ $stats['profile_status'] }}</p>
            <p>{{ __('Member Level') }}: {{ $stats['member_level'] }}</p>
            <p>{{ __('Reply Posting') }}: {{ $stats['can_reply'] ? __('Allowed') : __('Read only') }}</p>
            <p>{{ __('New Topics') }}: {{ $stats['can_create_topic'] ? __('Allowed') : __('Not allowed') }}</p>
            <p>{{ __('Image Uploads') }}: {{ $stats['can_upload_images'] ? __('Allowed') : __('Not allowed') }}</p>
            <p>{{ __('VIP Room Access') }}: {{ $stats['can_access_vip'] ? __('Available') : __('Not available') }}</p>
            <div class="inline-actions">
                <a class="button button--ghost button--small" href="{{ route('messages.index') }}">
                    {{ __('Private Messages') }}
                    @if (($unreadMessageCount ?? 0) > 0)
                        ({{ __(':count new', ['count' => $unreadMessageCount]) }})
                    @endif
                </a>
                <a class="button button--ghost button--small" href="{{ route('password.edit') }}">{{ __('Change Password') }}</a>
                <a class="button button--ghost button--small" href="{{ route('profile.edit') }}">{{ __('Edit Profile') }}</a>
                @if ($user->isAdmin())
                    <a class="button button--ghost button--small" href="{{ route('admin.users.index') }}">{{ __('User Management') }}</a>
                @endif
            </div>
        </div>
    </section>

    <section class="stats-grid stats-grid--dashboard">
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Topics Started') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['topics_started']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Posts Written') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['posts_written']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Bookmarks') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['bookmarks']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Private Messages') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['private_messages']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Articles') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['articles']) }}</strong>
        </article>
        <article class="stat-card">
            <span class="stat-card__label">{{ __('Moderated Rooms') }}</span>
            <strong class="stat-card__value">{{ number_format($stats['moderated_rooms']) }}</strong>
        </article>
    </section>

    <section class="section-grid">
        <div class="panel">
            <div class="panel__header">
                <h2>{{ __('Recent Messages') }}</h2>
                <p>{{ __('Your latest private conversations.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentConversations as $conversation)
                    <a class="stack-card" href="{{ route('messages.show', $conversation) }}">
                        <div>
                            <strong>
                                {{ $conversation->subjectLineFor($user) }}
                                @if ($conversation->is_unread_for_viewer)
                                    <span class="badge">{{ __('New') }}</span>
                                @endif
                            </strong>
                            <p>
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $conversation->latestMessage?->body_html), 120) ?: __('No message text') }}
                            </p>
                        </div>
                        <span>{{ optional($conversation->latest_message_at)->format('d M Y') ?: __('Archive') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ __('No private messages linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ __('Your Latest Topics') }}</h2>
                <p>{{ __('Imported discussions you originally started.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentTopics as $topic)
                    <a class="stack-card" href="{{ route('topics.show', $topic) }}">
                        <div>
                            <strong>{{ $topic->title }}</strong>
                            <p>{{ $topic->room?->localizedName() ?? __('Archived room') }}</p>
                        </div>
                        <span>{{ optional($topic->created_at)->format('d M Y') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ __('No imported topics are linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>

        <div class="panel">
            <div class="panel__header">
                <h2>{{ __('Your Recent Replies') }}</h2>
                <p>{{ __('Latest imported posting activity tied to this account.') }}</p>
            </div>

            <div class="stack-list">
                @forelse ($recentPosts as $post)
                    <a class="stack-card" href="{{ route('topics.show', $post->topic) }}">
                        <div>
                            <strong>{{ $post->topic?->title ?? __('Archived topic') }}</strong>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $post->body_html), 120) }}</p>
                        </div>
                        <span>{{ optional($post->created_at)->format('d M Y') }}</span>
                    </a>
                @empty
                    <p class="empty-state">{{ __('No imported replies are linked to this account yet.') }}</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Saved Topics') }}</h2>
            <p>{{ __('Legacy bookmarks imported into the new platform.') }}</p>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="40%">{{ __('Topic') }}</th>
                        <th width="20%">{{ __('Room') }}</th>
                        <th width="18%">{{ __('Author') }}</th>
                        <th width="16%">{{ __('Last') }}</th>
                        <th width="6%">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookmarkedTopics as $topic)
                        <tr>
                            <td>
                                <a class="forum-topic-link" href="{{ route('topics.show', $topic) }}">
                                    @if ($topic->hasPostedImage())
                                        @include('partials.camera-indicator')
                                    @endif
                                    {{ $topic->title }}
                                    @if ($topic->isNewlyPosted())
                                        @include('partials.new-indicator')
                                    @endif
                                </a>
                            </td>
                            <td>
                                @if ($topic->room)
                                    <a class="forum-room-link" href="{{ route('rooms.show', $topic->room) }}">
                                        {!! $topic->room->coloredLocalizedNameHtml() !!}
                                        @if ($topic->room->hasRecentActivity())
                                            @include('partials.new-indicator')
                                        @endif
                                    </a>
                                @else
                                    <span class="empty-state">{{ __('Archived room') }}</span>
                                @endif
                            </td>
                            <td>
                                @include('partials.author-badge', [
                                    'user' => $topic->author,
                                    'fallback' => __('Archived member'),
                                ])
                            </td>
                            <td>
                                <div class="forum-last-meta">
                                    {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: __('Archive') }}
                                </div>
                            </td>
                            <td class="forum-table__action">
                                <form method="POST" action="{{ route('topics.bookmarks.destroy', $topic) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-button icon-button--danger" type="submit" title="{{ __('Remove from Saved Topics') }}" aria-label="{{ __('Remove from Saved Topics') }}">
                                        <span class="icon-button__trash" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="forum-table__empty">{{ __('No bookmarks imported for this member.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
