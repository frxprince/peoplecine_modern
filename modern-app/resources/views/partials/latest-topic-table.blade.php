@php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)

<div class="forum-table-wrap">
    <table class="forum-table forum-topic-table">
        <thead>
            <tr>
                <th width="44%">{{ $t('หัวข้อ', 'Topic') }}</th>
                <th width="20%">{{ $t('ห้อง', 'Room') }}</th>
                <th width="10%">{{ $t('อ่าน', 'Read') }}</th>
                <th width="10%">{{ $t('ตอบ', 'Reply') }}</th>
                <th width="16%">{{ $t('ล่าสุด', 'Last') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topics as $topic)
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
                        <div class="forum-topic-meta">
                            @include('partials.author-badge', [
                                'user' => $topic->author,
                                'fallback' => $t('สมาชิกที่ถูกเก็บเข้าคลัง', 'Archived member'),
                            ])
                        </div>
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
                            <span class="empty-state">{{ $t('คลังข้อมูล', 'Archive') }}</span>
                        @endif
                    </td>
                    <td class="forum-table__number">{{ number_format($topic->view_count) }}</td>
                    <td class="forum-table__number">{{ number_format($topic->reply_count) }}</td>
                    <td>
                        <div class="forum-last-meta">
                            {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: $t('คลังข้อมูล', 'Archive') }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="forum-table__empty">{{ $t('ยังไม่มีการนำเข้าหัวข้อ', 'No topics imported yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
