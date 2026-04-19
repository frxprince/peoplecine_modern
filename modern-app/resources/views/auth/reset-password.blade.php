@extends('layouts.app', ['title' => __('Reset Password')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ __('Reset Password') }}</p>
                <h1>{{ __('Choose a new password for your PeopleCine account.') }}</h1>
                <p class="lede">
                    {{ __('Use a strong new password here, then head back to the login page and sign in with it.') }}
                </p>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('password.store') }}">
                    @csrf

                    <input name="token" type="hidden" value="{{ $token }}">

                    <div class="form-field">
                        <label for="email">{{ __('Email Address') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus>
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

                    <button class="button" type="submit">{{ __('Reset password') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
