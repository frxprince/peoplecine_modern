@extends('layouts.app', ['title' => $room->localizedName()])

@section('content')
    @php($maxPostImages = (int) config('peoplecine.post_image_limit', 12))

    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Forum Room') }}</p>
        <h1>
            {!! $room->coloredLocalizedNameHtml() !!}
            @if ($room->hasRecentActivity())
                @include('partials.new-indicator')
            @endif
        </h1>
        @if ($room->description)
            <div class="lede legacy-room-description">{!! $room->legacyDescriptionHtml() !!}</div>
        @endif
    </section>

    <section class="panel composer-panel">
        <div class="panel__header">
            <h2>{{ __('Create New Topic') }}</h2>
            @auth
                <p>
                    @if (auth()->user()->canCreateTopic())
                        {{ __('Start a new discussion in this room.') }}
                    @else
                        {{ __('Your current member level cannot create a new topic yet.') }}
                    @endif
                </p>
            @else
                <p>{{ __('Sign in to create a new topic.') }}</p>
            @endauth
        </div>

        @auth
            @if (auth()->user()->canCreateTopic())
                <form class="form-stack" method="POST" action="{{ route('rooms.topics.store', $room) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="form-field">
                        <label for="title">{{ __('Topic Title') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" maxlength="500">
                        @error('title')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                        <label for="body_html">{{ __('Message') }}</label>
                        @include('partials.tinymce-editor', [
                            'id' => 'body_html',
                            'name' => 'body_html',
                            'label' => __('Topic Message'),
                            'rows' => 8,
                            'placeholder' => __('Write your new topic message here...'),
                        ])
                    </div>

                    @if (auth()->user()->canUploadImages())
                        <div class="form-field">
                            @include('partials.image-uploader-v2', [
                                'inputId' => 'topic-attachments',
                                'maxFiles' => $maxPostImages,
                                'hint' => __('Drag and drop or select up to :count images for this topic.', ['count' => $maxPostImages]),
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
                        <button class="button" type="submit">{{ __('Post Topic') }}</button>
                    </div>
                </form>
            @else
                <p class="empty-state">{{ __('Reply access starts at level 1. New topic access starts at level 3.') }}</p>
            @endif
        @else
            <div class="inline-actions">
                <a class="button" href="{{ route('login') }}">{{ __('Sign in to post') }}</a>
            </div>
        @endauth
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Topics') }}</h2>
            <p>{{ __(':count archived discussions in this room.', ['count' => number_format($topics->total())]) }}</p>
        </div>

        @include('partials.room-topic-table', ['topics' => $topics])
        {{--
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="48%">Topic</th>
                        <th width="12%">Read</th>
                        <th width="12%">Reply</th>
                        <th width="28%">Last</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($topics as $topic)
                    <tr>
                        <td>
                            <a class="forum-topic-link" href="{{ route('topics.show', $topic) }}">
                            @if ($topic->is_pinned)
                                <span class="badge">Pinned</span>
                            @endif
                            @if ($topic->is_locked)
                                <span class="badge badge--muted">Locked</span>
                            @endif
                            @if ($topic->hasPostedImage())
                                @include('partials.camera-indicator')
                            @endif
                            {{ $topic->title }}
                        </strong>
                        <p>
                            @include('partials.author-badge', [
                                'user' => $topic->author,
                                'fallback' => 'Archived member',
                            ])
                            · {{ number_format($topic->reply_count) }} replies
                            · {{ number_format($topic->view_count) }} views
                        </p>
                    </div>
                    <span>{{ optional($topic->last_posted_at)->format('d M Y H:i') ?: 'Archive' }}</span>
                </a>
            @empty
                <p class="empty-state">No imported topics found for this room yet.</p>
            @endforelse
                </tbody>
            </table>
        --}}

        <div class="pagination-wrap">
            {{ $topics->links() }}
        </div>
    </section>
@endsection
