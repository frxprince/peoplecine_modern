@extends('layouts.app', ['title' => 'Claim Legacy Account'])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
        <p class="eyebrow">{{ __('Legacy Migration') }}</p>
        <h1>{{ __('Activate your old account on the new platform.') }}</h1>
                <p class="lede">
                    We imported legacy members with temporary passwords for safety. To claim your account, confirm the
                    original username and email address, then choose a new password for the rebuilt site.
                </p>
                <div class="callout">
            <strong>{{ __('Need help?') }}</strong>
                    <p>
                        If your old account never had an email address on file, self-service claiming will not work yet.
                        An administrator will need to help with manual verification.
                    </p>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('claim-account.store') }}">
                    @csrf

                    <div class="form-field">
                    <label for="username">{{ __('Legacy Username') }}</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus>
                        @error('username')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                    <label for="email">{{ __('Legacy Email Address') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required>
                        @error('email')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                    <label for="password">{{ __('New Password') }}</label>
                        <input id="password" name="password" type="password" required>
                        @error('password')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="form-field">
                    <label for="password_confirmation">{{ __('Confirm New Password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required>
                    </div>

            <button class="button" type="submit">{{ __('Claim account') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
