@extends('layouts.app', ['title' => __('New Private Message')])

@section('content')
    <section class="panel panel--hero">
        <p class="eyebrow">{{ __('Private Messages') }}</p>
        <h1>{{ __('Write a new private message') }}</h1>
        <p class="lede">
            {{ __('Send a direct message to another member by username or email address.') }}
        </p>
    </section>

    <section class="panel panel--tight">
        <form class="form-stack" method="POST" action="{{ route('messages.store') }}">
            @csrf

            <div class="form-field">
                <label for="recipient">{{ __('Recipient Username or Email') }}</label>
                <input
                    id="recipient"
                    name="recipient"
                    type="text"
                    value="{{ old('recipient', $recipient?->username) }}"
                    required
                >
                @error('recipient')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="subject">{{ __('Subject') }}</label>
                <input
                    id="subject"
                    name="subject"
                    type="text"
                    maxlength="255"
                    value="{{ old('subject') }}"
                    placeholder="{{ __('Optional conversation title') }}"
                >
                @error('subject')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-field">
                <label for="message_body">{{ __('Message') }}</label>
                @include('partials.tinymce-editor', [
                    'name' => 'message',
                    'id' => 'message_body',
                    'label' => __('Message'),
                    'value' => old('message'),
                    'rows' => 10,
                    'height' => 320,
                    'placeholder' => __('Write your private message here...'),
                ])
            </div>

            <div class="inline-actions">
                <button class="button" type="submit">{{ __('Send message') }}</button>
                <a class="button button--ghost button--small" href="{{ route('messages.index') }}">{{ __('Cancel') }}</a>
            </div>
        </form>
    </section>
@endsection
