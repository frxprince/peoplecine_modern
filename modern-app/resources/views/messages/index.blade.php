@extends('layouts.app', ['title' => __('Private Messages')])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Private Messages') }}</p>
        <h1>{{ __('Your message inbox') }}</h1>
        <p class="lede">
            {{ __('Imported member messages and new conversations now live together here in the rebuilt site.') }}
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('messages.create') }}">{{ __('Write new message') }}</a>
            <a class="button button--ghost button--small" href="{{ route('dashboard') }}">{{ __('Back to dashboard') }}</a>
        </div>
    </section>

    <section class="panel">
        <div class="panel__header">
            <h2>{{ $activeFolder === 'archived' ? __('Archived Conversations') : __('Inbox') }}</h2>
            <p>{{ __(':count conversations.', ['count' => $conversations->total()]) }}</p>
        </div>

        <div class="message-folder-tabs">
            <a
                @class([
                    'message-folder-tabs__link',
                    'message-folder-tabs__link--active' => $activeFolder === 'inbox',
                ])
                href="{{ route('messages.index') }}"
            >
                {{ __('Inbox') }}
            </a>
            <a
                @class([
                    'message-folder-tabs__link',
                    'message-folder-tabs__link--active' => $activeFolder === 'archived',
                ])
                href="{{ route('messages.index', ['folder' => 'archived']) }}"
            >
                {{ __('Archived') }}
            </a>
        </div>

        <form id="bulk-message-delete-form" method="POST" action="{{ route('messages.destroy-many') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="folder" value="{{ $activeFolder }}">
        </form>

        <div class="admin-bulk-toolbar message-bulk-toolbar">
            <label class="message-bulk-toolbar__toggle">
                <input
                    class="admin-user-table__checkbox"
                    type="checkbox"
                    data-message-select-all
                    aria-label="{{ __('Select all conversations on this page') }}"
                >
                <span>{{ __('Select all conversations on this page') }}</span>
            </label>
            <button
                class="button button--small button--danger"
                type="submit"
                form="bulk-message-delete-form"
                onclick="return confirm('{{ __('Remove selected conversations from your message list?') }}');"
            >
                {{ __('Delete Selected') }}
            </button>
        </div>

        <div class="forum-table-wrap">
            <table class="forum-table forum-topic-table">
                <thead>
                    <tr>
                        <th width="4%" class="forum-table__action">
                            <span class="sr-only">{{ __('Select') }}</span>
                        </th>
                        <th width="30%">{{ __('Conversation') }}</th>
                        <th width="18%">{{ __('With') }}</th>
                        <th width="26%">{{ __('Latest Message') }}</th>
                        <th width="8%">{{ __('Messages') }}</th>
                        <th width="8%">{{ __('Status') }}</th>
                        <th width="10%">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conversations as $conversation)
                        @php($latestMessage = $conversation->latestMessage)
                        @php($otherParticipants = $conversation->participants->reject(fn ($participant) => (int) $participant->id === (int) $user->id))
                        <tr @class(['message-row--unread' => $conversation->is_unread_for_viewer])>
                            <td class="forum-table__action">
                                <input
                                    class="admin-user-table__checkbox"
                                    type="checkbox"
                                    name="conversation_ids[]"
                                    value="{{ $conversation->id }}"
                                    form="bulk-message-delete-form"
                                    data-message-checkbox
                                    aria-label="{{ __('Select :username', ['username' => $conversation->subjectLineFor($user)]) }}"
                                >
                            </td>
                            <td>
                                <a class="forum-topic-link" href="{{ route('messages.show', $conversation) }}">
                                    {{ $conversation->subjectLineFor($user) }}
                                </a>
                                <div class="forum-last-meta">
                                    {{ optional($conversation->latest_message_at)->format('d M Y H:i') ?: __('Archive') }}
                                </div>
                            </td>
                            <td>
                                @forelse ($otherParticipants as $participant)
                                    <div class="message-recipient-line">
                                        @include('partials.author-badge', [
                                            'user' => $participant,
                                            'fallback' => 'Archived member',
                                        ])
                                    </div>
                                @empty
                                    <span class="empty-state">{{ __('Archived member') }}</span>
                                @endforelse
                            </td>
                            <td>
                                <strong>{{ $latestMessage?->sender?->displayName() ?? __('Archived member') }}</strong>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags((string) $latestMessage?->body_html), 110) ?: __('No message text') }}</p>
                            </td>
                            <td class="forum-table__number">{{ number_format($conversation->messages_count) }}</td>
                            <td>
                                @if ($conversation->is_muted_for_viewer)
                                    <span class="badge badge--muted">{{ __('Muted') }}</span>
                                @elseif ($conversation->is_unread_for_viewer)
                                    <span class="badge">{{ __('New') }}</span>
                                @else
                                    <span class="badge badge--muted">{{ __('Read') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="message-row-actions">
                                    <a class="button button--ghost button--small" href="{{ route('messages.show', $conversation) }}">{{ __('Open') }}</a>

                                    @if ($activeFolder === 'archived')
                                        <form method="POST" action="{{ route('messages.archive.destroy', $conversation) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button button--ghost button--small" type="submit">{{ __('Unarchive') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('messages.archive', $conversation) }}">
                                            @csrf
                                            <button class="button button--ghost button--small" type="submit">{{ __('Archive') }}</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('messages.destroy', $conversation) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="button button--small button--danger" type="submit" onclick="return confirm('{{ __('Remove this conversation from your message list?') }}');">{{ __('Delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="forum-table__empty">
                                {{ $activeFolder === 'archived' ? __('No archived conversations yet.') : __('No private messages yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap">
            {{ $conversations->links() }}
        </div>
    </section>
@endsection

@section('pageScripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.querySelector('[data-message-select-all]');
            const checkboxes = Array.from(document.querySelectorAll('[data-message-checkbox]'));

            if (!selectAll || checkboxes.length === 0) {
                return;
            }

            const syncSelectAll = function () {
                const checkedCount = checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                }).length;

                selectAll.checked = checkedCount === checkboxes.length;
                selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            };

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });

                selectAll.indeterminate = false;
            });

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', syncSelectAll);
            });

            syncSelectAll();
        });
    </script>
@endsection
