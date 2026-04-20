@extends('layouts.app', ['title' => $topic->title])

@section('content')
    @php($maxPostImages = (int) config('peoplecine.post_image_limit', 12))

    <section class="panel panel--hero">
        <p class="eyebrow">{{ $topic->room?->localizedName() ?? __('Discussion') }}</p>
        <h1>
            @if ($topic->hasPostedImage())
                @include('partials.camera-indicator')
            @endif
            {{ $topic->title }}
        </h1>
        <p class="lede">
            {{ __(':views views | :replies replies | Last activity :last', [
                'views' => number_format($topic->view_count),
                'replies' => number_format($topic->reply_count),
                'last' => optional($topic->last_posted_at)->format('d M Y H:i') ?: __('Archive'),
            ]) }}
        </p>
        <div class="inline-actions">
            <a
                class="icon-button icon-button--facebook"
                href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                target="_blank"
                rel="noopener noreferrer"
                title="{{ __('Share this topic on Facebook') }}"
                aria-label="{{ __('Share this topic on Facebook') }}"
            >
                <span class="icon-button__facebook" aria-hidden="true"></span>
            </a>
        @auth
            @if (isset($isBookmarked) && $isBookmarked)
                <form method="POST" action="{{ route('topics.bookmarks.destroy', $topic) }}">
                    @csrf
                    @method('DELETE')
                    <button class="icon-button icon-button--pin icon-button--active" type="submit" title="{{ __('Remove from Saved Topics') }}" aria-label="{{ __('Remove from Saved Topics') }}">
                        <span class="icon-button__disk" aria-hidden="true"></span>
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('topics.bookmarks.store', $topic) }}">
                    @csrf
                    <button class="icon-button icon-button--pin" type="submit" title="{{ __('Save to Saved Topics') }}" aria-label="{{ __('Save to Saved Topics') }}">
                        <span class="icon-button__disk" aria-hidden="true"></span>
                    </button>
                </form>
            @endif

            @if (auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('topics.destroy', $topic) }}" onsubmit="return confirm(@js(__('Delete this topic and all replies?')));">
                        @csrf
                        @method('DELETE')
                        <button class="icon-button icon-button--danger" type="submit" title="{{ __('Delete topic') }}" aria-label="{{ __('Delete topic') }}">
                            <span class="icon-button__trash" aria-hidden="true"></span>
                        </button>
                    </form>
            @endif
        @endauth
        </div>
    </section>

    <section class="topic-thread">
        @forelse ($posts as $post)
            <article class="post-card" id="post-{{ $post->id }}">
                <div class="post-card__meta">
                    @if ($post->hasPostedImage())
                        @include('partials.camera-indicator')
                    @endif
                    @include('partials.author-badge', [
                        'user' => $post->author,
                        'fallback' => 'Archived member',
                        'strong' => true,
                    ])
                    <span>#{{ $post->position_in_topic }}</span>
                    <span>{{ optional($post->created_at)->format('d M Y H:i') ?: __('Archive') }}</span>
                    @if ($post->wasEdited())
                        @php($latestChange = $post->changeLogs->first())
                        <span class="post-card__edited-note">
                            Edited {{ $post->changeLogs->count() }} time{{ $post->changeLogs->count() === 1 ? '' : 's' }}
                            @if ($latestChange)
                                by {{ $latestChange->editor?->displayName() ?? 'Unknown member' }}
                                on {{ optional($latestChange->created_at)->format('d M Y H:i') }}
                            @endif
                        </span>
                        <details class="post-card__change-log">
                            <summary>{{ __('Change log') }}</summary>
                            <div class="post-card__change-log-list">
                                @foreach ($post->changeLogs as $changeLog)
                                    <div class="post-card__change-log-item">
                                        <strong>{{ $changeLog->summary }}</strong>
                                        <span>
                                            {{ $changeLog->editor?->displayName() ?? 'Unknown member' }}
                                            | {{ optional($changeLog->created_at)->format('d M Y H:i') ?: 'Archive' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                    @auth
                        <div class="post-card__actions">
                        @if ($post->isEditableBy(auth()->user()))
                            <details class="post-card__edit-toggle" @if ((int) old('editing_post_id') === (int) $post->id) open @endif>
                                <summary class="post-card__edit-summary" title="{{ __('Edit your post') }}">
                                    <span class="post-card__edit-icon" aria-hidden="true"></span>
                                    <span>{{ __('Edit') }}</span>
                                </summary>
                                <div class="post-card__edit-panel">
                                    <form class="form-stack" method="POST" action="{{ route('topics.posts.update', [$topic, $post]) }}" enctype="multipart/form-data">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="editing_post_id" value="{{ $post->id }}">

                                        @if ($post->isTopicStarter())
                                            <div class="form-field">
                                                <label for="topic_title_{{ $post->id }}">{{ __('Topic Title') }}</label>
                                                <input
                                                    id="topic_title_{{ $post->id }}"
                                                    name="topic_title"
                                                    type="text"
                                                    maxlength="500"
                                                    value="{{ old('topic_title', $topic->title) }}"
                                                >
                                                @error('topic_title')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif

                                        <div class="form-field">
                                            <label for="message_body_{{ $post->id }}">{{ __('Message') }}</label>
                                            @include('partials.tinymce-editor', [
                                                'id' => 'message_body_'.$post->id,
                                                'name' => 'message_body',
                                                'label' => __('Edit Message'),
                                                'rows' => 7,
                                                'value' => $post->body_html,
                                                'errorKey' => 'message_body',
                                                'placeholder' => __('Edit your message here...'),
                                            ])
                                        </div>

                                        @if ($post->attachments->isNotEmpty())
                                            <div class="form-field">
                                                <label>{{ __('Current Images') }}</label>
                                                <div class="attachment-grid attachment-grid--manage">
                                                    @foreach ($post->attachments as $attachment)
                                                        <label class="attachment-card attachment-card--manage">
                                                            @if ($attachment->isImage() && $attachment->legacyUrl())
                                                                <img
                                                                    class="attachment-card__image"
                                                                    src="{{ $attachment->legacyUrl() }}"
                                        alt="{{ __('Current attachment') }}"
                                                                    loading="lazy"
                                                                >
                                                            @endif
                                                            <span class="attachment-card__name">{{ $attachment->original_filename }}</span>
                                                            <span class="attachment-card__toggle">
                                                                <input
                                                                    type="checkbox"
                                                                    name="remove_attachment_ids[]"
                                                                    value="{{ $attachment->id }}"
                                                                    @checked(in_array($attachment->id, old('remove_attachment_ids', [])))
                                                                >
                                                                {{ __('Remove this image') }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if (auth()->user()->canUploadImages())
                                            <div class="form-field">
                                                @include('partials.image-uploader-v2', [
                                                    'inputId' => 'edit-attachments-'.$post->id,
                                                    'maxFiles' => $maxPostImages,
                                                    'hint' => __('Upload replacement or additional images for post #:number.', ['number' => $post->position_in_topic]),
                                                ])
                                                @error('attachments')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                                @error('attachments.*')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                                @error('staged_uploads')
                                                    <p class="form-error">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif

                                        <div class="inline-actions">
                                            <button class="button button--small" type="submit">{{ __('Save Changes') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @endif
                        @if (auth()->user()->isAdmin() && ! $post->isTopicStarter())
                            <form method="POST" action="{{ route('topics.posts.destroy', [$topic, $post]) }}" onsubmit="return confirm(@js(__('Delete this reply?')));">
                                @csrf
                                @method('DELETE')
                                <button class="icon-button icon-button--danger" type="submit" title="{{ __('Delete reply') }}" aria-label="{{ __('Delete reply') }}">
                                    <span class="icon-button__trash" aria-hidden="true"></span>
                                </button>
                            </form>
                        @endif
                        </div>
                    @endauth
                </div>

                <div class="post-card__body">
                    {!! $post->renderedBodyHtml() !!}
                </div>

                @if ($post->attachments->isNotEmpty())
                    <div class="attachment-grid">
                        @foreach ($post->attachments as $attachment)
                            @if ($attachment->isImage() && $attachment->legacyUrl())
                                <a class="attachment-card attachment-card--image" href="{{ $attachment->legacyUrl() }}" target="_blank" rel="noopener noreferrer">
                                    <img
                                        class="attachment-card__image"
                                        src="{{ $attachment->legacyUrl() }}"
                                        alt="{{ __('Posted image') }}"
                                        loading="lazy"
                                    >
                                </a>
                            @else
                                <div class="attachment-card">
                                    @if ($attachment->legacyUrl())
                                        <a class="attachment-card__name" href="{{ $attachment->legacyUrl() }}" target="_blank" rel="noopener noreferrer">
                                            {{ $attachment->original_filename }}
                                        </a>
                                    @else
                                        <span class="attachment-card__name">{{ $attachment->original_filename }}</span>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </article>
        @empty
            <div class="panel">
                <p class="empty-state">{{ __('No posts imported for this topic yet.') }}</p>
            </div>
        @endforelse
    </section>

    <section class="panel composer-panel">
        <div class="panel__header">
            <h2>{{ __('Post Reply') }}</h2>
            @auth
                <p>
                    @if ($topic->is_locked && ! auth()->user()->isAdmin())
                        {{ __('This topic is locked and cannot accept new replies.') }}
                    @elseif (auth()->user()->canReply())
                        {{ __('Add your reply to this discussion.') }}
                    @else
                        {{ __('Your current member level cannot post replies yet.') }}
                    @endif
                </p>
            @else
                <p>{{ __('Sign in to reply to this topic.') }}</p>
            @endauth
        </div>

        @auth
            @if ($topic->is_locked && ! auth()->user()->isAdmin())
                <p class="empty-state">{{ __('This topic is locked.') }}</p>
            @elseif (auth()->user()->canReply())
                <form class="form-stack" method="POST" action="{{ route('topics.replies.store', $topic) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-field">
                        <label for="body_html">{{ __('Reply Message') }}</label>
                        @include('partials.tinymce-editor', [
                            'id' => 'body_html',
                            'name' => 'body_html',
                            'label' => __('Reply Message'),
                            'rows' => 7,
                            'placeholder' => __('Write your reply here...'),
                        ])
                    </div>

                    @if (auth()->user()->canUploadImages())
                        <div class="form-field">
                            @include('partials.image-uploader-v2', [
                                'inputId' => 'reply-attachments',
                                'maxFiles' => $maxPostImages,
                                'hint' => __('Drag and drop or select up to :count images for this reply.', ['count' => $maxPostImages]),
                            ])
                            @error('attachments')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                            @error('attachments.*')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="inline-actions">
                        <button class="button" type="submit">{{ __('Post Reply') }}</button>
                    </div>
                </form>
            @else
                <p class="empty-state">{{ __('Reply access starts at member level 1.') }}</p>
            @endif
        @else
            <div class="inline-actions">
                <a class="button" href="{{ route('login') }}">{{ __('Sign in to reply') }}</a>
            </div>
        @endauth
    </section>
@endsection
