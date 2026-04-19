@extends('layouts.app', ['title' => __('Recover Password')])

@section('content')
    <section class="auth-shell">
        <div class="hero-panel auth-hero">
            <div>
                <p class="eyebrow">{{ __('Password Recovery') }}</p>
                <h1>{{ __('Recover access to your PeopleCine account.') }}</h1>
                <p class="lede">
                    {{ __('Enter your username or email address. If that account has an email address on file, we will send a password reset link there.') }}
                </p>
                <div class="callout">
                    <strong>{{ __('No email on your account?') }}</strong>
                    <p>{{ __('You will need admin help to recover the account, because self-service reset links can only be sent by email.') }}</p>
                </div>
                <div class="inline-actions">
                    <a class="button button--ghost button--small" href="{{ route('login') }}">{{ __('Back to sign in') }}</a>
                </div>
            </div>

            <div class="panel panel--tight">
                <form class="form-stack" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="form-field">
                        <label for="login">{{ __('Username or Email') }}</label>
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus>
                        @error('login')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <button class="button" type="submit">{{ __('Send recovery link') }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
