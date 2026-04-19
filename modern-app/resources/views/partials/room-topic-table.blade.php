<div class="forum-table-wrap">
    <table class="forum-table forum-topic-table">
        <thead>
            <tr>
                <th width="48%">{{ __('Topic') }}</th>
                <th width="12%">{{ __('Read') }}</th>
                <th width="12%">{{ __('Reply') }}</th>
                <th width="28%">{{ __('Last') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($topics as $topic)
                <tr>
                    <td>
                        <a class="forum-topic-link" href="{{ route('topics.show', $topic) }}">
                            @if ($topic->is_pinned)
                                <span class="badge">{{ __('Pinned') }}</span>
                            @endif
                            @if ($topic->is_locked)
                                <span class="badge badge--muted">{{ __('Locked') }}</span>
                            @endif
                            @if ($topic->hasPostedImage())
                                @include('partials.camera-indicator')
                            @endif
                            {{ $topic->title }}
                        </a>
                        <div class="forum-topic-meta">
                            @include('partials.author-badge', [
                                'user' => $topic->author,
                                'fallback' => 'Archived member',
                            ])
                        </div>
                    </td>
                    <td class="forum-table__number">{{ number_format($topic->view_count) }}</td>
                    <td class="forum-table__number">{{ number_format($topic->reply_count) }}</td>
                    <td>
                        <div class="forum-last-meta">
                            {{ optional($topic->last_posted_at)->format('d M Y H:i') ?: __('Archive') }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="forum-table__empty">{{ __('No imported topics found for this room yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
