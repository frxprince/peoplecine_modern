<div class="forum-table-wrap">
    <table class="forum-table forum-topic-table">
        <thead>
            <tr>
                <th width="44%">{{ __('Topic') }}</th>
                <th width="20%">{{ __('Room') }}</th>
                <th width="10%">{{ __('Read') }}</th>
                <th width="10%">{{ __('Reply') }}</th>
                <th width="16%">{{ __('Last') }}</th>
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
                        </a>
                        <div class="forum-topic-meta">
                            @include('partials.author-badge', [
                                'user' => $topic->author,
                                'fallback' => 'Archived member',
                            ])
                        </div>
                    </td>
                    <td>
                        @if ($topic->room)
                            <a class="forum-room-link" href="{{ route('rooms.show', $topic->room) }}">
                                {!! $topic->room->coloredLocalizedNameHtml() !!}
                            </a>
                        @else
                            <span class="empty-state">{{ __('Archive') }}</span>
                        @endif
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
                    <td colspan="5" class="forum-table__empty">{{ __('No topics imported yet.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
