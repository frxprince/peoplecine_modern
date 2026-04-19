@extends('layouts.app', ['title' => $conversation->subjectLineFor($user)])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Private Messages') }}</p>
        <h1>{{ $conversation->subjectLineFor($user) }}</h1>
        <p class="lede">
            {{ __('Participants') }}:
            @if ($otherParticipants->isNotEmpty())
                {{ $otherParticipants->map(fn ($participant) => $participant->displayName())->join(', ') }}
            @else
                {{ __('Archived member') }}
            @endif
        </p>
        <div class="inline-actions">
            <a class="button button--ghost button--small" href="{{ route('messages.index') }}">{{ __('Back to inbox') }}</a>
            <a class="button button--ghost button--small" href="{{ route('messages.create') }}">{{ __('Write new message') }}</a>
            @if ($primaryOtherParticipant)
                @if ($isBlocked)
                    <form method="POST" action="{{ route('members.unblock', $primaryOtherParticipant) }}">
                        @csrf
                        @method('DELETE')
                        <button class="button button--ghost button--small" type="submit">{{ __('Unblock member') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('members.block', $primaryOtherParticipant) }}">
                        @csrf
                        <button class="button button--ghost button--small" type="submit">{{ __('Block member') }}</button>
                    </form>
                @endif

                @if ($isMuted)
                    <form method="POST" action="{{ route('members.unmute', $primaryOtherParticipant) }}">
                        @csrf
                        @method('DELETE')
                        <button class="button button--ghost button--small" type="submit">{{ __('Unmute alerts') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('members.mute', $primaryOtherParticipant) }}">
                        @csrf
                        <button class="button button--ghost button--small" type="submit">{{ __('Mute alerts') }}</button>
                    </form>
                @endif
            @endif

            @if ($isArchived)
                <form method="POST" action="{{ route('messages.archive.destroy', $conversation) }}">
                    @csrf
                    @method('DELETE')
                    <button class="button button--ghost button--small" type="submit">{{ __('Move to inbox') }}</button>
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
    </section>

    <section class="topic-thread">
        @forelse ($conversation->messages->sortBy('created_at') as $message)
            <article class="post-card">
                <div class="post-card__meta">
                    @include('partials.author-badge', [
                        'user' => $message->sender,
                        'fallback' => __('Archived member'),
                        'strong' => true,
                    ])
                    <span>{{ optional($message->created_at)->format('d M Y H:i') ?: __('Archive') }}</span>
                </div>

                <div class="post-card__body">{!! $message->renderedBodyHtml() !!}</div>
            </article>
        @empty
            <section class="panel panel--tight">
                <p class="empty-state">{{ __('This conversation has no visible messages.') }}</p>
            </section>
        @endforelse
    </section>

    <section class="panel panel--tight">
        <div class="panel__header">
            <h2>{{ __('Reply') }}</h2>
            <p>{{ __('Send a new private message in this conversation.') }}</p>
        </div>
        @if (! $canReply)
            <p class="empty-state">
                @if ($primaryOtherParticipant === null)
                    {{ __('This conversation no longer has an active member on the other side, so replies are disabled.') }}
                @elseif ($isBlocked)
                    {{ __('You have blocked this member. Unblock them to reply again.') }}
                @elseif ($isBlockedByParticipant)
                    {{ __('This member is not accepting private messages from you right now.') }}
                @else
                    {{ __('Replying is not available for this conversation right now.') }}
                @endif
            </p>
        @else
            <form class="form-stack" method="POST" action="{{ route('messages.reply', $conversation) }}">
                @csrf

                <div class="form-field">
                    <label for="reply_message_body">{{ __('Message') }}</label>
                    @include('partials.tinymce-editor', [
                        'name' => 'message',
                        'id' => 'reply_message_body',
                        'label' => __('Reply message'),
                        'value' => old('message'),
                        'rows' => 8,
                        'height' => 280,
                        'placeholder' => __('Write your reply here...'),
                    ])
                </div>

                <div class="inline-actions">
                    <button class="button" type="submit">{{ __('Send reply') }}</button>
                </div>
            </form>
        @endif
    </section>
@endsection
