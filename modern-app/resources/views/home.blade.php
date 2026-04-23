@extends('layouts.app', ['title' => __('PeopleCine Main Forum')])

@php($projectorManualLabel = app()->getLocale() === 'th' ? 'คู่มือโปรเจคเตอร์' : 'Projector manual')

@php($latestUpdateLabel = app()->getLocale() === 'th' ? 'อัปเดตล่าสุด' : 'Latest update')
@php($latestUpdateDescription = app()->getLocale() === 'th' ? 'ความเคลื่อนไหวล่าสุดจากหัวข้อในเว็บบอร์ด' : 'Latest activity from the forum topics.')

@section('content')
    <section class="legacy-panel">
        <div class="legacy-panel__header">
            <h2>{{ __('Open-air cinema community') }}</h2>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table">
                <thead>
                    <tr>
                        <th width="40%">{{ __('Section') }}</th>
                        <th width="8%">{{ __('Topic') }}</th>
                        <th width="8%">{{ __('Read') }}</th>
                        <th width="8%">{{ __('Reply') }}</th>
                        <th width="36%">{{ __('Last') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rooms as $room)
                        <tr>
                            <td>
                                <a class="forum-room-link" href="{{ route('rooms.show', $room) }}">
                                    {!! $room->coloredLocalizedNameHtml() !!}
                                    @if ($room->hasRecentActivity())
                                        @include('partials.new-indicator')
                                    @endif
                                </a>
                                @if ($room->description)
                                    <div class="forum-room-description">{!! $room->legacyDescriptionHtml() !!}</div>
                                @endif
                            </td>
                            <td>{{ number_format($room->topics_count) }}</td>
                            <td>{{ number_format((int) ($room->total_views ?? 0)) }}</td>
                            <td>{{ number_format((int) ($room->total_replies ?? 0)) }}</td>
                            <td>
                                @if ($room->latestTopic)
                                    <a class="forum-last-link" href="{{ route('topics.show', $room->latestTopic) }}">
                                        @if ($room->latestTopic->hasPostedImage())
                                            @include('partials.camera-indicator')
                                        @endif
                                        {{ \Illuminate\Support\Str::limit($room->latestTopic->title, 62) }}
                                        @if ($room->latestTopic->isNewlyPosted())
                                            @include('partials.new-indicator')
                                        @endif
                                    </a>
                                    <div class="forum-last-meta">
                                        @include('partials.author-badge', [
                                            'user' => $room->latestTopic->author,
                                            'fallback' => 'Archived member',
                                        ])
                                        |
                                        {{ optional($room->latestTopic->last_posted_at)->format('d M Y H:i') ?: __('Archive') }}
                                    </div>
                                @else
                                    <span class="empty-state">{{ __('No topics yet.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="forum-table__empty">{{ __('No forum rooms imported yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="legacy-panel">
        <div class="legacy-panel__header">
            <h2>{{ $latestUpdateLabel }}</h2>
            <p>{{ $latestUpdateDescription }}</p>
        </div>

        @include('partials.latest-topic-table', ['topics' => $latestTopics])
        {{--
            @forelse ($latestTopics as $topic)
                <a class="legacy-topic-row" href="{{ route('topics.show', $topic) }}">
                    <div>
                        <strong>
                            @if ($topic->hasPostedImage())
                                @include('partials.camera-indicator')
                            @endif
                            {{ $topic->title }}
                        </strong>
                        <p>{{ $topic->room?->name }} ยท {{ optional($topic->author?->profile)->display_name ?? $topic->author?->username ?? 'Archived member' }}</p>
                    </div>
                    <span>{{ optional($topic->last_posted_at)->format('d M Y H:i') ?: 'Archive' }}</span>
                </a>
            @empty
                <p class="empty-state">No topics imported yet.</p>
            @endforelse
        --}}
    </section>
@endsection
