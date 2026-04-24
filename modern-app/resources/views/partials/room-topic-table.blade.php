@php($t = static fn (string $thai, string $english): string => app()->getLocale() === 'th' ? $thai : $english)

<div class="forum-table-wrap">
    <table class="forum-table forum-topic-table">
        <thead>
            <tr>
                <th width="48%">{{ $t('หัวข้อ', 'Topic') }}</th>
                <th width="12%">{{ $t('อ่าน', 'Read') }}</th>
                <th width="12%">{{ $t('ตอบ', 'Reply') }}</th>
                <th width="28%">{{ $t('ล่าสุด', 'Last') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topics as $topic)
                <tr>
                    <td>
                        <a class="forum-topic-link" href="{{ route('topics.show', $topic) }}">
                            @if ($topic->is_pinned)
                                <span class="badge">{{ $t('ปักหมุด', 'Pinned') }}</span>
                            @endif
                            @if ($topic->is_locked)
                                <span class="badge badge--muted">{{ $t('ล็อก', 'Locked') }}</span>
                            @endif
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
                    <td colspan="4" class="forum-table__empty">{{ $t('ยังไม่มีหัวข้อที่นำเข้าในห้องนี้', 'No imported topics found for this room yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
