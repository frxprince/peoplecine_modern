@extends('layouts.app', ['title' => __('Forum Search')])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Forum Search') }}</p>
        <h1>{{ __('Search Topics and Replies') }}</h1>
        <p class="lede">
            {{ __('Search across topic titles and posted replies in all rooms you are allowed to read.') }}
        </p>

        <form class="forum-search-panel" method="GET" action="{{ route('search.index') }}">
            <label class="sr-only" for="forum-search-page-input">{{ __('Search the forum') }}</label>
            <input
                id="forum-search-page-input"
                name="q"
                type="search"
                value="{{ $searchQuery }}"
                maxlength="120"
                placeholder="{{ __('Search topic titles or reply text') }}"
            >
            <button class="button" type="submit">{{ __('Search') }}</button>
        </form>

        @if ($searchError)
            <p class="form-error">{{ $searchError }}</p>
        @elseif ($searchQuery !== '')
            <p class="forum-last-meta">
                {{ __('Search results for') }} <strong>{{ $searchQuery }}</strong>.
            </p>
        @endif
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Topic Matches') }}</h2>
            <p>
                @if ($topicResults)
                    {{ __(':count matching topic titles.', ['count' => $topicResults->total()]) }}
                @else
                    {{ __('Search to see matching topic titles.') }}
                @endif
            </p>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="44%">{{ __('Topic') }}</th>
                        <th width="16%">{{ __('Room') }}</th>
                        <th width="12%">{{ __('Replies') }}</th>
                        <th width="28%">{{ __('Last') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($topicResults && $topicResults->isNotEmpty())
                        @foreach ($topicResults as $topic)
                            <tr>
                                <td>
                                    <a class="forum-topic-link" href="{{ $topic->result_url }}">{{ $topic->title }}</a>
                                    <div class="forum-topic-meta">
                                        @include('partials.author-badge', [
                                            'user' => $topic->author,
                                            'fallback' => __('Archived member'),
                                        ])
                                    </div>
                                </td>
                                <td>
                                    <a class="forum-room-link" href="{{ route('rooms.show', $topic->room) }}">
                                        {!! $topic->room?->coloredLocalizedNameHtml() !!}
                                    </a>
                                </td>
                                <td class="forum-table__number">{{ number_format($topic->reply_count) }}</td>
                                <td>
                                    <div class="forum-last-meta">
                                        {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: __('Archive') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="forum-table__empty">
                                @if ($searchQuery === '')
                                    {{ __('Enter a search term to look for matching topics.') }}
                                @else
                                    {{ __('No topic titles matched your search.') }}
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($topicResults)
            <div class="pagination-wrap">
                {{ $topicResults->links() }}
            </div>
        @endif
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ __('Reply Matches') }}</h2>
            <p>
                @if ($replyResults)
                    {{ __(':count matching posts.', ['count' => $replyResults->total()]) }}
                @else
                    {{ __('Search to see matching replies and first-post messages.') }}
                @endif
            </p>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="34%">{{ __('Topic') }}</th>
                        <th width="18%">{{ __('Posted By') }}</th>
                        <th width="34%">{{ __('Matched Text') }}</th>
                        <th width="14%">{{ __('Posted') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @if ($replyResults && $replyResults->isNotEmpty())
                        @foreach ($replyResults as $post)
                            <tr>
                                <td>
                                    <a class="forum-topic-link" href="{{ $post->result_url }}">{{ $post->topic?->title ?? __('Archived topic') }}</a>
                                    <div class="forum-topic-meta">
                                        @if ($post->topic?->room)
                                            <a class="forum-room-link" href="{{ route('rooms.show', $post->topic->room) }}">
                                                {!! $post->topic->room->coloredLocalizedNameHtml() !!}
                                            </a>
                                        @endif
                                        <span>
                                            @if ($post->isTopicStarter())
                                                {{ __('starter post') }}
                                            @else
                                                {{ __('reply #:number', ['number' => $post->position_in_topic]) }}
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @include('partials.author-badge', [
                                        'user' => $post->author,
                                        'fallback' => __('Archived member'),
                                    ])
                                </td>
                                <td>
                                    <a class="forum-search-snippet" href="{{ $post->result_url }}">
                                        {{ $post->search_excerpt }}
                                    </a>
                                </td>
                                <td>
                                    <div class="forum-last-meta">
                                        {{ optional($post->created_at)->format('d M Y H:i') ?: __('Archive') }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="forum-table__empty">
                                @if ($searchQuery === '')
                                    {{ __('Enter a search term to look for matching replies.') }}
                                @else
                                    {{ __('No replies matched your search.') }}
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if ($replyResults)
            <div class="pagination-wrap">
                {{ $replyResults->links() }}
            </div>
        @endif
    </section>
@endsection
