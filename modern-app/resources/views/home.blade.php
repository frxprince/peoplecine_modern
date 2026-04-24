@extends('layouts.app', ['title' => __('PeopleCine Main Forum')])

@php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)
@php($latestUpdateLabel = app()->getLocale() === 'th' ? 'อัปเดตล่าสุด' : 'Latest update')
@php($latestUpdateDescription = app()->getLocale() === 'th' ? 'ความเคลื่อนไหวล่าสุดจากหัวข้อในเว็บบอร์ด' : 'Latest activity from the forum topics.')

@section('content')
    <section class="legacy-panel">
        <div class="legacy-panel__header">
            <h2>{{ $t('ชุมชนหนังกลางแปลง', 'Open-air cinema community') }}</h2>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table">
                <thead>
                    <tr>
                        <th width="40%">{{ $t('ห้อง', 'Section') }}</th>
                        <th width="8%">{{ $t('หัวข้อ', 'Topic') }}</th>
                        <th width="8%">{{ $t('อ่าน', 'Read') }}</th>
                        <th width="8%">{{ $t('ตอบ', 'Reply') }}</th>
                        <th width="36%">{{ $t('ล่าสุด', 'Last') }}</th>
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
                                            'fallback' => $t('สมาชิกที่ถูกเก็บเข้าคลัง', 'Archived member'),
                                        ])
                                        |
                                        {{ optional($room->latestTopic->last_posted_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}
                                    </div>
                                @else
                                    <span class="empty-state">{{ $t('ยังไม่มีหัวข้อ', 'No topics yet.') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="forum-table__empty">{{ $t('ยังไม่มีการนำเข้าห้องเว็บบอร์ด', 'No forum rooms imported yet.') }}</td>
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
    </section>
@endsection
